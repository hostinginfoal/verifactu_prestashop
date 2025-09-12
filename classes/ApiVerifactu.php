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
    public function checkDNI($id_order)
    {
        $order = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_order = "'.(int) $id_order.'"');
        $invoice = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'order_invoice WHERE id_order = "'.(int) $id_order.'"');
        $address = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'address WHERE id_address = "'.(int) $order['id_address_invoice'].'"');
        $prov = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'state WHERE id_state = "'.(int) $address['id_state'].'"');
        $pais = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'country WHERE id_country = "'.(int) $address['id_country'].'"');
        $currency = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'currency WHERE id_currency = "'.(int) $order['id_currency'].'"');
        $lines = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'order_detail WHERE id_order = "'.(int) $id_order.'"');

        $curl  = curl_init();
        $url   = 'https://verifactu.infoal.io/api_v2/cdi/check';
        $token = Configuration::get('VERIFACTU_API_TOKEN', null);

        // HTTP request headers
        $headers = [
            'Authorization: Bearer '.$token,
            'Content-Type: application/json',
        ];

        $data = new \stdClass();
        $data->dni = $address['dni'];
        $data->nombre = $address['firstname'].' '.$address['lastname'];
        $dataString = json_encode($data);

        if (Configuration::get('VERIFACTU_DEBUG_MODE') == '1')
        {
            PrestaShopLogger::addLog(
                'Módulo Verifactu: CDI - Envío a api ' . $dataString.'
                ',
                1
            );
        }

        curl_setopt_array($curl, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => 'utf-8',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2TLS,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => $dataString,
                CURLOPT_HTTPHEADER     => $headers,
            ]
        );

        $response = curl_exec($curl);
        curl_close($curl);

        if (Configuration::get('VERIFACTU_DEBUG_MODE') == '1')
        {
            PrestaShopLogger::addLog(
                'Módulo Verifactu: CDI - Respuesta api ' . $response.'
                ',
                1
            );
        }

        $obj = json_decode($response);

        return $response;
    }

    public function sendAltaVerifactu($id_order,$tipo='alta')
    {
        $reply = array();

        $order = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_order = "'.(int) $id_order.'"');
        if ($tipo == 'abono')
        {
            $slip = Db::getInstance()->getRow('SELECT os.*,vos.verifactuEstadoRegistro, vos.estado FROM ' . _DB_PREFIX_ . 'order_slip as os LEFT JOIN ' . _DB_PREFIX_ . 'verifactu_order_slip as vos ON os.id_order_slip = vos.id_order_slip WHERE os.id_order = "'.(int) $id_order.'" ORDER BY os.id_order_slip DESC');
            $slipLines = Db::getInstance()->ExecuteS('SELECT sd.*,od.product_reference,od.tax_rate,od.product_name FROM ' . _DB_PREFIX_ . 'order_slip_detail as sd LEFT JOIN ' . _DB_PREFIX_ . 'order_detail as od ON sd.id_order_detail = od.id_order_detail WHERE sd.id_order_slip = "'.((int)$slip['id_order_slip']).'"'); 
                
            if (Configuration::get('VERIFACTU_DEBUG_MODE') == '1')
            {
                PrestaShopLogger::addLog(
                    'Módulo Verifactu: <br>
                    Factura de abono: '.json_encode($slip).'<br>
                    Lineas: '.json_encode($slipLines).'<br>
                    ',
                    1
                );
            }

            if ($slip->estado == 'pendiente') //Si el estado es pendiente evitamos que se vuelva a enviar.
            {
                $reply['response'] = 'pendiente';
                return json_encode($reply);
            }
                
        }

        $invoice = Db::getInstance()->getRow('SELECT oi.*,voi.verifactuEstadoRegistro, voi.estado FROM ' . _DB_PREFIX_ . 'order_invoice as oi LEFT JOIN ' . _DB_PREFIX_ . 'verifactu_order_invoice as voi ON oi.id_order_invoice = voi.id_order_invoice WHERE oi.id_order = "'.(int)$id_order.'" ORDER BY oi.id_order_invoice DESC');
        if ($invoice->estado == 'pendiente') //Si el estado es pendiente evitamos que se vuelva a enviar.
        {
            $reply['response'] = 'pendiente';
            return json_encode($reply);
        }

        $address = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'address WHERE id_address = "'.(int) $order['id_address_invoice'].'"');
        $prov = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'state WHERE id_state = "'.(int) $address['id_state'].'"');
        $pais = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'country WHERE id_country = "'.(int) $address['id_country'].'"');
        $currency = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'currency WHERE id_currency = "'.(int) $order['id_currency'].'"');
        $lines = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'order_detail WHERE id_order = "'.(int) $id_order.'"');

        $curl  = curl_init();

        $url   = 'https://verifactu.infoal.io/api_v2/verifactu/alta';
        $token = Configuration::get('VERIFACTU_API_TOKEN', null);

        // HTTP request headers
        $headers = [
            'Authorization: Bearer '.$token,
            'Content-Type: application/json',
        ];

        

        $data = new \stdClass();
        $buyer = new \stdClass();
        $inv = new \stdClass();
        
        $buyer->TaxIdentificationNumber = $address['dni'];
        $buyer->CorporateName = $address['company'];
        $buyer->Name = $address['firstname'].' '.$address['lastname'];
        $buyer->Address = $address['address1'];
        $buyer->PostCode = $address['postcode'];
        $buyer->Town = $address['city'];
        $buyer->Province = $prov['name'];
        $buyer->CountryCode = $pais['iso_code'];

        $data->buyer = $buyer;

        $order = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_order = "'.$id_order.'"');
        if ($tipo == 'abono')
        {
            $InvoiceNumber = $this->getFormattedCreditSlipNumber($slip['id_order_slip']);
            $totalTaxExcl = ((float) $slip['total_products_tax_excl'] + (float) $slip['total_shipping_tax_excl']);
            $totalTaxIncl = ((float) $slip['total_products_tax_incl'] + (float) $slip['total_shipping_tax_incl']);
            $inv->InvoiceNumber = $InvoiceNumber;
            $inv->InvoiceDocumentType = ($address['dni'] != ''?"FC":"FA");
            $inv->InvoiceClass = "OR"; //Factura normal
            $inv->IssueDate = date('Y-m-d', strtotime($slip['date_add']));
            $inv->InvoiceCurrencyCode = $currency['iso_code'];
            $inv->TaxCurrencyCode = $currency['iso_code'];
            $inv->LanguageName = 'es';
            $inv->TotalGrossAmount = -$slip['total_products_tax_excl'];
            $inv->TotalGeneralDiscounts = /*$invoice['number']*/0;
            $inv->TotalGeneralSurcharges = /*$invoice['number']*/0;
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
            $inv->InvoiceDocumentType = ($address['dni'] != ''?"FC":"FA");
            $inv->InvoiceClass = "OO"; //Factura normal
            $inv->IssueDate = date('Y-m-d', strtotime($invoice['date_add']));
            $inv->InvoiceCurrencyCode = $currency['iso_code'];
            $inv->TaxCurrencyCode = $currency['iso_code'];
            $inv->LanguageName = 'es';
            $inv->TotalGrossAmount = $invoice['total_paid_tax_excl'];
            $inv->TotalGeneralDiscounts = /*$invoice['number']*/0;
            $inv->TotalGeneralSurcharges = /*$invoice['number']*/0;
            $inv->TotalGrossAmountBeforeTaxes = $invoice['total_paid_tax_excl'];
            $inv->TotalTaxOutputs = /*-abs*/((float) $invoice['total_paid_tax_incl'] - (float) $invoice['total_paid_tax_excl']);
            $inv->TotalTaxesWithheld = ((float) $invoice['total_paid_tax_incl'] - (float) $invoice['total_paid_tax_excl']);
            $inv->InvoiceTotal = /*-abs*/((float) $invoice['total_paid_tax_incl']);
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
                $line->TaxableBaseAmount = /*-abs*/((float) $l['total_price_tax_excl']);
                $line->TaxAmountTotal = /*-abs*/((float) $l['total_price_tax_incl'] - (float) $l['total_price_tax_excl']);
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

        if (Configuration::get('VERIFACTU_DEBUG_MODE') == '1')
        {
            PrestaShopLogger::addLog(
                'Módulo Verifactu: Alta - Envío a api ' . $dataString.'
                ',
                1
            );
        }

        curl_setopt_array($curl, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => 'utf-8',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
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
        if (Configuration::get('VERIFACTU_DEBUG_MODE') == '1')
        {
            PrestaShopLogger::addLog(
                'Módulo Verifactu: Alta - Respuesta de api ' . $response.'
                ',
                1
            );
        }

        //Guardamos el campo verifactuEstadoRegistro y verifactuEstadoEnvio en base de datos si ha sido correcto
        $obj = json_decode($response);

        //die($obj->error);

        if ($obj->response == 'OK')
        {  
            $urlQR = $obj->urlQR;
            $api_id_queue = (int) $obj->id_queue;
            $api_estado_queue = 'pendiente';

            if ($tipo == 'abono') 
            {
                $id_order_invoice = $slip['id_order_slip'];

                //Miramos si a se habia enviado previamente para actualizar el estado o insertar uno nuevo
                $vos = Db::getInstance()->getRow('SELECT id_order_slip FROM ' . _DB_PREFIX_ . 'verifactu_order_slip WHERE id_order_slip = "'.(int) $slip['id_order_slip'].'"');
                if ($vos['id_order_slip'] != '')
                {
                    $sql = 'UPDATE ' . _DB_PREFIX_ . 'verifactu_order_slip SET estado="pendiente", api_id_queue="'.(int) $api_id_queue.'" WHERE id_order_slip = "'.(int) $vos['id_order_slip'].'"';
                }
                else
                {
                    $sql = 'INSERT IGNORE INTO '. _DB_PREFIX_ .'verifactu_order_slip (id_order_slip, estado, api_id_queue, urlQR) VALUES ("'. (int) $slip['id_order_slip'] .'", "pendiente", "'.(int) $api_id_queue.'", "'.pSQL($urlQR).'")';
                }
                
                
                if (!Db::getInstance()->execute($sql)) {
                    $errorMessage = Db::getInstance()->getMsgError();
                    if (Configuration::get('VERIFACTU_DEBUG_MODE') == '1')
                    {
                        PrestaShopLogger::addLog(
                            'Módulo Verifactu: Alta - Error al insertar en verifactu_order_invoice ' . $errorMessage.'
                            ',
                            1
                        );
                    }
                }
            }
            else
            {
                $id_order_invoice = $invoice['id_order_invoice'];

                $voi = Db::getInstance()->getRow('SELECT id_order_invoice FROM ' . _DB_PREFIX_ . 'verifactu_order_invoice WHERE id_order_invoice = "'.(int) $invoice['id_order_invoice'].'"');
                if ($voi['id_order_invoice'] != '')
                {
                    $sql = 'UPDATE ' . _DB_PREFIX_ . 'verifactu_order_invoice SET estado="pendiente", api_id_queue="'.(int) $api_id_queue.'" WHERE id_order_invoice = "'.(int) $voi['id_order_invoice'].'"';
                }
                else
                {
                    $sql = 'INSERT IGNORE INTO '. _DB_PREFIX_ .'verifactu_order_invoice (id_order_invoice, estado, api_id_queue, urlQR) VALUES ("'. (int) $invoice['id_order_invoice'] .'", "pendiente", "'.(int) $api_id_queue.'", "'.pSQL($urlQR).'")';
                }

                // 4. Ejecutamos la consulta.
                if (!Db::getInstance()->execute($sql)) {
                    $errorMessage = Db::getInstance()->getMsgError();
                    if (Configuration::get('VERIFACTU_DEBUG_MODE') == '1')
                    {
                        PrestaShopLogger::addLog(
                            'Módulo Verifactu: Alta - Error al insertar en verifactu_order_invoice ' . $errorMessage.'
                            ',
                            1
                        );
                    }
                }
            }

            //Guardamos el log
            $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'verifactu_logs (id_order_invoice,invoice_number,tipo,api_id_queue,api_estado_queue,fechahora) VALUES ("'.(int) $id_order_invoice.'","'.pSQL($InvoiceNumber).'","'.pSQL($tipo).'","'.(int) $api_id_queue.'","'.pSQL($api_estado_queue).'","'.date('Y-m-d H:i:s').'")'; 
            if (!Db::getInstance()->execute($sql)) 
            {
                $errorMessage = Db::getInstance()->getMsgError();
                //echo $errorMessage;
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
        $order = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_order = "'.(int) $id_order.'"');
        if ($tipo == 'abono')
        {
            $slip = Db::getInstance()->getRow('SELECT os.*,vos.verifactuEstadoRegistro, vos.estado FROM ' . _DB_PREFIX_ . 'order_slip as os LEFT JOIN ' . _DB_PREFIX_ . 'verifactu_order_slip as vos ON os.id_order_slip = vos.id_order_slip WHERE os.id_order = "'.(int) $id_order.'" ORDER BY os.id_order_slip DESC');
            $slipLines = Db::getInstance()->ExecuteS('SELECT sd.*,od.product_reference,od.tax_rate,od.product_name FROM ' . _DB_PREFIX_ . 'order_slip_detail as sd LEFT JOIN ' . _DB_PREFIX_ . 'order_detail as od ON sd.id_order_detail = od.id_order_detail WHERE sd.id_order_slip = "'.((int) $slip['id_order_slip']).'"'); 
                

            if ($slip->estado == 'pendiente') //Si el estado es pendiente evitamos que se vuelva a enviar.
            {
                $reply['response'] = 'pendiente';
                return json_encode($reply);
            }
                
        }

        $invoice = Db::getInstance()->getRow('SELECT oi.*,voi.verifactuEstadoRegistro, voi.estado FROM ' . _DB_PREFIX_ . 'order_invoice as oi LEFT JOIN ' . _DB_PREFIX_ . 'verifactu_order_invoice as voi ON oi.id_order_invoice = voi.id_order_invoice WHERE oi.id_order = "'.(int) $id_order.'" ORDER BY oi.id_order_invoice DESC');
        if ($invoice->estado == 'pendiente') //Si el estado es pendiente evitamos que se vuelva a enviar.
        {
            $reply['response'] = 'pendiente';
            return json_encode($reply);
        }

        $curl  = curl_init();

        $url   = 'https://verifactu.infoal.io/api_v2/verifactu/anulacion';

        $token = Configuration::get('VERIFACTU_API_TOKEN', null);


        // HTTP request headers
        $headers = [
            'Authorization: Bearer '.$token,
            'Content-Type: application/json',
        ];

        $data = new \stdClass();

        $InvoiceNumber = $this->getFormattedInvoiceNumber($invoice['id_order_invoice']);
        $data->InvoiceNumber = $InvoiceNumber;
        
        $dataString = json_encode($data);

        if (Configuration::get('VERIFACTU_DEBUG_MODE') == '1')
        {
            PrestaShopLogger::addLog(
                'Módulo Verifactu: Anulación - Envío a api ' . $dataString.'
                ',
                1
            );
        }

        curl_setopt_array($curl, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => 'utf-8',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
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
        if (Configuration::get('VERIFACTU_DEBUG_MODE') == '1')
        {
            PrestaShopLogger::addLog(
                'Módulo Verifactu: Anulación - Respuesta de api ' . $response.'
                ',
                1
            );
        }

        //Guardamos el campo verifactuEstadoRegistro y verifactuEstadoEnvio en base de datos si ha sido correcto
        $obj = json_decode($response);

        if ($obj->response == 'OK')
        {  
            $urlQR = $obj->urlQR;
            $api_id_queue = (int) $obj->id_queue;
            $api_estado_queue = 'pendiente';

            if ($tipo == 'abono') 
            {
                $id_order_invoice = $slip['id_order_slip'];

                //Miramos si a se habia enviado previamente para actualizar el estado o insertar uno nuevo
                $vos = Db::getInstance()->getRow('SELECT id_order_slip FROM ' . _DB_PREFIX_ . 'verifactu_order_slip WHERE id_order_slip = "'.(int) $slip['id_order_slip'].'"');
                if ($vos['id_order_slip'] != '') //Solo actualizamos, tiene que existir previamente
                {
                    $sql = 'UPDATE ' . _DB_PREFIX_ . 'verifactu_order_slip SET estado="pendiente", api_id_queue="'.(int) $api_id_queue.'" WHERE id_order_slip = "'.(int) $vos['id_order_slip'].'"';
                }
                
                
                if (!Db::getInstance()->execute($sql)) {
                    $errorMessage = Db::getInstance()->getMsgError();
                    if (Configuration::get('VERIFACTU_DEBUG_MODE') == '1')
                    {
                        PrestaShopLogger::addLog(
                            'Módulo Verifactu: Anulación - Error al insertar en verifactu_order_invoice ' . $errorMessage.'
                            ',
                            1
                        );
                    }
                }
            }
            else
            {
                $id_order_invoice = $invoice['id_order_invoice'];

                $voi = Db::getInstance()->getRow('SELECT id_order_invoice FROM ' . _DB_PREFIX_ . 'verifactu_order_invoice WHERE id_order_invoice = "'.(int) $invoice['id_order_invoice'].'"');
                if ($voi['id_order_invoice'] != '') //Solo actualizamos, tiene que existir previamente
                {
                    $sql = 'UPDATE ' . _DB_PREFIX_ . 'verifactu_order_invoice SET estado="pendiente", api_id_queue="'.(int) $api_id_queue.'" WHERE id_order_invoice = "'.(int) $voi['id_order_invoice'].'"';
                }
                

                // 4. Ejecutamos la consulta.
                if (!Db::getInstance()->execute($sql)) {
                    $errorMessage = Db::getInstance()->getMsgError();
                    if (Configuration::get('VERIFACTU_DEBUG_MODE') == '1')
                    {
                        PrestaShopLogger::addLog(
                            'Módulo Verifactu: Anulación - Error al insertar en verifactu_order_invoice ' . $errorMessage.'
                            ',
                            1
                        );
                    }
                }
            }

            //Guardamos el log
            $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'verifactu_logs (id_order_invoice,invoice_number,tipo,api_id_queue,api_estado_queue,fechahora) VALUES ("'.(int) $id_order_invoice.'","'.pSQL($InvoiceNumber).'","'.pSQL($tipo).'","'.(int) $api_id_queue.'","'.pSQL($api_estado_queue).'","'.date('Y-m-d H:i:s').'")'; 
            if (!Db::getInstance()->execute($sql)) 
            {
                $errorMessage = Db::getInstance()->getMsgError();
                //echo $errorMessage;
            }
            
            $reply['response'] = 'OK';
        }
        else
        {
            $reply['response'] = 'KO';
        }
        

        return $response;
    }

    public function checkPendingInvoices()
    {

        // NOTA: La lógica de consulta a tu API externa iría aquí.
        // Por ahora, simularemos una respuesta y actualizaremos la BD.
        
        // 1. Buscar facturas pendientes en nuestra base de datos.
        $pending_invoices = Db::getInstance()->executeS(
            'SELECT id_order_invoice,api_id_queue FROM `'._DB_PREFIX_.'verifactu_order_invoice`
             WHERE `estado` = "pendiente"'
        );

        $pending_slips = Db::getInstance()->executeS(
            'SELECT id_order_slip,api_id_queue FROM `'._DB_PREFIX_.'verifactu_order_slip`
             WHERE `estado` = "pendiente"'
        );

        $updated_count = 0;

        $data = new \stdClass();
        $queue = array();
        $inv = array();
        $sl = array();

        foreach ($pending_invoices as $p)
        {
            $api_id_queue = (int) $p['api_id_queue'];
            if ($api_id_queue != '0')
            {
                $queue[] = $p['api_id_queue'];
                //Para la comprobación luego
                $inv[] = $p['api_id_queue'];
            }
            
        }

        foreach ($pending_slips as $p)
        {
            $api_id_queue = (int) $p['api_id_queue'];
            if ($api_id_queue != '0')
            {
                $queue[] = $p['api_id_queue'];
                //para la comprobacion luego
                $sl[] = $p['api_id_queue'];
            }
        }

        $data->queue = $queue;

        if ($queue) 
        {
            $curl  = curl_init();
            $url   = 'https://verifactu.infoal.io/api_v2/verifactu/check';
            $token = Configuration::get('VERIFACTU_API_TOKEN', null);

            // HTTP request headers
            $headers = [
                'Authorization: Bearer '.$token,
                'Content-Type: application/json',
            ];

            $dataString = json_encode($data);


            if (Configuration::get('VERIFACTU_DEBUG_MODE') == '1')
            {
                PrestaShopLogger::addLog(
                    'Módulo Verifactu: Update pendientes: Envío a api ' . $dataString.'
                    ',
                    1
                );
            }

            curl_setopt_array($curl, [
                    CURLOPT_URL            => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING       => 'utf-8',
                    CURLOPT_MAXREDIRS      => 10,
                    CURLOPT_TIMEOUT        => 30,
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
            if (Configuration::get('VERIFACTU_DEBUG_MODE') == '1')
            {
                PrestaShopLogger::addLog(
                    'Módulo Verifactu: Update pendientes: Respuesta de api ' . $response.'
                    ',
                    1
                );
            }

            //Guardamos el campo verifactuEstadoRegistro y verifactuEstadoEnvio en base de datos si ha sido correcto
            $obj = json_decode($response);

            if (isset($obj))
            {
                foreach ($obj as $o)
                {
                    if (Configuration::get('VERIFACTU_DEBUG_MODE') == '1')
                    {
                        PrestaShopLogger::addLog(
                            'Procesando:'.$o->id_queue.'
                            ',
                            1
                        );
                    }

                    if (in_array($o->id_queue, $inv))
                    {
                        if ($o->estado_queue != 'pendiente' && $o->estado_queue != 'procesando')
                        {
                            //Es factura de venta
                            $guardar = false;
                            $invoice = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'verifactu_order_invoice WHERE api_id_queue = "'.(int) $o->id_queue.'"');
                            
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
                                $sql = 'UPDATE ' . _DB_PREFIX_ . 'verifactu_order_invoice SET estado = "sincronizado", verifactuEstadoRegistro = "'.pSQL($o->EstadoRegistro).'",verifactuEstadoEnvio = "'.pSQL($o->EstadoEnvio).'",verifactuCodigoErrorRegistro = "'.pSQL($o->CodigoErrorRegistro).'", verifactuDescripcionErrorRegistro = "'.pSQL($o->DescripcionErrorRegistro).'", urlQR = "'.pSQL($o->urlQR).'",anulacion = "'.(int) $anulacion.'" WHERE id_order_invoice = "'.(int) $invoice['id_order_invoice'].'"';
                                if (!Db::getInstance()->execute($sql)) 
                                {
                                    $errorMessage = Db::getInstance()->getMsgError();
                                    if (Configuration::get('VERIFACTU_DEBUG_MODE') == '1')
                                    {
                                        PrestaShopLogger::addLog(
                                            'Módulo Verifactu: Error al actualizar en verifactu_order_invoice ' . $errorMessage.'
                                            ',
                                            1
                                        );
                                    }
                                }
                                //Guardamos parámetros para el log
                                $id_order_invoice = $slip['id_order_invoice'];
                                $tipo = 'alta';
                            }
                        }
                        
                    }
                    else if (in_array($o->id_queue, $sl)) //Es de abono
                    {
                        
                        if ($o->estado_queue != 'pendiente' && $o->estado_queue != 'procesando')
                        {
                            $slip = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'verifactu_order_slip WHERE api_id_queue = "'.(int) $o->id_queue.'"');

                            $guardar = false;

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
                                $sql = 'UPDATE ' . _DB_PREFIX_ . 'verifactu_order_slip SET estado = "sincronizado", verifactuEstadoRegistro = "'.pSQL($o->EstadoRegistro).'",verifactuEstadoEnvio = "'.pSQL($o->EstadoEnvio).'",verifactuCodigoErrorRegistro = "'.pSQL($o->CodigoErrorRegistro).'", verifactuDescripcionErrorRegistro = "'.pSQL($o->DescripcionErrorRegistro).'", urlQR = "'.pSQL($o->urlQR).'",anulacion = "'.(int) $anulacion.'" WHERE id_order_slip = "'.(int) $slip['id_order_slip'].'"';
                                if (!Db::getInstance()->execute($sql)) 
                                {
                                    $errorMessage = Db::getInstance()->getMsgError();
                                    //echo $errorMessage;
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
                            $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'verifactu_reg_fact (id_reg_fact,id_order_invoice,invoice_number,tipo,verifactuEstadoRegistro,verifactuEstadoEnvio,verifactuCodigoErrorRegistro,verifactuDescripcionErrorRegistro,urlQR) VALUES ("'.(int) $o->id_reg_fact.'","'.(int) $id_order_invoice.'","'.pSQL($o->InvoiceNumber).'","'.pSQL($tipo).'","'.pSQL($o->EstadoRegistro).'","'.pSQL($o->EstadoEnvio).'","'.pSQL($o->CodigoErrorRegistro).'","'.pSQL($o->DescripcionErrorRegistro).'","'.pSQL($o->urlQR).'")'; 
                            if (!Db::getInstance()->execute($sql)) 
                            {
                                $errorMessage = Db::getInstance()->getMsgError();
                                //echo $errorMessage;
                            }

                        }

                        //Actualizamos el log
                        $sql = 'UPDATE ' . _DB_PREFIX_ . 'verifactu_logs SET api_estado_queue="'.(int) $o->estado_queue.'",verifactuEstadoRegistro="'.pSQL($o->EstadoRegistro).'",verifactuEstadoEnvio="'.pSQL($o->EstadoEnvio).'",verifactuCodigoErrorRegistro="'.pSQL($o->CodigoErrorRegistro).'",verifactuDescripcionErrorRegistro="'.pSQL($o->DescripcionErrorRegistro).'",fechahora="'.date('Y-m-d H:i:s').'" WHERE api_id_queue = "'.(int) $o->id_queue.'"';
                        if (!Db::getInstance()->execute($sql)) 
                        {
                            $errorMessage = Db::getInstance()->getMsgError();
                            //echo $errorMessage;
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
        $sql->select('oi.number, oi.date_add, o.id_lang');
        $sql->from('order_invoice', 'oi');
        $sql->leftJoin('orders', 'o', 'o.id_order = oi.id_order');
        $sql->where('oi.id_order_invoice = ' . (int)$id_order_invoice);

        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            return null;
        }

        // 3. Obtenemos las variables de configuración de PrestaShop.
        $prefix = Configuration::get('PS_INVOICE_PREFIX', (int)$result['id_lang']);
        $use_year = (int)Configuration::get('PS_INVOICE_USE_YEAR');
        $year_position = (int)Configuration::get('PS_INVOICE_YEAR_POS');

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
        $sql->select('os.date_add, o.id_lang');
        $sql->from('order_slip', 'os');
        $sql->leftJoin('orders', 'o', 'o.id_order = os.id_order');
        $sql->where('os.id_order_slip = ' . (int)$id_order_slip);

        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            return null;
        }

        // 3. Obtenemos las variables de configuración específicas para las facturas de abono.
        $prefix = Configuration::get('PS_CREDIT_SLIP_PREFIX', (int)$result['id_lang']);
        $year_position = (int)Configuration::get('PS_CREDIT_SLIP_YEAR_POS');

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