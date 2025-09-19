<?php
/**
* InFoAL S.L.
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to hosting@infoal.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    InFoAL S.L. <hosting@infoal.com>
*  @copyright InFoAL S.L.
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of InFoAL S.L.
*/

namespace Verifactu\VerifactuClasses;

use Db;
use DbQuery;
use Configuration;
use PrestaShopLogger;

class ApiVerifactu
{
    /** @var string|null El token de la API específico de la tienda. */
    private $apiToken;

    /** @var bool Indica si el modo debug está activo para la tienda. */
    private $debugMode;

    /** @var int El ID de la tienda para la que se está trabajando. */
    private $id_shop;

    public function __construct($apiToken, $debugMode, $id_shop)
    {
        $this->apiToken = $apiToken;
        $this->debugMode = (bool)$debugMode;
        $this->id_shop = (int)$id_shop;
    }

    public function checkDNI($id_order)
    {
        $sql = new DbQuery();
        $sql->select('*')->from('orders')->where('id_order = ' . (int)$id_order);
        $order = Db::getInstance()->getRow($sql);

        $sql = new DbQuery();
        $sql->select('*')->from('address')->where('id_address = ' . (int)$order['id_address_invoice']);
        $address = Db::getInstance()->getRow($sql);

        $curl  = curl_init();
        $url   = 'https://verifactu.infoal.io/api_v2/cdi/check';
        $token = $this->apiToken;

        // HTTP request headers
        $headers = [
            'Authorization: Bearer '.$token,
            'Content-Type: application/json',
        ];

        $data = new \stdClass();
        $taxIdentificationNumber = !empty($address['vat_number']) ? $address['vat_number'] : $address['dni'];
        $data->dni = $taxIdentificationNumber;
        $data->nombre = $address['firstname'].' '.$address['lastname'];
        $dataString = json_encode($data);

        if ($this->debugMode)
        {
            PrestaShopLogger::addLog(
                'Módulo Verifactu: CDI - Envío a api ' . $dataString.'
                ',
                1, null, null, null, true, $this->id_shop
            );
        }

        curl_setopt_array($curl, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => 'utf-8',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2TLS,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => $dataString,
                CURLOPT_HTTPHEADER     => $headers,
            ]
        );

        $response = curl_exec($curl);
        curl_close($curl);

        if ($this->debugMode)
        {
            PrestaShopLogger::addLog(
                'Módulo Verifactu: CDI - Respuesta api ' . $response.'
                ',
                1, null, null, null, true, $this->id_shop
            );
        }

        $obj = json_decode($response);

