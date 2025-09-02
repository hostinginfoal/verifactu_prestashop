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
        //$id_order = Tools::getValue('id_order');
        $envioXml = false;
        //die('wtf');

        $order = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_order = "'.$id_order.'"');
        $invoice = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'order_invoice WHERE id_order = "'.$id_order.'"');
        $address = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'address WHERE id_address = "'.$order['id_address_invoice'].'"');
        $prov = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'state WHERE id_state = "'.$address['id_state'].'"');
        $pais = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'country WHERE id_country = "'.$address['id_country'].'"');
        $currency = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'currency WHERE id_currency = "'.$order['id_currency'].'"');
        $lines = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'order_detail WHERE id_order = "'.$id_order.'"');

        $curl  = curl_init();
        $url   = 'https://verifactu.infoal.com/index.php?option=com_facturae&format=raw&task=CDI.CHECK';
        $token = Configuration::get('VERIFACTU_API_TOKEN', null);

        // HTTP request headers
        if ($envioXml)
        {
            $headers = [
                'Authorization: Bearer '.$token,
                'Content-Type: application/xml',
            ];
        }
        else
        {
            $headers = [
                'Authorization: Bearer '.$token,
                'Content-Type: application/json',
            ];
        }
        

        $data = new \stdClass();
        $data->dni = $address['dni'];
        $data->nombre = $address['firstname'].' '.$address['lastname'];

        if ($envioXml)
        {
            $xml = new \stdClass();
            $xml->root = $data;
            $dataString = $this->xml_encode($xml);
        }
        else
        {
            $dataString = json_encode($data);
        }
        //die($dataString);

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

        //Guardamos el campo verifactuEstadoRegistro y verifactuEstadoEnvio en base de datos si ha sido correcto
        $obj = json_decode($response);

        return $response;
    }

    public function sendAltaVerifactu($id_order,$tipo='alta')
    {
        //$id_order = Tools::getValue('id_order');
        $envioXml = false;

        //AQUI, ACABAR DE PROGRAMAR LOS ENVIOS DE FACTURAS DE
        $order = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_order = "'.$id_order.'"');
        if ($tipo == 'abono')
        {
            $slip = Db::getInstance()->getRow('SELECT os.*,vos.verifactuEstadoRegistro FROM ' . _DB_PREFIX_ . 'order_slip as os LEFT JOIN ' . _DB_PREFIX_ . 'verifactu_order_slip as vos ON os.id_order_slip = vos.id_order_slip WHERE os.id_order = "'.$id_order.'"');

        }

        $invoice = Db::getInstance()->getRow('SELECT oi.*,voi.verifactuEstadoRegistro FROM ' . _DB_PREFIX_ . 'order_invoice as oi LEFT JOIN ' . _DB_PREFIX_ . 'verifactu_order_invoice as voi ON oi.id_order_invoice = voi.id_order_invoice WHERE oi.id_order = "'.$id_order.'"');
        $address = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'address WHERE id_address = "'.$order['id_address_invoice'].'"');
        $prov = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'state WHERE id_state = "'.$address['id_state'].'"');
        $pais = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'country WHERE id_country = "'.$address['id_country'].'"');
        $currency = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'currency WHERE id_currency = "'.$order['id_currency'].'"');
        $lines = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'order_detail WHERE id_order = "'.$id_order.'"');

        $curl  = curl_init();

        $url   = 'https://verifactu.infoal.com/index.php?option=com_facturae&format=raw&task=verifactu.alta';

        $token = Configuration::get('VERIFACTU_API_TOKEN', null);

        // HTTP request headers
        if ($envioXml)
        {
            $headers = [
                'Authorization: Bearer '.$token,
                'Content-Type: application/xml',
            ];
        }
        else
        {
            $headers = [
                'Authorization: Bearer '.$token,
                'Content-Type: application/json',
            ];
        }
        

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
            $inv->InvoiceNumber = $this->getFormattedCreditSlipNumber($slip['id_order_slip']);
            //$inv->InvoiceSeriesCode = Configuration::get('VERIFACTU_SERIE_FACTURA_ABONO', 'B');
            $inv->InvoiceDocumentType = ($address['dni'] != ''?"FC":"FA");
            $inv->InvoiceClass = "OR"; //Factura normal
            $inv->IssueDate = date('Y-m-d', strtotime($slip['date_add']));
            $inv->InvoiceCurrencyCode = $currency['iso_code'];
            $inv->TaxCurrencyCode = $currency['iso_code'];
            $inv->LanguageName = 'es';
            $inv->TotalGrossAmount = -$slip['total_products_tax_excl'];
            $inv->TotalGeneralDiscounts = /*$invoice['number']*/0;
            $inv->TotalGeneralSurcharges = /*$invoice['number']*/0;
            $inv->TotalGrossAmountBeforeTaxes = -$slip['total_products_tax_excl'];
            $inv->TotalTaxOutputs = -((float) $slip['total_products_tax_incl'] - (float) $slip['total_products_tax_excl']);
            $inv->TotalTaxesWithheld = -((float) $slip['total_products_tax_incl'] - (float) $slip['total_products_tax_excl']);
            $inv->InvoiceTotal = -((float) $slip['total_products_tax_incl']);
            $inv->TotalOutstandingAmount = -$slip['total_products_tax_incl'];
            $inv->TotalExecutableAmount = -$slip['total_products_tax_incl'];

            $inv->CorrectiveCorrectionMethod  = "01";
            $inv->CorrectiveCorrectionMethodDescription  = "Factura de abono ".$slip['id_order_slip'];
            $inv->CorrectiveInvoiceNumber = $this->getFormattedInvoiceNumber($invoice['id_order_invoice']); //$invoice['number'];
            //$inv->CorrectiveInvoiceSeriesCode = Configuration::get('VERIFACTU_SERIE_FACTURA', 'A');
            $inv->CorrectiveIssueDate = date('Y-m-d', strtotime($invoice['date_add']));
            //$inv->CorrectiveBaseAmount  = $inv->TotalGrossAmount;
            //$inv->CorrectiveTaxAmount  = $inv->TotalTaxOutputs;
        }
        else
        {
            $inv->InvoiceNumber = $this->getFormattedInvoiceNumber($invoice['id_order_invoice']); //$invoice['number'];
            //$inv->InvoiceSeriesCode = Configuration::get('VERIFACTU_SERIE_FACTURA', 'A');
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
        }


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

        if ($envioXml)
        {
            $xml = new \stdClass();
            $xml->root = $data;
            $dataString = $this->xml_encode($xml);
        }
        else
        {
            $dataString = json_encode($data);
        }
        //die($dataString);
        /*PrestaShopLogger::addLog(
            'Módulo Verifactu: Envío a api ' . $dataString ,
            1
        );*/

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
        PrestaShopLogger::addLog(
            'Módulo Verifactu: Respuesta de api ' . $response ,
            1
        );

        //Guardamos el campo verifactuEstadoRegistro y verifactuEstadoEnvio en base de datos si ha sido correcto
        $obj = json_decode($response);

        //die($obj->error);

        if (!$obj->error)
        {
            $guardar = false;

            if ($invoice['verifactuEstadoRegistro'] == 'Correcto') //Si está marcada ya como correcto no hacemos nada
            {

            }
            else if ($invoice['verifactuEstadoRegistro'] == 'AceptadoConErrores') //Si está marcada como AceptadoConErrores solo modificamos si es AceptadoConErrores o Correcto
            {
                /*if ($obj->EstadoRegistro == 'Correcto' || $obj->EstadoRegistro == 'AceptadoConErrores')
                { 
                    $guardar = true;
                }*/
            }
            else //Para lo demás guardamos el estado que sea
            {
                $guardar = true;
            }

            if ($guardar) //Guardamos el verifactu_order_invoice solo si no existe el registro o este ha cambiado
            {   
                //Guardamos o actualizamos el estado de la factura
                if($invoice['verifactuEstadoRegistro'] != '')
                {
                    $sql = 'UPDATE ' . _DB_PREFIX_ . 'verifactu_order_invoice SET verifactuEstadoRegistro = "'.$obj->EstadoRegistro.'",verifactuEstadoEnvio = "'.$obj->EstadoEnvio.'",verifactuCodigoErrorRegistro = "'.$obj->CodigoErrorRegistro.'", verifactuDescripcionErrorRegistro = "'.$obj->DescripcionErrorRegistro.'", urlQR = "'.$obj->urlQR.'" WHERE id_order_invoice = "'.$invoice['id_order_invoice'].'"';
                    if (!Db::getInstance()->execute($sql)) 
                    {
                        $errorMessage = Db::getInstance()->getMsgError();
                        //echo $errorMessage;
                    }
                }
                else
                {
                    $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'verifactu_order_invoice (id_order_invoice,verifactuEstadoRegistro,verifactuEstadoEnvio,verifactuCodigoErrorRegistro,verifactuDescripcionErrorRegistro,urlQR) VALUES ("'.$invoice['id_order_invoice'].'","'.$obj->EstadoRegistro.'","'.$obj->EstadoEnvio.'","'.$obj->CodigoErrorRegistro.'","'.$obj->DescripcionErrorRegistro.'","'.$obj->urlQR.'")'; 
                    if (!Db::getInstance()->execute($sql)) 
                    {
                        $errorMessage = Db::getInstance()->getMsgError();
                        //echo $errorMessage;
                    }
                }
            }


            if (isset($obj->id_reg_fact)) //Si se ha guardado un registro de facturación, del tipo que sea
            {
                $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'verifactu_reg_fact (id_reg_fact,id_order_invoice,verifactuEstadoRegistro,verifactuEstadoEnvio,verifactuCodigoErrorRegistro,verifactuDescripcionErrorRegistro,urlQR) VALUES ("'.$obj->id_reg_fact.'","'.$invoice['id_order_invoice'].'","'.$obj->EstadoRegistro.'","'.$obj->EstadoEnvio.'","'.$obj->CodigoErrorRegistro.'","'.$obj->DescripcionErrorRegistro.'","'.$obj->urlQR.'")'; 
                if (!Db::getInstance()->execute($sql)) 
                {
                    $errorMessage = Db::getInstance()->getMsgError();
                    //echo $errorMessage;
                }
            }
            
        }

        //Guardamos el log
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'verifactu_logs (id_order_invoice,verifactuEstadoRegistro,verifactuEstadoEnvio,verifactuCodigoErrorRegistro,verifactuDescripcionErrorRegistro,fechahora) VALUES ("'.$invoice['id_order_invoice'].'","'.$obj->EstadoRegistro.'","'.$obj->EstadoEnvio.'","'.$obj->CodigoErrorRegistro.'","'.$obj->DescripcionErrorRegistro.'","'.date('Y-m-d H:i:s').'")'; 
        if (!Db::getInstance()->execute($sql)) 
        {
            $errorMessage = Db::getInstance()->getMsgError();
            //echo $errorMessage;
        }
        //echo $sql;
                

        return $response;
    }

    public function sendAnulacionVerifactu($id_order)
    {
        //$id_order = Tools::getValue('id_order');


        $order = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_order = "'.$id_order.'"');
        $invoice = Db::getInstance()->getRow('SELECT oi.*,voi.verifactuEstadoRegistro FROM ' . _DB_PREFIX_ . 'order_invoice as oi LEFT JOIN ' . _DB_PREFIX_ . 'verifactu_order_invoice as voi ON oi.id_order_invoice = voi.id_order_invoice WHERE oi.id_order = "'.$id_order.'"');

        $curl  = curl_init();

        $url   = 'https://verifactu.infoal.com/index.php?option=com_facturae&format=raw&task=verifactu.anulacion';

        $token = Configuration::get('VERIFACTU_API_TOKEN', null);


        // HTTP request headers
        $headers = [
            'Authorization: Bearer '.$token,
            'Content-Type: application/json',
        ];

        $data = new \stdClass();

        $data->InvoiceNumber = $invoice['number'];
        $data->InvoiceSeriesCode = Configuration::get('VERIFACTU_SERIE_FACTURA', 'A');
        
        $dataString = json_encode($data);

        //die($dataString);

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

        //Guardamos el campo verifactuEstadoRegistro y verifactuEstadoEnvio en base de datos si ha sido correcto
        $obj = json_decode($response);

        /*if ($invoice['verifactuEstadoRegistro'] == 'Correcto') //Si está marcada ya como correcto no hacemos nada
        {

        }
        else if ($invoice['verifactuEstadoRegistro'] == 'AceptadoConErrores') //Si está marcada como AceptadoConErrores solo modificamos si es AceptadoConErrores o Correcto
        {
            if ($obj->EstadoRegistro == 'Correcto' || $obj->EstadoRegistro == 'AceptadoConErrores')
            {
                $sql = 'UPDATE ' . _DB_PREFIX_ . 'order_invoice SET verifactuEstadoRegistro = "'.$obj->EstadoRegistro.'",verifactuEstadoEnvio = "'.$obj->EstadoEnvio.'",verifactuCodigoErrorRegistro = "'.$obj->CodigoErrorRegistro.'", verifactuDescripcionErrorRegistro = "'.$obj->DescripcionErrorRegistro.'", urlQR = "'.$obj->urlQR.'", qr = "'.$obj->qr.'" WHERE id_order_invoice = "'.$invoice['id_order_invoice'].'"';
                $result = Db::getInstance()->execute($sql);
            }
        }
        else //Para lo demás guardamos el estado que sea
        {
            $sql = 'UPDATE ' . _DB_PREFIX_ . 'order_invoice SET verifactuEstadoRegistro = "'.$obj->EstadoRegistro.'",verifactuEstadoEnvio = "'.$obj->EstadoEnvio.'",verifactuCodigoErrorRegistro = "'.$obj->CodigoErrorRegistro.'", verifactuDescripcionErrorRegistro = "'.$obj->DescripcionErrorRegistro.'", urlQR = "'.$obj->urlQR.'", qr = "'.$obj->qr.'" WHERE id_order_invoice = "'.$invoice['id_order_invoice'].'"';
                $result = Db::getInstance()->execute($sql);
        }*/
        

        return $response;
    }

    /**
     * Genera el número de factura completo y formateado tal como lo hace PrestaShop,
     * incluyendo el prefijo y el año si está configurado.
     *
     * @param int $id_order_invoice El ID de la factura (de la tabla ps_order_invoice).
     * @return string|null El número de factura formateado o null si no se encuentra.
     */
    private function getFormattedInvoiceNumber($id_order_invoice)
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
        $year_position = (int)Configuration::get('PS_INVOICE_YEAR_POS');

        // 4. Preparamos los componentes del número.
        $padded_number = sprintf('%06d', $result['number']);
        $year = date('Y', strtotime($result['date_add']));
        
        $final_invoice_number = $prefix;

        // 5. Construimos el número final basándonos en la configuración del año.
        switch ($year_position) {
            case 1: // Año antes del número
                $final_invoice_number .= $year . $padded_number;
                break;
            case 2: // Año después del número
                $final_invoice_number .= $padded_number . $year;
                break;
            case 0: // Sin año
            default:
                $final_invoice_number .= $padded_number;
                break;
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

    private function xml_encode($mixed, $domElement=null, $DOMDocument=null) 
    {
        if (is_null($DOMDocument)) {
            $DOMDocument =new \DOMDocument;
            $DOMDocument->formatOutput = true;
            $this->xml_encode($mixed, $DOMDocument, $DOMDocument);
            return $DOMDocument->saveXML();
        }
        else {
            // To cope with embedded objects 
            if (is_object($mixed)) {
              $mixed = get_object_vars($mixed);
            }
            if (is_array($mixed)) {
                foreach ($mixed as $index => $mixedElement) {

                    if (is_int($index)) {
                        if ($index === 0) {
                            $node = $domElement;
                        }
                        else {
                            $node = $DOMDocument->createElement($domElement->tagName);
                            $domElement->parentNode->appendChild($node);
                        }
                    }
                    else if ($mixedElement === '') {
                        continue; // Salta a la siguiente iteración
                    }
                    else {
                        $plural = $DOMDocument->createElement($index);
                        $domElement->appendChild($plural);
                        $node = $plural;
                        /*if (!(rtrim($index, 's') === $index)) {
                            $singular = $DOMDocument->createElement(rtrim($index, 's'));
                            $plural->appendChild($singular);
                            $node = $singular;
                        }*/
                    }

                    $this->xml_encode($mixedElement, $node, $DOMDocument);
                }
            }
            else {
                $mixed = is_bool($mixed) ? ($mixed ? 'true' : 'false') : $mixed;
                $domElement->appendChild($DOMDocument->createTextNode($mixed));
            }
        }
    }
}