<?php
// Compatibilidad PS 1.6 / PS 1.7+: cargamos la clase via require_once como fallback
// En PS 1.7+ el autoloader PSR-4 de Composer ya la resuelve via composer.json
if (!class_exists('Verifactu\\VerifactuClasses\\ApiVerifactu') && !class_exists('ApiVerifactu')) {
    require_once dirname(__FILE__) . '/../../classes/ApiVerifactu.php';
}
require_once dirname(__FILE__) . '/../../services/VerifactuFacturaeService.php';
use Verifactu\VerifactuClasses\ApiVerifactu;

class AdminVerifactuAjaxController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->ajax = true;
    }

    public function displayAjaxEnviarVerifactu()
    {
        $id_order = (int)Tools::getValue('id_order');
        $type = Tools::getValue('type', 'alta');

        if (!$id_order || !in_array($type, ['alta', 'abono'])) {
            die(json_encode(['error' => 'Parámetros no válidos.']));
        }

        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            die(json_encode(['error' => 'No se pudo cargar el pedido.']));
        }

        $id_shop    = (int)$order->id_shop;
        $api_token  = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
        $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);

        $av = new ApiVerifactu($api_token, $debug_mode, $id_shop);
        $response = $av->sendAltaVerifactu($id_order, $type);

        header('Content-Type: application/json');
        die($response);
    }

    public function displayAjaxCheckDNI()
    {
        $id_order = (int)Tools::getValue('id_order');
        if (!$id_order) {
            die(json_encode(['error' => 'ID de pedido no válido.']));
        }

        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            die(json_encode(['error' => 'No se pudo cargar el pedido.']));
        }

        $id_shop    = (int)$order->id_shop;
        $api_token  = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
        $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);

        $av = new ApiVerifactu($api_token, $debug_mode, $id_shop);
        $response = $av->checkDNI($id_order);

        header('Content-Type: application/json');
        die($response);
    }

    public function displayAjaxAnularVerifactu()
    {
        $id_order = (int)Tools::getValue('id_order');
        if (!$id_order) {
            die(json_encode(['error' => 'ID de pedido no válido.']));
        }

        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            die(json_encode(['error' => 'No se pudo cargar el pedido.']));
        }

        $id_shop    = (int)$order->id_shop;
        $api_token  = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
        $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);

        $av = new ApiVerifactu($api_token, $debug_mode, $id_shop);
        $response = $av->sendAnulacionVerifactu($id_order);

        header('Content-Type: application/json');
        die($response);
    }

    /**
     * Throttled check of pending VeriFactu statuses.
     */
    public function displayAjaxCheckPendingStatus()
    {
        $id_shop = (int)$this->context->shop->id;

        if (!$id_shop) {
            die(json_encode(['error' => 'Por favor, seleccione una tienda específica para realizar esta acción.']));
        }

        $throttle_key = 'VERIFACTU_LAST_CRON_RUN_' . $id_shop;
        $interval     = 60;
        $last_run     = (int)Configuration::get($throttle_key);

        if ((time() - $last_run) < $interval) {
            header('Content-Type: application/json');
            die(json_encode([
                'success' => true,
                'skipped' => true,
                'message' => 'Throttle activo. Próxima ejecución en ' . ($interval - (time() - $last_run)) . 's.',
            ]));
        }

        $api_token  = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
        $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);

        Configuration::updateValue($throttle_key, time());

        $av = new ApiVerifactu($api_token, $debug_mode, $id_shop);
        $av->checkPendingInvoices();
    }

    /**
     * Checks the VeriFactu API status.
     */
    public function displayAjaxCheckStatus()
    {
        $module = Module::getInstanceByName('verifactu');
        if (!Validate::isLoadedObject($module)) {
            die(json_encode(['success' => false, 'message' => 'No se pudo cargar el módulo.']));
        }

        $result = $module->checkApiStatus();
        die(json_encode($result));
    }

    // =========================================================================
    // Factura Electrónica (Facturae)
    // =========================================================================

    /**
     * Generates a Facturae via the InFoAL API and records the id_facturae_api in DB.
     * The .xsig is stored on the API side — nothing is saved locally.
     *
     * POST params:
     *   id_order_invoice  int  PrestaShop OrderInvoice ID
     */
    public function displayAjaxGenerarFacturae()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $idOrderInvoice = (int)Tools::getValue('id_order_invoice');

            if (!$idOrderInvoice) {
                die(json_encode(array('success' => false, 'error' => 'Parámetro id_order_invoice no válido.')));
            }

            $invoice = new OrderInvoice($idOrderInvoice);
            if (!Validate::isLoadedObject($invoice)) {
                die(json_encode(array('success' => false, 'error' => 'Factura no encontrada (id=' . $idOrderInvoice . ').')));
            }

            $order = new Order((int)$invoice->id_order);
            if (!Validate::isLoadedObject($order)) {
                die(json_encode(array('success' => false, 'error' => 'Pedido no encontrado para la factura.')));
            }
            $idShop = (int)$order->id_shop;

            $apiToken = Configuration::get('VERIFACTU_API_TOKEN', null, null, $idShop);
            if (!$apiToken) {
                die(json_encode(array('success' => false, 'error' => 'No hay token de API configurado.')));
            }

            // Generate via API — .xsig stored on API side
            $service = new VerifactuFacturaeService($apiToken, $idShop);
            $result  = $service->generateFromInvoice($idOrderInvoice);

            if (!$result['success']) {
                die(json_encode(array('success' => false, 'error' => $result['error'])));
            }

            // Buyer info for the local record
            $customer  = new Customer((int)$order->id_customer);
            $address   = new Address((int)$order->id_address_invoice);
            $buyerNif  = trim($address->vat_number);
            $buyerNif  = ($buyerNif !== '') ? $buyerNif : 'ND';
            $buyerName = trim($address->company);
            if ($buyerName === '') {
                $buyerName = trim($customer->firstname . ' ' . $customer->lastname);
            }

            $idLang = (int)Configuration::get('PS_LANG_DEFAULT');

            // Upsert: update if already generated, insert if new
            $db         = Db::getInstance();
            $existingId = (int)$db->getValue(
                'SELECT id FROM `' . _DB_PREFIX_ . 'verifactu_facturae`
                 WHERE id_order_invoice = ' . $idOrderInvoice . '
                 AND id_shop = ' . $idShop
            );

            $rowData = array(
                'id_facturae_api' => $result['id_facturae'],
                'invoice_number'  => pSQL($invoice->getInvoiceNumberFormatted($idLang)),
                'buyer_nif'       => pSQL($buyerNif),
                'buyer_name'      => pSQL($buyerName),
                'total_amount'    => (float)$invoice->total_paid_tax_incl,
                'issue_date'      => date('Y-m-d', strtotime($invoice->date_add)),
                'face_sent'       => 0,
                'face_estado'     => 'pendiente',
            );

            if ($existingId) {
                $db->update('verifactu_facturae', $rowData, 'id = ' . $existingId);
                $dbId = $existingId;
            } else {
                $rowData['id_order_invoice'] = $idOrderInvoice;
                $rowData['id_shop']          = $idShop;
                $rowData['date_add']         = date('Y-m-d H:i:s');
                $db->insert('verifactu_facturae', $rowData);
                $dbId = (int)$db->Insert_ID();
            }

            die(json_encode(array(
                'success'        => true,
                'id'             => $dbId,
                'id_facturae'    => $result['id_facturae'],
                'id_invoice'     => $result['id_invoice'],
                'invoice_number' => $result['invoice_number'],
                'filename_xsig'  => $result['filename_xsig'],
                'filename_xml'   => $result['filename_xml'],
            )));

        } catch (\Throwable $e) {
            die(json_encode(array(
                'success' => false,
                'error'   => 'Error PHP: ' . $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine(),
            )));
        }
    }

    /**
     * Generates a Facturae for a given OrderSlip (credit note) and records it in DB.
     *
     * POST params:
     *   id_order_slip  int  PrestaShop OrderSlip ID
     */
    public function displayAjaxGenerarFacturaeSlip()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $idOrderSlip = (int)Tools::getValue('id_order_slip');

            if (!$idOrderSlip) {
                die(json_encode(array('success' => false, 'error' => 'Parámetro id_order_slip no válido.')));
            }

            $slip = new OrderSlip($idOrderSlip);
            if (!Validate::isLoadedObject($slip)) {
                die(json_encode(array('success' => false, 'error' => 'Abono no encontrado (id=' . $idOrderSlip . ').')));
            }

            $order = new Order((int)$slip->id_order);
            if (!Validate::isLoadedObject($order)) {
                die(json_encode(array('success' => false, 'error' => 'Pedido no encontrado para el abono.')));
            }
            $idShop = (int)$order->id_shop;

            $apiToken = Configuration::get('VERIFACTU_API_TOKEN', null, null, $idShop);
            if (!$apiToken) {
                die(json_encode(array('success' => false, 'error' => 'No hay token de API configurado.')));
            }

            $service = new VerifactuFacturaeService($apiToken, $idShop);
            $result  = $service->generateFromOrderSlip($idOrderSlip);

            if (!$result['success']) {
                die(json_encode(array('success' => false, 'error' => $result['error'])));
            }

            // Buyer info
            $customer  = new Customer((int)$order->id_customer);
            $address   = new Address((int)$order->id_address_invoice);
            $buyerNif  = trim($address->vat_number);
            $buyerNif  = ($buyerNif !== '') ? $buyerNif : 'ND';
            $buyerName = trim($address->company);
            if ($buyerName === '') {
                $buyerName = trim($customer->firstname . ' ' . $customer->lastname);
            }

            // Build the same invoice_number used inside the service
            $slipPrefix = Configuration::get('PS_CREDIT_SLIP_PREFIX', null, null, $idShop);
            $slipPrefix = trim(str_replace('{year}', date('Y', strtotime($slip->date_add)), $slipPrefix));
            $fullNumber = $slipPrefix . sprintf('%06d', $idOrderSlip);

            // Upsert in verifactu_facturae
            $db         = Db::getInstance();
            $existingId = (int)$db->getValue(
                'SELECT id FROM `' . _DB_PREFIX_ . 'verifactu_facturae`
                 WHERE id_order_slip = ' . $idOrderSlip . '
                 AND id_shop = ' . $idShop
            );

            $rowData = array(
                'id_facturae_api' => $result['id_facturae'],
                'invoice_number'  => pSQL($fullNumber),
                'buyer_nif'       => pSQL($buyerNif),
                'buyer_name'      => pSQL($buyerName),
                'total_amount'    => (float)$slip->total_products_tax_incl,
                'issue_date'      => date('Y-m-d', strtotime($slip->date_add)),
                'face_sent'       => 0,
                'face_estado'     => 'pendiente',
            );

            if ($existingId) {
                $db->update('verifactu_facturae', $rowData, 'id = ' . $existingId);
                $dbId = $existingId;
            } else {
                $rowData['id_order_slip'] = $idOrderSlip;
                $rowData['id_shop']       = $idShop;
                $rowData['date_add']      = date('Y-m-d H:i:s');
                $db->insert('verifactu_facturae', $rowData);
                $dbId = (int)$db->Insert_ID();
            }

            die(json_encode(array(
                'success'        => true,
                'id'             => $dbId,
                'id_facturae'    => $result['id_facturae'],
                'id_invoice'     => $result['id_invoice'],
                'invoice_number' => $result['invoice_number'],
                'filename_xsig'  => $result['filename_xsig'],
                'filename_xml'   => $result['filename_xml'],
            )));

        } catch (\Throwable $e) {
            die(json_encode(array(
                'success' => false,
                'error'   => 'Error PHP: ' . $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine(),
            )));
        }
    }

    /**
     * Sends a Facturae to FACe via the InFoAL API.
     * Updates face_sent, face_estado and face_registro in the local DB.
     *
     * POST params:
     *   id  int  verifactu_facturae.id (DB record)
     */
    public function displayAjaxEnviarFace()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id = (int)Tools::getValue('id');
            if (!$id) {
                die(json_encode(array('success' => false, 'error' => 'Parámetro id no válido.')));
            }

            $idShop = (int)$this->context->shop->id;
            $db     = Db::getInstance();
            $sql    = new DbQuery();
            $sql->select('*')
                ->from('verifactu_facturae')
                ->where('id = ' . $id)
                ->where('id_shop = ' . $idShop);
            $row = $db->getRow($sql);

            if (!$row) {
                die(json_encode(array('success' => false, 'error' => 'Registro de FE no encontrado.')));
            }

            if (empty($row['id_facturae_api'])) {
                die(json_encode(array('success' => false, 'error' => 'Esta factura no tiene id_facturae generado. Genérala primero.')));
            }

            // Immutability check: once sent, cannot be re-sent
            $estado = isset($row['face_estado']) ? $row['face_estado'] : 'pendiente';
            if ($estado !== 'pendiente') {
                die(json_encode(array(
                    'success' => false,
                    'error'   => 'La factura ya fue enviada a FACe (estado: "' . $estado . '"). No puede volver a enviarse.',
                )));
            }

            $apiToken = Configuration::get('VERIFACTU_API_TOKEN', null, null, $idShop);
            if (!$apiToken) {
                die(json_encode(array('success' => false, 'error' => 'Token de API no configurado.')));
            }

            $service = new VerifactuFacturaeService($apiToken, $idShop);
            $result  = $service->sendToFace((int)$row['id_facturae_api']);

            if (!$result['success']) {
                die(json_encode(array('success' => false, 'error' => $result['error'])));
            }

            // Update local DB with FACe result
            $db->update('verifactu_facturae', array(
                'face_sent'     => 1,
                'face_estado'   => pSQL($result['face_estado']   ?? 'enviada'),
                'face_registro' => pSQL($result['face_registro'] ?? ''),
                'face_mensaje'  => pSQL($result['face_mensaje']  ?? ''),
            ), 'id = ' . $id);

            die(json_encode(array(
                'success'       => true,
                'face_estado'   => $result['face_estado'],
                'face_registro' => $result['face_registro'],
                'face_mensaje'  => $result['face_mensaje'],
            )));

        } catch (\Throwable $e) {
            die(json_encode(array(
                'success' => false,
                'error'   => 'Error PHP: ' . $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine(),
            )));
        }
    }

    /**
     * Proxies the .xsig download from the API to the browser (on-demand, no local storage).
     *
     * GET params:
     *   id  int  verifactu_facturae.id (DB record)
     */
    public function displayAjaxDescargarFacturae()
    {
        $id = (int)Tools::getValue('id');

        if (!$id) {
            header('HTTP/1.1 400 Bad Request');
            die('Parámetro id no válido.');
        }

        $idShop = (int)$this->context->shop->id;
        $db     = Db::getInstance();
        $sql    = new DbQuery();
        $sql->select('*')
            ->from('verifactu_facturae')
            ->where('id = ' . $id)
            ->where('id_shop = ' . $idShop);
        $row = $db->getRow($sql);

        if (!$row || empty($row['id_facturae_api'])) {
            header('HTTP/1.1 404 Not Found');
            die('Factura electrónica no encontrada.');
        }

        $apiToken = Configuration::get('VERIFACTU_API_TOKEN', null, null, $idShop);
        if (!$apiToken) {
            header('HTTP/1.1 503 Service Unavailable');
            die('Token de API no configurado.');
        }

        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['invoice_number']) . '.xsig';
        $service  = new VerifactuFacturaeService($apiToken, $idShop);

        $error = $service->downloadXsig((int)$row['id_facturae_api'], $filename);

        if (is_array($error)) {
            header('HTTP/1.1 502 Bad Gateway');
            die('Error al descargar el .xsig de la API: ' . $error['error']);
        }
    }

    /**
     * Proxies the unsigned .xml download from the API to the browser.
     *
     * GET params:
     *   id  int  verifactu_facturae.id (DB record)
     */
    public function displayAjaxDescargarFacturaeXml()
    {
        $id = (int)Tools::getValue('id');

        if (!$id) {
            header('HTTP/1.1 400 Bad Request');
            die('Parámetro id no válido.');
        }

        $idShop = (int)$this->context->shop->id;
        $db     = Db::getInstance();
        $sql    = new DbQuery();
        $sql->select('*')
            ->from('verifactu_facturae')
            ->where('id = ' . $id)
            ->where('id_shop = ' . $idShop);
        $row = $db->getRow($sql);

        if (!$row || empty($row['id_facturae_api'])) {
            header('HTTP/1.1 404 Not Found');
            die('Factura electrónica no encontrada.');
        }

        $apiToken = Configuration::get('VERIFACTU_API_TOKEN', null, null, $idShop);
        if (!$apiToken) {
            header('HTTP/1.1 503 Service Unavailable');
            die('Token de API no configurado.');
        }

        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['invoice_number']) . '.xml';
        $service  = new VerifactuFacturaeService($apiToken, $idShop);

        $error = $service->downloadXml((int)$row['id_facturae_api'], $filename);

        if (is_array($error)) {
            header('HTTP/1.1 502 Bad Gateway');
            die('Error al descargar el .xml de la API: ' . $error['error']);
        }
    }


    protected function ajaxDie($value = null, $controller = null, $method = null)
    {
        header('Content-Type: application/json');
        parent::ajaxDie($value, $controller, $method);
    }
}