        return $response;
    }

    public function sendAltaVerifactu($id_order,$tipo='alta')
    {
        $reply = array();

        $sql = new DbQuery();
        $sql->select('*')->from('orders')->where('id_order = ' . (int)$id_order);
        $order_data = Db::getInstance()->getRow($sql);

        if (!$order_data) 
        {
            if ($this->debugMode) {
                PrestaShopLogger::addLog('Módulo Verifactu: No se encontraron datos para el pedido ID ' . $id_order, 2, null, null, null, true, $this->id_shop);
            }
            return json_encode(['response' => 'KO', 'error' => 'Pedido no encontrado.']);
        }

        if ($tipo == 'abono')
        {
            $sql = new DbQuery();
            $sql->select('os.*, vos.verifactuEstadoRegistro, vos.estado')
                ->from('order_slip', 'os')
                ->leftJoin('verifactu_order_slip', 'vos', 'os.id_order_slip = vos.id_order_slip')
                ->where('os.id_order = ' . (int)$id_order)
                ->orderBy('os.id_order_slip DESC');
            $slip = Db::getInstance()->getRow($sql);

            $sql = new DbQuery();
            $sql->select('sd.*, od.product_reference, od.tax_rate, od.product_name')
                ->from('order_slip_detail', 'sd')
                ->leftJoin('order_detail', 'od', 'sd.id_order_detail = od.id_order_detail')
                ->where('sd.id_order_slip = ' . (int)$slip['id_order_slip']);
            $slipLines = Db::getInstance()->executeS($sql);
                
            if ($this->debugMode)
            {
                PrestaShopLogger::addLog(
                    'Módulo Verifactu: <br>
                    Factura de abono: '.json_encode($slip).'<br>
                    Lineas: '.json_encode($slipLines).'<br>
                    ',
                    1, null, null, null, true, $this->id_shop
                );
            }

            if (isset($slip['estado']) && $slip['estado'] == 'pendiente') //Si el estado es pendiente evitamos que se vuelva a enviar.
            {
                $reply['response'] = 'pendiente';
                return json_encode($reply);
            }
                
        }

        $sql = new DbQuery();
        $sql->select('oi.*, voi.verifactuEstadoRegistro, voi.estado')
            ->from('order_invoice', 'oi')
            ->leftJoin('verifactu_order_invoice', 'voi', 'oi.id_order_invoice = voi.id_order_invoice')
            ->where('oi.id_order = ' . (int)$id_order)
            ->orderBy('oi.id_order_invoice DESC');
        $invoice = Db::getInstance()->getRow($sql);

        if (isset($invoice['estado']) && $invoice['estado'] == 'pendiente') //Si el estado es pendiente evitamos que se vuelva a enviar.
        {
            $reply['response'] = 'pendiente';
            return json_encode($reply);
        }

        $sql = new DbQuery();
        $sql->select('*')->from('address')->where('id_address = ' . (int)$order_data['id_address_invoice']);
        $address = Db::getInstance()->getRow($sql);

        if (!$address) {
            if ($this->debugMode) {
                PrestaShopLogger::addLog('Módulo Verifactu: No se encontró dirección de facturación para el pedido ID ' . $id_order, 2, null, null, null, true, $this->id_shop);
            }
            return json_encode(['response' => 'KO', 'error' => 'Dirección de facturación no encontrada.']);
        }

        $sql = new DbQuery();
        $sql->select('*')->from('state')->where('id_state = ' . (int)$address['id_state']);
        $prov = Db::getInstance()->getRow($sql);

        if (!$prov) {
            if ($this->debugMode) {
                PrestaShopLogger::addLog('Módulo Verifactu: No se encontraron provincias para el pedido ID ' . $id_order, 2, null, null, null, true, $this->id_shop);
            }
            //return json_encode(['response' => 'KO', 'error' => 'Provincia no encontrada.']);
        }

        $sql = new DbQuery();
        $sql->select('*')->from('country')->where('id_country = ' . (int)$address['id_country']);
        $pais = Db::getInstance()->getRow($sql);

        if (!$pais) {
            if ($this->debugMode) {
                PrestaShopLogger::addLog('Módulo Verifactu: No se encontraron paises para el pedido ID ' . $id_order, 2, null, null, null, true, $this->id_shop);
            }
            //return json_encode(['response' => 'KO', 'error' => 'País no encontrado.']);
        }

        $sql = new DbQuery();
        $sql->select('*')->from('currency')->where('id_currency = ' . (int)$order_data['id_currency']);
        $currency = Db::getInstance()->getRow($sql);

        if (!$currency) {
            if ($this->debugMode) {
                PrestaShopLogger::addLog('Módulo Verifactu: No se encontraron monedas para el pedido ID ' . $id_order, 2, null, null, null, true, $this->id_shop);
            }
            //return json_encode(['response' => 'KO', 'error' => 'Moneda no encontrada.']);
        }

        $sql = new DbQuery();
        $sql->select('*')->from('order_detail')->where('id_order = ' . (int)$id_order);
        $lines = Db::getInstance()->executeS($sql);

        if (!$lines) {
            if ($this->debugMode) {
                PrestaShopLogger::addLog('Módulo Verifactu: No se encontraron lineas de pedido para el pedido ID ' . $id_order, 2, null, null, null, true, $this->id_shop);
            }
            //return json_encode(['response' => 'KO', 'error' => 'Lineas de factura no encontradas.']);
        }

        $curl  = curl_init();

        $url   = 'https://verifactu.infoal.io/api_v2/verifactu/alta';
        $token = $this->apiToken;

        // HTTP request headers
        $headers = [
            'Authorization: Bearer '.$token,
            'Content-Type: application/json',
        ];

        

        $data = new \stdClass();
        $buyer = new \stdClass();
        $inv = new \stdClass();
        
        // Comprueba si 'vat_number' tiene contenido. Si lo tiene, lo usamos. Si no, usamos 'dni'.
        $taxIdentificationNumber = !empty($address['vat_number']) ? $address['vat_number'] : $address['dni'];
        $buyer->TaxIdentificationNumber = $taxIdentificationNumber;
        $buyer->CorporateName = (isset($address['company']) && $address['company'] != ''?$address['company']:'');
        $buyer->Name = $address['firstname'].' '.$address['lastname'];
        $buyer->Address = (isset($address['address1']) && $address['address1'] != ''?$address['address1']:'');
        $buyer->PostCode = (isset($address['postcode']) && $address['postcode'] != ''?$address['postcode']:'');
        $buyer->Town = (isset($address['city']) && $address['city'] != ''?$address['city']:'');
        $buyer->Province = (isset($prov['name']) && $prov['name'] != ''?$prov['name']:'');
        $buyer->CountryCode = (isset($pais['iso_code']) && $pais['iso_code'] != ''?$pais['iso_code']:'ES');

        $data->buyer = $buyer;

        //$order = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_order = "'.$id_order.'"');
        if ($tipo == 'abono')
        {
            $InvoiceNumber = $this->getFormattedCreditSlipNumber($slip['id_order_slip']);
            $totalTaxExcl = ((float) $slip['total_products_tax_excl'] + (float) $slip['total_shipping_tax_excl']);
            $totalTaxIncl = ((float) $slip['total_products_tax_incl'] + (float) $slip['total_shipping_tax_incl']);
            $inv->InvoiceNumber = $InvoiceNumber;
            $inv->InvoiceDocumentType = ($taxIdentificationNumber != ''?"FC":"FA");
            $inv->InvoiceClass = "OR"; //Factura rectificativa
            $inv->IssueDate = date('Y-m-d', strtotime($slip['date_add']));
            $inv->InvoiceCurrencyCode = $currency['iso_code'];
            $inv->TaxCurrencyCode = $currency['iso_code'];
            $inv->LanguageName = 'es';
            $inv->TotalGrossAmount = -$slip['total_products_tax_excl'];
            $inv->TotalGeneralDiscounts = 0;
            $inv->TotalGeneralSurcharges = 0;
            $inv->TotalGrossAmountBeforeTaxes = -((float) $totalTaxExcl);
            $inv->TotalTaxOutputs = -((float) $totalTaxIncl - (float) $totalTaxExcl );
            $inv->TotalTaxesWithheld = -((float) $totalTaxIncl - (float) $totalTaxExcl );
            $inv->InvoiceTotal = -((float) $totalTaxIncl);
            $inv->TotalOutstandingAmount = -((float) $totalTaxIncl);
            $inv->TotalExecutableAmount = -((float) $totalTaxIncl);

            $inv->CorrectiveCorrectionMethod  = "01";
            $inv->CorrectiveCorrectionMethodDescription  = "Factura de abono ".$slip['id_order_slip'];
            $inv->CorrectiveInvoiceNumber = $this->getFormattedInvoiceNumber($invoice['id_order_invoice']); 
            $inv->CorrectiveIssueDate = date('Y-m-d', strtotime($invoice['date_add']));
            //$inv->CorrectiveBaseAmount  = $inv->TotalGrossAmount;
            //$inv->CorrectiveTaxAmount  = $inv->TotalTaxOutputs;

            $data->invoice = $inv;

            $seq = 1;
            foreach ($slipLines as $l)
            {
                
                $line = new \stdClass();
                $line->SequenceNumber = $seq;
                $line->ItemDescription = $l['product_name'];
                $line->Quantity = ((float) $l['product_quantity']);
                $line->UnitPriceWithoutTax = -((float) $l['unit_price_tax_excl']);
                $line->TotalCost = -((float) $l['total_price_tax_incl']);
                $line->GrossAmount = -((float) $l['total_price_tax_incl']);
                $line->TaxTypeCode = '01';
                $line->TaxRate = $l['tax_rate'];
                $line->TaxableBaseAmount = -((float) $l['total_price_tax_excl']);
                $line->TaxAmountTotal = -((float) $l['total_price_tax_incl'] - (float) $l['total_price_tax_excl']);
                $line->ArticleCode = $l['product_reference'];
                $seq++;

                $data->invoice->lines[] = $line;
            } 

            // Comprobamos si el pedido tiene gastos de envío. Usamos los datos de la factura para mayor precisión.
            if ((float)$slip['total_shipping_tax_excl'] > 0) {
                $shipping_line = new \stdClass();
                
                $shipping_tax_rate = 0;
                // Calculamos el tipo de IVA del envío a partir de los totales de la factura para evitar errores de redondeo.
                if ((float)$slip['total_shipping_tax_excl'] > 0) {
                    $shipping_tax_rate = (((float)$slip['total_shipping_tax_incl'] / (float)$slip['total_shipping_tax_excl']) - 1) * 100;
                }

                $shipping_line->SequenceNumber = $seq;
                $shipping_line->ItemDescription = 'Gastos de Envío';
                $shipping_line->Quantity = 1;
                $shipping_line->UnitPriceWithoutTax = $slip['total_shipping_tax_excl'];
                $shipping_line->TotalCost = $slip['total_shipping_tax_incl'];
                $shipping_line->GrossAmount = $slip['total_shipping_tax_incl'];
                $shipping_line->TaxTypeCode = '01';
                $shipping_line->TaxRate = round($shipping_tax_rate, 2);
                $shipping_line->TaxableBaseAmount = (float)$slip['total_shipping_tax_excl'];
                $shipping_line->TaxAmountTotal = (float)$slip['total_shipping_tax_incl'] - (float)$slip['total_shipping_tax_excl'];
                $shipping_line->ArticleCode = 'ENVIO';
                
                $data->invoice->lines[] = $shipping_line;
            }  
        }
        else //Si es factura de alta
        {
            $InvoiceNumber = $this->getFormattedInvoiceNumber($invoice['id_order_invoice']);
            $inv->InvoiceNumber = $InvoiceNumber;
            $inv->InvoiceDocumentType = ($taxIdentificationNumber != ''?"FC":"FA");
            $inv->InvoiceClass = "OO"; //Factura normal
            $inv->IssueDate = date('Y-m-d', strtotime($invoice['date_add']));
            $inv->InvoiceCurrencyCode = $currency['iso_code'];
            $inv->TaxCurrencyCode = $currency['iso_code'];
            $inv->LanguageName = 'es';
            $inv->TotalGrossAmount = $invoice['total_paid_tax_excl'];
            $inv->TotalGeneralDiscounts = 0;
            $inv->TotalGeneralSurcharges = 0;
            $inv->TotalGrossAmountBeforeTaxes = $invoice['total_paid_tax_excl'];
            $inv->TotalTaxOutputs = ((float) $invoice['total_paid_tax_incl'] - (float) $invoice['total_paid_tax_excl']);
            $inv->TotalTaxesWithheld = ((float) $invoice['total_paid_tax_incl'] - (float) $invoice['total_paid_tax_excl']);
            $inv->InvoiceTotal = ((float) $invoice['total_paid_tax_incl']);
            $inv->TotalOutstandingAmount = $invoice['total_paid_tax_incl'];
            $inv->TotalExecutableAmount = $invoice['total_paid_tax_incl'];

            $data->invoice = $inv;

            $seq = 1;
            foreach ($lines as $l)
            {
                
                $line = new \stdClass();
                $line->SequenceNumber = $seq;
                //$line->DeliveryNoteNumber = $seq; //ESTO QUE ES??
                //$line->DeliveryNoteDate = $seq; //ESTO QUE ES??
                $line->ItemDescription = $l['product_name'];
                $line->Quantity = $l['product_quantity'];
                $line->UnitPriceWithoutTax = $l['product_price'];
                $line->TotalCost = $l['total_price_tax_incl'];
                $line->GrossAmount = $l['total_price_tax_incl'];
                $line->TaxTypeCode = '01';
                $line->TaxRate = $l['tax_rate'];
                $line->TaxableBaseAmount = ((float) $l['total_price_tax_excl']);
                $line->TaxAmountTotal = ((float) $l['total_price_tax_incl'] - (float) $l['total_price_tax_excl']);
                $line->ArticleCode = $l['product_reference'];
                $seq++;

                $data->invoice->lines[] = $line;
            } 

            // Comprobamos si el pedido tiene gastos de envío. Usamos los datos de la factura para mayor precisión.
            if ((float)$invoice['total_shipping_tax_excl'] > 0) {
                $shipping_line = new \stdClass();
                
                $shipping_tax_rate = 0;
                // Calculamos el tipo de IVA del envío a partir de los totales de la factura para evitar errores de redondeo.
                if ((float)$invoice['total_shipping_tax_excl'] > 0) {
                    $shipping_tax_rate = (((float)$invoice['total_shipping_tax_incl'] / (float)$invoice['total_shipping_tax_excl']) - 1) * 100;
                }

                $shipping_line->SequenceNumber = $seq;
                $shipping_line->ItemDescription = 'Gastos de Envío';
                $shipping_line->Quantity = 1;
                $shipping_line->UnitPriceWithoutTax = $invoice['total_shipping_tax_excl'];
                $shipping_line->TotalCost = $invoice['total_shipping_tax_incl'];
                $shipping_line->GrossAmount = $invoice['total_shipping_tax_incl'];
                $shipping_line->TaxTypeCode = '01';
                $shipping_line->TaxRate = round($shipping_tax_rate, 2);
                $shipping_line->TaxableBaseAmount = (float)$invoice['total_shipping_tax_excl'];
                $shipping_line->TaxAmountTotal = (float)$invoice['total_shipping_tax_incl'] - (float)$invoice['total_shipping_tax_excl'];
                $shipping_line->ArticleCode = 'ENVIO';
                
                $data->invoice->lines[] = $shipping_line;
            }  
        }

        $dataString = json_encode($data);

        if ($this->debugMode)
        {
            PrestaShopLogger::addLog(
                'Módulo Verifactu: Alta - Envío a api ' . $dataString.'
                ',
                1, null, null, null, true, $this->id_shop
            );
        }

        curl_setopt_array($curl, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => 'utf-8',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2TLS,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => $dataString,
                CURLOPT_HTTPHEADER     => $headers,
            ]
        );

        $response = curl_exec($curl);
        curl_close($curl);

        //die($response);
        if ($this->debugMode)
        {
            PrestaShopLogger::addLog(
                'Módulo Verifactu: Alta - Respuesta de api ' . $response.'
                ',
                1, null, null, null, true, $this->id_shop
            );
        }

        //Guardamos el campo verifactuEstadoRegistro y verifactuEstadoEnvio en base de datos si ha sido correcto
        $obj = json_decode($response);

        //die($obj->error);

        if (isset($obj, $obj->response) && $obj->response == 'OK')
        {  
            $urlQR = isset($obj->urlQR) ? pSQL($obj->urlQR) : '';
            $id_reg_fact = isset($obj->id_reg_fact) ? (int)$obj->id_reg_fact : 0;
            $api_estado_queue = 'pendiente';
            $id_order_invoice = 0;

            if ($tipo == 'abono') 
            {
                $id_order_invoice = $slip['id_order_slip'];

                //Miramos si a se habia enviado previamente para actualizar el estado o insertar uno nuevo
                $vos = Db::getInstance()->getRow('SELECT id_order_slip FROM ' . _DB_PREFIX_ . 'verifactu_order_slip WHERE id_order_slip = "'.(int) $slip['id_order_slip'].'"');

                if (isset($vos,$vos['id_order_slip']) && $vos['id_order_slip'] != '')
                {
                    $update_data = [
                        'estado' => 'pendiente',
                        'id_reg_fact' => $id_reg_fact,
                    ];
                    if (!Db::getInstance()->update('verifactu_order_slip', $update_data, 'id_order_slip = ' . $id_order_invoice)) {
                        if ($this->debugMode) {
                            PrestaShopLogger::addLog('Módulo Verifactu: Alta Abono - Error al actualizar verifactu_order_slip: ' . Db::getInstance()->getMsgError(), 3, null, null, null, true, $this->id_shop);
                        }
                    }
                }
                else
                {
                    $insert_data = [
                        'id_order_slip' => $id_order_invoice,
                        'estado' => 'pendiente',
                        'id_reg_fact' => $id_reg_fact,
                        'urlQR' => $urlQR,
                    ];
                    if (!Db::getInstance()->insert('verifactu_order_slip', $insert_data)) {
                         if ($this->debugMode) {
                            PrestaShopLogger::addLog('Módulo Verifactu: Alta Abono - Error al insertar en verifactu_order_slip: ' . Db::getInstance()->getMsgError(), 3, null, null, null, true, $this->id_shop);
                        }
                    }
                }
            }
            else
            {
                $id_order_invoice = $invoice['id_order_invoice'];

                $voi = Db::getInstance()->getRow('SELECT id_order_invoice FROM ' . _DB_PREFIX_ . 'verifactu_order_invoice WHERE id_order_invoice = "'.(int) $invoice['id_order_invoice'].'"');

                if (isset($voi,$voi['id_order_invoice']) && $voi['id_order_invoice'] != '')
                {
                    $update_data = [
                        'estado' => 'pendiente',
                        'id_reg_fact' => $id_reg_fact,
                    ];
                    if (!Db::getInstance()->update('verifactu_order_invoice', $update_data, 'id_order_invoice = ' . $id_order_invoice)) {
                        if ($this->debugMode) {
                            PrestaShopLogger::addLog('Módulo Verifactu: Alta Factura - Error al actualizar verifactu_order_invoice: ' . Db::getInstance()->getMsgError(), 3, null, null, null, true, $this->id_shop);
                        }
                    }
                }
                else
                {
                    $insert_data = [
                        'id_order_invoice' => $id_order_invoice,
                        'estado' => 'pendiente',
                        'id_reg_fact' => $id_reg_fact,
                        'urlQR' => $urlQR,
                    ];
                    if (!Db::getInstance()->insert('verifactu_order_invoice', $insert_data)) {
                        if ($this->debugMode) {
                           PrestaShopLogger::addLog('Módulo Verifactu: Alta Factura - Error al insertar en verifactu_order_invoice: ' . Db::getInstance()->getMsgError(), 3, null, null, null, true, $this->id_shop);
                        }
                    }
                }
            }

            if (isset($obj->id_reg_fact)) //Si se ha guardado un registro de facturación, del tipo que sea
            {

                $insert_data = [
                'id_reg_fact' => (int)$obj->id_reg_fact,
                'tipo' => pSQL($tipo),
                'estado_queue' => pSQL($api_estado_queue),
                'id_order_invoice' => (int)$id_order_invoice,
                'invoice_number' => pSQL($obj->InvoiceNumber),
                'urlQR' => pSQL($obj->urlQR),
                'id_shop' => (int)$this->id_shop
                ];
                if (!Db::getInstance()->insert('verifactu_reg_fact', $insert_data)) {
                     if ($this->debugMode) {
                        PrestaShopLogger::addLog('Módulo Verifactu: CheckPending - Error al insertar en verifactu_reg_fact: ' . Db::getInstance()->getMsgError(), 3, null, null, null, true, $this->id_shop);
                    }

                }

            }

            
            
            $reply['response'] = 'OK';
        }
        else
        {
            $reply['response'] = 'KO';
        }


        return json_encode($reply);
         
    }

    public function sendAnulacionVerifactu($id_order,$tipo='alta')
    {
        $sql = new DbQuery();
        $sql->select('*')->from('orders')->where('id_order = ' . (int)$id_order);
        $order_data = Db::getInstance()->getRow($sql);

        if (!$order_data) 
        {
            if ($this->debugMode) {
                PrestaShopLogger::addLog('Módulo Verifactu: No se encontraron datos para el pedido ID ' . $id_order, 2, null, null, null, true, $this->id_shop);
            }
            return json_encode(['response' => 'KO', 'error' => 'Pedido no encontrado.']);
        }

        if ($tipo == 'abono')
        {
            $sql = new DbQuery();
            $sql->select('os.*, vos.verifactuEstadoRegistro, vos.estado')
                ->from('order_slip', 'os')
                ->leftJoin('verifactu_order_slip', 'vos', 'os.id_order_slip = vos.id_order_slip')
                ->where('os.id_order = ' . (int)$id_order)
                ->orderBy('os.id_order_slip DESC');
            $slip = Db::getInstance()->getRow($sql);

            $sql = new DbQuery();
            $sql->select('sd.*, od.product_reference, od.tax_rate, od.product_name')
                ->from('order_slip_detail', 'sd')
                ->leftJoin('order_detail', 'od', 'sd.id_order_detail = od.id_order_detail')
                ->where('sd.id_order_slip = ' . (int)$slip['id_order_slip']);
            $slipLines = Db::getInstance()->executeS($sql);
                

            if (isset($slip['estado']) && $slip['estado'] == 'pendiente') //Si el estado es pendiente evitamos que se vuelva a enviar.
            {
                $reply['response'] = 'pendiente';
                return json_encode($reply);
            }
                
        }

        $sql = new DbQuery();
        $sql->select('oi.*, voi.verifactuEstadoRegistro, voi.estado')
            ->from('order_invoice', 'oi')
            ->leftJoin('verifactu_order_invoice', 'voi', 'oi.id_order_invoice = voi.id_order_invoice')
            ->where('oi.id_order = ' . (int)$id_order)
            ->orderBy('oi.id_order_invoice DESC');
        $invoice = Db::getInstance()->getRow($sql);

        if (isset($invoice['estado']) && $invoice['estado'] == 'pendiente') //Si el estado es pendiente evitamos que se vuelva a enviar.
        {
            $reply['response'] = 'pendiente';
            return json_encode($reply);
        }

        $curl  = curl_init();

        $url   = 'https://verifactu.infoal.io/api_v2/verifactu/anulacion';

        $token = $this->apiToken;


        // HTTP request headers
        $headers = [
            'Authorization: Bearer '.$token,
            'Content-Type: application/json',
        ];

        $data = new \stdClass();

        $InvoiceNumber = $this->getFormattedInvoiceNumber($invoice['id_order_invoice']);
        $data->InvoiceNumber = $InvoiceNumber;
        
        $dataString = json_encode($data);

        if ($this->debugMode)
        {
            PrestaShopLogger::addLog(
                'Módulo Verifactu: Anulación - Envío a api ' . $dataString.'
                ',
                1, null, null, null, true, $this->id_shop
            );
        }

        curl_setopt_array($curl, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => 'utf-8',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2TLS,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => $dataString,
                CURLOPT_HTTPHEADER     => $headers,
            ]
        );

        $response = curl_exec($curl);
        curl_close($curl);

        //die($response);
        if ($this->debugMode)
        {
            PrestaShopLogger::addLog(
                'Módulo Verifactu: Anulación - Respuesta de api ' . $response.'
                ',
                1, null, null, null, true, $this->id_shop
            );
        }

        //Guardamos el campo verifactuEstadoRegistro y verifactuEstadoEnvio en base de datos si ha sido correcto
        $obj = json_decode($response);

        if (isset($obj, $obj->response) && $obj->response == 'OK')
        {
            $id_reg_fact = isset($obj->id_reg_fact) ? (int)$obj->id_reg_fact : 0;
            $api_estado_queue = 'pendiente';
            $InvoiceNumber = '';

            $update_data = [
                'estado' => 'pendiente',
                'id_reg_fact' => $id_reg_fact,
            ];

            if ($tipo == 'abono')
            {
                $id_order_invoice = (int)$slip['id_order_slip'];
                $InvoiceNumber = $this->getFormattedCreditSlipNumber($id_order_invoice);
                if (!Db::getInstance()->update('verifactu_order_slip', $update_data, 'id_order_slip = ' . $id_order_invoice)) {
                    if ($this->debugMode) {
                        PrestaShopLogger::addLog('Módulo Verifactu: Anulación Abono - Error al actualizar verifactu_order_slip: ' . Db::getInstance()->getMsgError(), 3, null, null, null, true, $this->id_shop);
                    }
                }
            }
            else
            {
                $id_order_invoice = (int)$invoice['id_order_invoice'];
                $InvoiceNumber = $this->getFormattedInvoiceNumber($id_order_invoice);
                if (!Db::getInstance()->update('verifactu_order_invoice', $update_data, 'id_order_invoice = ' . $id_order_invoice)) {
                    if ($this->debugMode) {
                        PrestaShopLogger::addLog('Módulo Verifactu: Anulación Factura - Error al actualizar verifactu_order_invoice: ' . Db::getInstance()->getMsgError(), 3, null, null, null, true, $this->id_shop);
                    }
                }
            }

            if (isset($obj->id_reg_fact)) //Si se ha guardado un registro de facturación, del tipo que sea
            {
                $reg_fact_data = [
                'id_reg_fact' => (int)$obj->id_reg_fact,
                'tipo' => pSQL($tipo),
                'id_reg_fact' => (int)$obj->id_reg_fact,
                'estado_queue' => pSQL($api_estado_queue),
                'id_order_invoice' => (int)$id_order_invoice,
                'invoice_number' => pSQL($obj->InvoiceNumber),
                'id_shop' => (int)$this->id_shop
                ];
                if (!Db::getInstance()->insert('verifactu_reg_fact', $reg_fact_data)) {
                     if ($this->debugMode) {
                        PrestaShopLogger::addLog('Módulo Verifactu: CheckPending - Error al insertar en verifactu_reg_fact: ' . Db::getInstance()->getMsgError(), 3, null, null, null, true, $this->id_shop);
                    }
                }

            }

            
            
        }
        
        

        return $response;
    }

    public function checkPendingInvoices()
    {

        // NOTA: La lógica de consulta a tu API externa iría aquí.
        // Por ahora, simularemos una respuesta y actualizaremos la BD.
        
        // 1. Buscar facturas pendientes en nuestra base de datos.
        $sql = new DbQuery();
        $sql->select('id_order_invoice, id_reg_fact')->from('verifactu_order_invoice')->where('estado = "pendiente"');
        $pending_invoices = Db::getInstance()->executeS($sql);

        $sql = new DbQuery();
        $sql->select('id_order_slip, id_reg_fact')->from('verifactu_order_slip')->where('estado = "pendiente"');
        $pending_slips = Db::getInstance()->executeS($sql);

        $updated_count = 0;

        $data = new \stdClass();
        $ids = array();
        $inv = array();
        $sl = array();

        foreach ($pending_invoices as $p)
        {
            $id_reg_fact = (int) $p['id_reg_fact'];
            if ($id_reg_fact != '0')
            {
                $ids[] = $p['id_reg_fact'];
                //Para la comprobación luego
                $inv[] = $p['id_reg_fact'];
            }
            
        }

        foreach ($pending_slips as $p)
        {
            $id_reg_fact = (int) $p['id_reg_fact'];
            if ($id_reg_fact != '0')
            {
                $ids[] = $p['id_reg_fact'];
                //para la comprobacion luego
                $sl[] = $p['id_reg_fact'];
            }
        }

        $data->ids = $ids;

        if ($ids) 
        {
            $curl  = curl_init();
            $url   = 'https://verifactu.infoal.io/api_v2/verifactu/check';
            $token = $this->apiToken;

            // HTTP request headers
            $headers = [
                'Authorization: Bearer '.$token,
                'Content-Type: application/json',
            ];

            $dataString = json_encode($data);


            if ($this->debugMode)
            {
                PrestaShopLogger::addLog(
                    'Módulo Verifactu: Update pendientes: Envío a api ' . $dataString.'
                    ',
                    1, null, null, null, true, $this->id_shop
                );
            }

            curl_setopt_array($curl, [
                    CURLOPT_URL            => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING       => 'utf-8',
                    CURLOPT_MAXREDIRS      => 10,
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2TLS,
                    CURLOPT_CUSTOMREQUEST  => 'POST',
                    CURLOPT_POSTFIELDS     => $dataString,
                    CURLOPT_HTTPHEADER     => $headers,
                ]
            );

            $response = curl_exec($curl);
            curl_close($curl);

            //die($response);
            if ($this->debugMode)
            {
                PrestaShopLogger::addLog(
                    'Módulo Verifactu: Update pendientes: Respuesta de api ' . $response.'
                    ',
                    1, null, null, null, true, $this->id_shop
                );
            }

            //Guardamos el campo verifactuEstadoRegistro y verifactuEstadoEnvio en base de datos si ha sido correcto
            $obj = json_decode($response);

            if (isset($obj))
            {
                foreach ($obj as $o)
                {
                    if ($this->debugMode)
                    {
                        PrestaShopLogger::addLog(
                            'Procesando:'.$o->id_reg_fact.'
                            ',
                            1, null, null, null, true, $this->id_shop
                        );
                    }

                    $guardar = false;

                    if (in_array($o->id_reg_fact, $inv))
                    {
                        if ($o->estado_queue != 'pendiente' && $o->estado_queue != 'procesando')
                        {
                            //Es factura de venta
                            $invoice = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'verifactu_order_invoice WHERE id_reg_fact = "'.(int) $o->id_reg_fact.'"');
                            
                            if ($o->tipo == 'anulacion') //Guardamos siempre
                            {
                                $guardar = true;
                                $anulacion = 1;
                            }
                            else
                            {
                                $anulacion = 0;
                                if ($invoice['verifactuEstadoRegistro'] == 'Correcto') //Si está marcada ya como correcto no hacemos nada
                                {

                                }
                                else if ($invoice['verifactuEstadoRegistro'] == 'AceptadoConErrores') //Si está marcada como AceptadoConErrores solo modificamos si es AceptadoConErrores o Correcto
                                {
                                    if ($o->EstadoRegistro == 'Correcto' || $o->EstadoRegistro == 'AceptadoConErrores')
                                    { 
                                        $guardar = true;
                                    }
                                }
                                else //Para lo demás guardamos el estado que sea
                                {
                                    $guardar = true;
                                }
                            }

                            if ($guardar) //Actualizamos
                            {   
                                $update_data = [
                                'estado' => 'sincronizado',
                                'verifactuEstadoRegistro' => pSQL($o->EstadoRegistro),
                                'verifactuEstadoEnvio' => pSQL($o->EstadoEnvio),
                                'verifactuCodigoErrorRegistro' => pSQL($o->CodigoErrorRegistro),
                                'verifactuDescripcionErrorRegistro' => pSQL($o->DescripcionErrorRegistro),
                                'TipoFactura' => pSQL($o->TipoFactura),
                                'urlQR' => pSQL($o->urlQR),
                                'anulacion' => (int)$anulacion,
                                ];
                                if (!Db::getInstance()->update('verifactu_order_invoice', $update_data, 'id_order_invoice = ' . (int)$invoice['id_order_invoice'])) {
                                    if ($this->debugMode) {
                                        PrestaShopLogger::addLog('Módulo Verifactu: CheckPending - Error al actualizar verifactu_order_invoice: ' . Db::getInstance()->getMsgError(), 3, null, null, null, true, $this->id_shop);
                                    }
                                }
                                //Guardamos parámetros para el log
                                $id_order_invoice = $invoice['id_order_invoice'];
                                $tipo = 'alta';
                            }
                        }
                        
                    }
                    else if (in_array($o->id_reg_fact, $sl)) //Es de abono
                    {
                        
                        if ($o->estado_queue != 'pendiente' && $o->estado_queue != 'procesando')
                        {
                            $slip = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'verifactu_order_slip WHERE id_reg_fact = "'.(int) $o->id_reg_fact.'"');

                            if ($o->tipo == 'anulacion') //Guardamos siempre
                            {
                                $guardar = true;
                                $anulacion = 1;
                            }
                            else
                            {   
                                $anulacion = 0;
                                if ($slip['verifactuEstadoRegistro'] == 'Correcto') //Si está marcada ya como correcto no hacemos nada
                                {

                                }
                                else if ($slip['verifactuEstadoRegistro'] == 'AceptadoConErrores') //Si está marcada como AceptadoConErrores solo modificamos si es AceptadoConErrores o Correcto
                                {
                                    if ($o->EstadoRegistro == 'Correcto' || $o->EstadoRegistro == 'AceptadoConErrores')
                                    { 
                                        $guardar = true;
                                    }
                                }
                                else //Para lo demás guardamos el estado que sea
                                {
                                    $guardar = true;
                                }
                            }

                            if ($guardar) //Guardamos el verifactu_order_invoice solo si no existe el registro o este ha cambiado
                            {   
                                $update_data = [
                                'estado' => 'sincronizado',
                                'verifactuEstadoRegistro' => pSQL($o->EstadoRegistro),
                                'verifactuEstadoEnvio' => pSQL($o->EstadoEnvio),
                                'verifactuCodigoErrorRegistro' => pSQL($o->CodigoErrorRegistro),
                                'verifactuDescripcionErrorRegistro' => pSQL($o->DescripcionErrorRegistro),
                                'TipoFactura' => pSQL($o->TipoFactura),
                                'urlQR' => pSQL($o->urlQR),
                                'anulacion' => (int)$anulacion,
                                ];
                                if (!Db::getInstance()->update('verifactu_order_slip', $update_data, 'id_order_slip = ' . (int)$slip['id_order_slip'])) {
                                    if ($this->debugMode) {
                                        PrestaShopLogger::addLog('Módulo Verifactu: CheckPending - Error al actualizar verifactu_order_slip: ' . Db::getInstance()->getMsgError(), 3, null, null, null, true, $this->id_shop);
                                    }
                                }

                                //Guardamos parámetros para el log
                                $id_order_invoice = $slip['id_order_slip'];
                                $tipo = 'abono';
                            }
                        }
                        
                    }


                    if ($guardar) //Si se ha guardado algun registro de factura o abono, guardamos el registro de facturación y actualizamos el log
                    { 
                        if (isset($o->id_reg_fact)) //Si se ha guardado un registro de facturación, del tipo que sea
                        {
                            $update_data = [

                                //Cola
                                'estado_queue' => (isset($o->estado_queue) ? pSQL($o->estado_queue) : ''), 

                                //Factura
                                'InvoiceNumber' => (isset($o->InvoiceNumber) ? pSQL($o->InvoiceNumber) : ''), 
                                'IssueDate' => (isset($o->IssueDate) ? pSQL($o->IssueDate) : ''), 

                                //Respuesta verifactu
                                'EstadoEnvio' => (isset($o->EstadoEnvio) ? pSQL($o->EstadoEnvio) : ''),
                                'EstadoRegistro' => (isset($o->EstadoRegistro) ? pSQL($o->EstadoRegistro) : ''), 
                                'CodigoErrorRegistro' => (isset($o->CodigoErrorRegistro) ? pSQL($o->CodigoErrorRegistro) : ''), 
                                'DescripcionErrorRegistro' => (isset($o->DescripcionErrorRegistro) ? pSQL($o->DescripcionErrorRegistro) : ''), 

                                //Registro de facturación - Registro
                                'TipoOperacion' => (isset($o->TipoOperacion) ? pSQL($o->TipoOperacion) : ''),
                                'EmpresaNombreRazon' => (isset($o->EmpresaNombreRazon) ? pSQL($o->EmpresaNombreRazon) : ''), 
                                'EmpresaNIF' => (isset($o->EmpresaNIF) ? pSQL($o->EmpresaNIF) : ''),
                                'hash' => (isset($o->hash) ? pSQL($o->hash) : ''),
                                'cadena' => (isset($o->cadena) ? pSQL($o->cadena) : ''), 
                                'AnteriorHash' => (isset($o->AnteriorHash) ? pSQL($o->AnteriorHash) : ''),

                                'TipoFactura' => (isset($o->TipoFactura) ? pSQL($o->TipoFactura) : ''),
                                'FacturaSimplificadaArt7273' => (isset($o->FacturaSimplificadaArt7273) ? pSQL($o->FacturaSimplificadaArt7273) : ''),
                                'FacturaSinIdentifDestinatarioArt61d' => (isset($o->FacturaSinIdentifDestinatarioArt61d) ? pSQL($o->FacturaSinIdentifDestinatarioArt61d) : ''),
                                //'CalificacionOperacion' => pSQL($o->CalificacionOperacion),
                                'Macrodato' => (isset($o->Macrodato) ? pSQL($o->Macrodato) : ''), 



                                'Cupon' => (isset($o->Cupon) ? pSQL($o->Cupon) : ''),
                                'TotalTaxOutputs' => (isset($o->TotalTaxOutputs) ? pSQL($o->TotalTaxOutputs) : ''),
                                'InvoiceTotal' => (isset($o->InvoiceTotal) ? pSQL($o->InvoiceTotal) : ''),

                                //Fechas registro
                                'FechaHoraHusoGenRegistro' => (isset($o->FechaHoraHusoGenRegistro) ? pSQL($o->FechaHoraHusoGenRegistro) : ''),
                                'fechaHoraRegistro' => (isset($o->fechaHoraRegistro) ? pSQL($o->fechaHoraRegistro) : ''),

                                //SIF
                                'SIFNombreRazon' => (isset($o->SIFNombreRazon) ? pSQL($o->SIFNombreRazon) : ''),
                                'SIFNIF' => (isset($o->SIFNIF) ? pSQL($o->SIFNIF) : ''),
                                'SIFNombreSIF' => (isset($o->SIFNombreSIF) ? pSQL($o->SIFNombreSIF) : ''),
                                'SIFIdSIF' => (isset($o->SIFIdSIF) ? pSQL($o->SIFIdSIF) : ''), 
                                'SIFVersion' => (isset($o->SIFVersion) ? pSQL($o->SIFVersion) : ''),
                                'SIFNumeroInstalacion' => (isset($o->SIFNumeroInstalacion) ? pSQL($o->SIFNumeroInstalacion) : ''),
                                'SIFTipoUsoPosibleSoloVerifactu' => (isset($o->SIFTipoUsoPosibleSoloVerifactu) ? pSQL($o->SIFTipoUsoPosibleSoloVerifactu) : ''),
                                'SIFTipoUsoPosibleMultiOT' => (isset($o->SIFTipoUsoPosibleMultiOT) ? pSQL($o->SIFTipoUsoPosibleMultiOT) : ''),
                                'SIFIndicadorMultiplesOT' => (isset($o->SIFIndicadorMultiplesOT) ? pSQL($o->SIFIndicadorMultiplesOT) : ''),
                                ];

                                //Comprador
                                if ($o->FacturaSimplificadaArt7273 != 'S')
                                {
                                    $update_data['BuyerName'] = (isset($o->BuyerName) ? pSQL($o->BuyerName) : '');
                                    $update_data['BuyerCorporateName'] = (isset($o->BuyerCorporateName) ? pSQL($o->BuyerCorporateName) : '');
                                    $update_data['BuyerCountryCode'] = (isset($o->BuyerCountryCode) ? pSQL($o->BuyerCountryCode) : '');
                                    if (isset($o->BuyerCountryCode) && $o->BuyerCountryCode != 'ES')
                                    {
                                        $update_data['IDOtroIDType'] = (isset($o->IDOtroIDType) ? pSQL($o->IDOtroIDType) : '');
                                        $update_data['IDOtroID'] = (isset($o->IDOtroID) ? pSQL($o->IDOtroID) : '');
                                    }
                                    else
                                    {
                                        $update_data['BuyerTaxIdentificationNumber'] = (isset($o->BuyerTaxIdentificationNumber) ? pSQL($o->BuyerTaxIdentificationNumber) : '');
                                    }
                                    
                                }

                                //Factura rectificativa
                                if (substr($o->TipoFactura,0,1) == 'R') 
                                {
                                    $update_data['TipoRectificativa'] = pSQL($o->TipoRectificativa);
                                    $update_data['CorrectiveInvoiceNumber'] = pSQL($o->CorrectiveInvoiceNumber);
                                    $update_data['CorrectiveInvoiceSeriesCode'] = pSQL($o->CorrectiveInvoiceSeriesCode);
                                    $update_data['CorrectiveIssueDate'] = pSQL($o->CorrectiveIssueDate);
                                    $update_data['CorrectiveBaseAmount'] = pSQL($o->CorrectiveBaseAmount);
                                    $update_data['CorrectiveTaxAmount'] = pSQL($o->CorrectiveTaxAmount);
                                }

                                if (!Db::getInstance()->update('verifactu_reg_fact', $update_data, 'id_reg_fact = ' . (int)$o->id_reg_fact)) {
                                    if ($this->debugMode) {
                                        PrestaShopLogger::addLog('Módulo Verifactu: CheckPending - Error al actualizar verifactu_order_slip: ' . Db::getInstance()->getMsgError(), 3, null, null, null, true, $this->id_shop);
                                    }
                                }

                        }

                        
                    }

                    
                    
                }

                
            }

        }
        
        // 5. Devolvemos una respuesta JSON al JavaScript.
        header('Content-Type: application/json');
        die(json_encode([
            'success' => true,
            'message' => $updated_count . ' registros actualizados.',
            'updated' => $updated_count
        ]));

    }

    

    /**
     * Genera el número de factura completo y formateado tal como lo hace PrestaShop,
     * incluyendo el prefijo y el año si está configurado.
     *
     * @param int $id_order_invoice El ID de la factura (de la tabla ps_order_invoice).
     * @return string|null El número de factura formateado o null si no se encuentra.
     */
    public function getFormattedInvoiceNumber($id_order_invoice)
    {
        // 1. Validamos la entrada.
        if ($id_order_invoice <= 0) {
            return null;
        }

        // 2. Preparamos la consulta para obtener todos los datos necesarios:
        // el número, la fecha (para el año) y el idioma (para el prefijo).
        $sql = new DbQuery();
        $sql->select('oi.number, oi.date_add, o.id_lang, o.id_shop');
        $sql->from('order_invoice', 'oi');
        $sql->leftJoin('orders', 'o', 'o.id_order = oi.id_order');
        $sql->where('oi.id_order_invoice = ' . (int)$id_order_invoice);
        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            return null;
        }

        $id_shop = (int)$result['id_shop'];

        // 3. Obtenemos las variables de configuración de PrestaShop.
        $prefix = Configuration::get('PS_INVOICE_PREFIX', (int)$result['id_lang'], null, $id_shop);
        $use_year = (int)Configuration::get('PS_INVOICE_USE_YEAR', null, $id_shop);
        $year_position = (int)Configuration::get('PS_INVOICE_YEAR_POS', null, $id_shop);

        // 4. Preparamos los componentes del número.
        $padded_number = sprintf('%06d', $result['number']);
        $year = date('Y', strtotime($result['date_add']));
        
        $final_invoice_number = $prefix;

        // 5. Construimos el número final basándonos en la configuración del año.
        if ($use_year == '1')
        {
            switch ($year_position) {
                case 1: // Año antes del número
                    $final_invoice_number .= $year .'/'. $padded_number;
                    break;
                case 2: // Año después del número
                    $final_invoice_number .= $padded_number .'/'. $year;
                    break;
                case 0: // Sin año
                default:
                    $final_invoice_number .= $padded_number;
                    break;
            }
        }
        

        return $final_invoice_number;
    }

    /**
     * Genera el número de factura de abono completo y formateado tal como lo hace PrestaShop,
     * incluyendo el prefijo y el año si está configurado.
     *
     * @param int $id_order_slip El ID de la factura de abono (de la tabla ps_order_slip).
     * @return string|null El número de abono formateado o null si no se encuentra.
     */
    private function getFormattedCreditSlipNumber($id_order_slip)
    {
        // 1. Validamos la entrada.
        if ($id_order_slip <= 0) {
            return null;
        }

        // 2. Preparamos la consulta para obtener los datos necesarios:
        // la fecha (para el año) y el idioma del pedido asociado.
        $sql = new DbQuery();
        $sql->select('os.date_add, o.id_lang, o.id_shop');
        $sql->from('order_slip', 'os');
        $sql->leftJoin('orders', 'o', 'o.id_order = os.id_order');
        $sql->where('os.id_order_slip = ' . (int)$id_order_slip);

        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            return null;
        }

        $id_shop = (int)$result['id_shop'];

        // 3. Obtenemos las variables de configuración específicas para las facturas de abono.
        $prefix = Configuration::get('PS_CREDIT_SLIP_PREFIX', (int)$result['id_lang'], null, $id_shop);
        $year_position = (int)Configuration::get('PS_CREDIT_SLIP_YEAR_POS', null, $id_shop);

        // 4. Preparamos los componentes del número.
        // En las facturas de abono, el ID se usa como número secuencial.
        $padded_number = sprintf('%06d', $id_order_slip);
        $year = date('Y', strtotime($result['date_add']));
        
        $final_slip_number = $prefix;

        // 5. Construimos el número final basándonos en la configuración del año.
        switch ($year_position) {
            case 1: // Año antes del número
                $final_slip_number .= $year . $padded_number;
                break;
            case 2: // Año después del número
                $final_slip_number .= $padded_number . $year;
                break;
            case 0: // Sin año
            default:
                $final_slip_number .= $padded_number;
                break;
        }

        return $final_slip_number;
    }

}