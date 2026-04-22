<?php
/**
 * InFoAL S.L.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to a Commercial License (EULA)
 * that is bundled with this package in the file LICENSE.txt.
 *
 * @author    InFoAL S.L. <hosting@infoal.com>
 * @copyright 2025 InFoAL S.L.
 * @license   Proprietary - All Rights Reserved
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * VerifactuFacturaeService
 *
 * Manages electronic invoices (Facturae 3.2.2) via the InFoAL API.
 *
 * Architecture:
 *  - The .xsig is generated, signed and stored on the API side.
 *  - PrestaShop only stores the id_facturae returned by the API.
 *  - The seller (emisor) is resolved by the API from the Bearer token.
 *
 * Endpoints (all POST with JSON body):
 *  POST /api_v2/facturae/alta          → Unified: generate {base}.xml + sign {base}.xsig + save history
 *                                         Returns: { id_facturae, id_invoice, filename_xsig, filename_xml }
 *  POST /api_v2/facturae/download      → Stream .xsig FIRMADO    body: { file_path: 'base' } | legacy { id_facturae: N }
 *  POST /api_v2/facturae/download_xml  → Stream .xml SIN FIRMAR   body: { file_path: 'base' } | legacy { id_facturae: N }
 *  POST /api_v2/facturae/send_face     → Submit to FACe           body: { id_facturae: N }
 *  POST /api_v2/facturae/face_status   → Query FACe               body: { id_facturae: N }
 */
class VerifactuFacturaeService
{
    const API_BASE = 'https://verifactu.infoal.io/api_v2/facturae';

    /** @var string Bearer token */
    private $apiToken;

    /** @var int PrestaShop shop ID */
    private $idShop;

    /**
     * @param string $apiToken  Bearer token
     * @param int    $idShop    Shop ID
     */
    public function __construct($apiToken, $idShop)
    {
        $this->apiToken = $apiToken;
        $this->idShop   = (int)$idShop;
    }

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Generates a Facturae for a given OrderInvoice via the unified ALTA endpoint.
     * The API generates {base}.xml (unsigned) and {base}.xsig (XAdES signed) in one call.
     *
     * @param  int $idOrderInvoice
     * @return array {
     *   'success'        => bool,
     *   'id_facturae'    => int|null,     API-side ID
     *   'id_invoice'     => int|null,     Invoice history ID
     *   'filename_xsig'  => string|null,  Signed .xsig filename
     *   'filename_xml'   => string|null,  Unsigned .xml filename
     *   'invoice_number' => string|null,
     *   'error'          => string|null,
     * }
     */
    public function generateFromInvoice($idOrderInvoice)
    {
        $idOrderInvoice = (int)$idOrderInvoice;

        $invoice = new OrderInvoice($idOrderInvoice);
        if (!Validate::isLoadedObject($invoice)) {
            return $this->error('No se pudo cargar la factura (id=' . $idOrderInvoice . ').');
        }

        $order = new Order((int)$invoice->id_order);
        if (!Validate::isLoadedObject($order)) {
            return $this->error('No se pudo cargar el pedido asociado a la factura.');
        }

        $customer = new Customer((int)$order->id_customer);
        $address  = new Address((int)$order->id_address_invoice);

        $lines = $this->buildLines((int)$order->id, $idOrderInvoice);
        if (empty($lines)) {
            return $this->error('La factura no tiene líneas de detalle exportables.');
        }

        // Build payload — seller NOT included (resolved by API from Bearer token)
        $payload = array(
            'buyer'   => $this->buildBuyer($customer, $address),
            'invoice' => $this->buildInvoiceBlock($invoice, $lines),
        );

        return $this->callAlta($payload);
    }

    /**
     * Generates a Facturae for a given OrderSlip (credit note) via the unified ALTA endpoint.
     *
     * @param  int $idOrderSlip
     * @return array { 'success', 'id_facturae', 'id_invoice', 'filename_xsig', 'filename_xml', 'invoice_number', 'error' }
     */
    public function generateFromOrderSlip($idOrderSlip)
    {
        $idOrderSlip = (int)$idOrderSlip;

        $slip = new OrderSlip($idOrderSlip);
        if (!Validate::isLoadedObject($slip)) {
            return $this->error('No se pudo cargar el abono (id=' . $idOrderSlip . ').');
        }

        $order = new Order((int)$slip->id_order);
        if (!Validate::isLoadedObject($order)) {
            return $this->error('No se pudo cargar el pedido asociado al abono.');
        }

        $customer = new Customer((int)$order->id_customer);
        $address  = new Address((int)$order->id_address_invoice);

        // Build lines from order_slip_detail
        $lines = $this->buildLinesFromSlip($idOrderSlip, (int)$order->id);
        if (empty($lines)) {
            return $this->error('El abono no tiene líneas de detalle exportables.');
        }

        // Build invoice number for the slip
        $slipPrefix = Configuration::get('PS_CREDIT_SLIP_PREFIX', null, null, $this->idShop);
        $slipPrefix = trim(str_replace('{year}', date('Y', strtotime($slip->date_add)), $slipPrefix));
        $slipNumber = sprintf('%06d', (int)$slip->id);  // $slip->id is the ObjectModel PK
        $fullNumber = $slipPrefix . $slipNumber;

        $payload = array(
            'buyer'   => $this->buildBuyer($customer, $address),
            'invoice' => array(
                'InvoiceNumber' => $fullNumber,
                'InvoiceSeries' => $slipPrefix,
                'IssueDate'     => date('Y-m-d', strtotime($slip->date_add)),
                'InvoiceClass'  => 'RC',   // Rectificativa
                'lines'         => $lines,
            ),
        );

        return $this->callAlta($payload);
    }

    /**
     * Builds lines from order_slip_detail records.
     */
    private function buildLinesFromSlip($idOrderSlip, $idOrder)
    {
        $db  = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('osd.product_quantity, od.product_name, od.unit_price_tax_excl, od.tax_rate')
            ->from('order_slip_detail', 'osd')
            ->leftJoin('order_detail', 'od', 'osd.id_order_detail = od.id_order_detail')
            ->where('osd.id_order_slip = ' . $idOrderSlip);

        $details = $db->executeS($sql);
        if (!$details) {
            return array();
        }

        $lines = array();
        foreach ($details as $row) {
            if ((float)$row['product_quantity'] == 0) {
                continue;
            }
            $lines[] = array(
                'ItemDescription'     => $row['product_name'],
                'Quantity'            => (float)$row['product_quantity'],
                'UnitPriceWithoutTax' => round((float)$row['unit_price_tax_excl'], 6),
                'TaxRate'             => (float)$row['tax_rate'],
                'TaxTypeCode'         => '01',  // IVA
            );
        }

        return $lines;
    }

    /**
     * Downloads the signed .xsig from the API and streams it to the browser.
     * POST /api_v2/facturae/download  body: { "id_facturae": N }
     *
     * @param  int    $idFacturae
     * @param  string $filename
     * @return array|void  Returns error array on failure, otherwise exits
     */
    public function downloadXsig($idFacturae, $filename = 'factura.xsig')
    {
        $url  = self::API_BASE . '/download';
        $data = $this->curlPost($url, array('id_facturae' => (int)$idFacturae));

        if ($data['error']) {
            return $this->error($data['error']);
        }

        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . addslashes(basename($filename)) . '"');
        header('Content-Length: ' . strlen($data['body']));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $data['body'];
        exit;
    }

    /**
     * Downloads the unsigned .xml from the API and streams it to the browser.
     * POST /api_v2/facturae/download_xml  body: { "id_facturae": N }
     *
     * @param  int    $idFacturae
     * @param  string $filename
     * @return array|void  Returns error array on failure, otherwise exits
     */
    public function downloadXml($idFacturae, $filename = 'factura.xml')
    {
        $url  = self::API_BASE . '/download_xml';
        $data = $this->curlPost($url, array('id_facturae' => (int)$idFacturae));

        if ($data['error']) {
            return $this->error($data['error']);
        }

        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . addslashes(basename($filename)) . '"');
        header('Content-Length: ' . strlen($data['body']));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $data['body'];
        exit;
    }

    /**
     * Sends an already-generated Facturae to FACe.
     * POST /api_v2/facturae/send_face  body: { "id_facturae": N }
     *
     * @param  int $idFacturae
     * @return array { 'success', 'face_registro', 'face_estado', 'face_mensaje', 'error' }
     */
    public function sendToFace($idFacturae)
    {
        $url      = self::API_BASE . '/send_face';
        $response = $this->curlPost($url, array('id_facturae' => (int)$idFacturae));

        if ($response['error']) {
            return $this->error($response['error']);
        }

        $decoded = json_decode($response['body'], true);
        if (!$decoded) {
            return $this->error('Respuesta inválida de la API al enviar a FACe.');
        }

        if (empty($decoded['success'])) {
            $msg = isset($decoded['error']) ? $decoded['error'] : 'Error desconocido al enviar a FACe.';
            return $this->error($msg);
        }

        return array(
            'success'       => true,
            'face_registro' => isset($decoded['face_registro']) ? $decoded['face_registro'] : null,
            'face_estado'   => isset($decoded['face_estado'])   ? $decoded['face_estado']   : 'enviada',
            'face_mensaje'  => isset($decoded['face_mensaje'])  ? $decoded['face_mensaje']  : null,
            'error'         => null,
        );
    }

    /**
     * Queries the FACe status of a Facturae.
     * POST /api_v2/facturae/face_status  body: { "id_facturae": N }
     *
     * @param  int $idFacturae
     * @return array { 'success', 'face_estado', 'face_registro', 'error' }
     */
    public function getFaceStatus($idFacturae)
    {
        $url      = self::API_BASE . '/face_status';
        $response = $this->curlPost($url, array('id_facturae' => (int)$idFacturae));

        if ($response['error']) {
            return $this->error($response['error']);
        }

        $decoded = json_decode($response['body'], true);
        if (!$decoded) {
            return $this->error('Respuesta inválida de la API al consultar estado FACe.');
        }

        return array(
            'success'        => true,
            'face_estado'    => isset($decoded['face_estado'])   ? $decoded['face_estado']   : 'desconocido',
            'face_registro'  => isset($decoded['face_registro']) ? $decoded['face_registro'] : null,
            'invoice_number' => isset($decoded['invoice_number'])? $decoded['invoice_number']: null,
            'error'          => null,
        );
    }

    // =========================================================================
    // Payload builders
    // =========================================================================

    /**
     * Builds the buyer block.
     * Field names match the API's validateGeneratePayload() expected keys.
     */
    private function buildBuyer(Customer $customer, Address $address)
    {
        // NIF/VAT — prefer address.vat_number, fallback to 'ND' for B2C
        $vat = trim($address->vat_number);
        if ($vat === '') {
            $vat = 'ND';
        }

        // Name — B2B uses company name, B2C uses customer full name
        $company = trim($address->company);
        if ($company !== '') {
            // Legal entity — use CorporateName
            $nameKey   = 'CorporateName';
            $nameValue = $company;
        } else {
            // Individual — use Name
            $nameKey   = 'Name';
            $nameValue = trim($customer->firstname . ' ' . $customer->lastname);
        }

        // Province from state
        $province = '';
        if ((int)$address->id_state > 0) {
            $state    = new State((int)$address->id_state);
            $province = $state->name;
        }

        // CountryCode: send alpha-2, the API converts internally to alpha-3
        $country     = new Country((int)$address->id_country);
        $countryIso2 = Tools::strtoupper($country->iso_code);

        return array(
            'TaxIdentificationNumber' => $vat,
            $nameKey                  => $nameValue,
            'Address'                 => $address->address1,
            'PostCode'                => $address->postcode,
            'Town'                    => $address->city,
            'Province'                => $province,
            'CountryCode'             => $countryIso2,  // alpha-2 — API converts to alpha-3
        );
    }

    /**
     * Builds the invoice block including lines (lines live inside invoice in this API).
     * Field names match the API's validateGeneratePayload() and buildFacturaeObject() expected keys.
     */
    private function buildInvoiceBlock(OrderInvoice $invoice, array $lines)
    {
        $idLang     = (int)Configuration::get('PS_LANG_DEFAULT');
        $fullNumber = $invoice->getInvoiceNumberFormatted($idLang);

        $prefix = Configuration::get('PS_INVOICE_PREFIX', null, null, $this->idShop);
        $prefix = trim(str_replace('{year}', date('Y', strtotime($invoice->date_add)), $prefix));

        $serie  = $prefix;
        $number = $fullNumber;
        if ($prefix !== '' && strpos($fullNumber, $prefix) === 0) {
            $number = substr($fullNumber, strlen($prefix));
        }

        return array(
            'InvoiceNumber' => $fullNumber,       // full formatted number used as invoice number
            'InvoiceSeries' => $serie,
            'IssueDate'     => date('Y-m-d', strtotime($invoice->date_add)),
            'lines'         => $lines,             // lines are nested inside invoice block
        );
    }

    /**
     * Builds the lines array from OrderDetail records.
     * Field names match the API's buildFacturaeObject() expected keys.
     */
    private function buildLines($idOrder, $idOrderInvoice)
    {
        $igicTaxIds = json_decode(Configuration::get('VERIFACTU_IGIC_TAXES', null, null, $this->idShop), true);
        $ipsiTaxIds = json_decode(Configuration::get('VERIFACTU_IPSI_TAXES', null, null, $this->idShop), true);
        $igicTaxIds = is_array($igicTaxIds) ? $igicTaxIds : array();
        $ipsiTaxIds = is_array($ipsiTaxIds) ? $ipsiTaxIds : array();
        $territorioEspecial = (int)Configuration::get('VERIFACTU_TERRITORIO_ESPECIAL', null, null, $this->idShop);

        $db  = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('od.id_order_detail, od.product_name, od.product_quantity, od.unit_price_tax_excl, od.tax_rate')
            ->from('order_detail', 'od')
            ->where('od.id_order = ' . (int)$idOrder);

        $details = $db->executeS($sql);
        if (!$details) {
            return array();
        }

        $lines = array();
        foreach ($details as $row) {
            $taxTypeCode   = '01';  // 01=IVA (default)
            $taxRate       = (float)$row['tax_rate'];
            $idOrderDetail = (int)$row['id_order_detail'];

            // Detect IGIC / IPSI from order_detail_tax
            $taxSql = new DbQuery();
            $taxSql->select('id_tax')
                   ->from('order_detail_tax')
                   ->where('id_order_detail = ' . $idOrderDetail);
            $detailTaxes = $db->executeS($taxSql);

            $legacyIdTax = 0;
            if ($detailTaxes) {
                foreach ($detailTaxes as $dt) {
                    $idTax = (int)$dt['id_tax'];
                    if ($legacyIdTax === 0) {
                        $legacyIdTax = $idTax;
                    }
                    if ($territorioEspecial === 1 && in_array($idTax, $igicTaxIds)) {
                        $taxTypeCode = '03';  // IGIC
                        $legacyIdTax = $idTax;
                        break;
                    } elseif ($territorioEspecial === 1 && in_array($idTax, $ipsiTaxIds)) {
                        $taxTypeCode = '02';  // IPSI
                        $legacyIdTax = $idTax;
                    }
                }
            }

            // Fallback: get tax rate from tax table if not stored in order_detail
            if ($taxRate == 0 && $legacyIdTax > 0) {
                $rateSql = new DbQuery();
                $rateSql->select('rate')->from('tax')->where('id_tax = ' . $legacyIdTax);
                $rateFromTable = (float)$db->getValue($rateSql);
                if ($rateFromTable > 0) {
                    $taxRate = $rateFromTable;
                }
            }

            // Field names match API's buildFacturaeObject() expected keys
            $lines[] = array(
                'ItemDescription'     => $row['product_name'],
                'Quantity'            => (float)$row['product_quantity'],
                'UnitPriceWithoutTax' => round((float)$row['unit_price_tax_excl'], 6),
                'TaxRate'             => $taxRate,
                'TaxTypeCode'         => $taxTypeCode,
            );
        }

        return $lines;
    }

    // =========================================================================
    // HTTP helpers
    // =========================================================================

    /**
     * POST /api_v2/facturae/alta — unified endpoint.
     * Generates {base}.xml (Facturae 3.2.2 unsigned) and {base}.xsig (XAdES signed)
     * in a single call, saves to invoice history, and registers the alta.
     *
     * Expected JSON response:
     * {
     *   "success": true,
     *   "id_facturae": 12345,
     *   "id_invoice": 67890,
     *   "filename_xsig": "FAC_2025_001.xsig",
     *   "filename_xml":  "FAC_2025_001.xml"
     * }
     */
    private function callAlta(array $payload)
    {
        $response = $this->curlPost(self::API_BASE . '/alta', $payload);

        if ($response['error']) {
            return $this->error($response['error']);
        }

        $decoded = json_decode($response['body'], true);

        if (!$decoded) {
            return $this->error('La API devolvió una respuesta no válida (esperado JSON). HTTP ' . $response['http_code']);
        }

        if (empty($decoded['success'])) {
            // Idempotency: already exists (409) — treat as success if id_facturae is present
            if ($response['http_code'] === 409 && !empty($decoded['id_facturae'])) {
                return array(
                    'success'        => true,
                    'id_facturae'    => (int)$decoded['id_facturae'],
                    'id_invoice'     => isset($decoded['id_invoice'])     ? (int)$decoded['id_invoice']   : null,
                    'filename_xsig'  => isset($decoded['filename_xsig'])  ? $decoded['filename_xsig']     : null,
                    'filename_xml'   => isset($decoded['filename_xml'])   ? $decoded['filename_xml']       : null,
                    'invoice_number' => isset($decoded['invoice_number']) ? $decoded['invoice_number']     : null,
                    'error'          => null,
                );
            }
            $msg = isset($decoded['error']) ? $decoded['error'] : ('HTTP ' . $response['http_code']);
            return $this->error($msg);
        }

        return array(
            'success'        => true,
            'id_facturae'    => isset($decoded['id_facturae'])   ? (int)$decoded['id_facturae']   : null,
            'id_invoice'     => isset($decoded['id_invoice'])    ? (int)$decoded['id_invoice']    : null,
            'filename_xsig'  => isset($decoded['filename_xsig']) ? $decoded['filename_xsig']      : null,
            'filename_xml'   => isset($decoded['filename_xml'])  ? $decoded['filename_xml']       : null,
            'invoice_number' => isset($decoded['invoice_number'])? $decoded['invoice_number']     : null,
            'error'          => null,
        );
    }

    /**
     * Generic authenticated POST with JSON body.
     *
     * @return array { 'body' => string, 'http_code' => int, 'error' => string|null }
     */
    private function curlPost($url, array $data)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => array(
                'Authorization: Bearer ' . $this->apiToken,
                'Content-Type: application/json',
                'Accept: application/json, application/xml, application/zip',
            ),
        ));
        $body      = curl_exec($curl);
        $httpCode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($body === false) {
            return array('body' => null, 'http_code' => 0, 'error' => 'Error cURL: ' . $curlError);
        }

        // On HTTP errors, try to extract API error message from JSON body
        if ($httpCode >= 400) {
            $decoded = json_decode($body, true);
            $msg = isset($decoded['error']) ? $decoded['error'] : ('HTTP ' . $httpCode);
            return array('body' => $body, 'http_code' => $httpCode, 'error' => $msg);
        }

        return array('body' => $body, 'http_code' => $httpCode, 'error' => null);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function error($message)
    {
        return array(
            'success'        => false,
            'id_facturae'    => null,
            'id_invoice'     => null,
            'filename_xsig'  => null,
            'filename_xml'   => null,
            'invoice_number' => null,
            'error'          => $message,
        );
    }
}
