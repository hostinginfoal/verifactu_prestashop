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
use Configuration;

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

    public function sendAltaVerifactu($id_order)
    {
        //$id_order = Tools::getValue('id_order');
        $envioXml = false;

        $order = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_order = "'.$id_order.'"');
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

        $inv->InvoiceNumber = $invoice['number'];
        $inv->InvoiceSeriesCode = "A"; //SERIE??
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

        //$inv->InvoiceSeriesCode = "B"; //SERIE??
        //$inv->InvoiceClass = "OR"; //Factura rectificativa
        //$inv->CorrectiveCorrectionMethod  = "01"; //01 Por Diferencia  //02 Por Sustitución
        //$inv->CorrectiveCorrectionMethodDescription  = "Factura rectificativa";
        //$inv->CorrectiveInvoiceNumber  = $invoice['number'];
        //$inv->CorrectiveInvoiceSeriesCode  = "A";
        //$inv->BaseRectificada  = $inv->TotalGrossAmount;
        //$inv->CuotaRectificada  = $inv->TotalTaxOutputs;

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

            if ($guardar)
            {   
                //Guardamo o actualizamos el estado de la factura
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
                    $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'verifactu_order_invoice (id_order_invoice,verifactuEstadoRegistro,verifactuEstadoEnvio,verifactuCodigoErrorRegistro,verifactuDescripcionErrorRegistro,urlQR) VALUES ("'.$invoice['id_order_invoice'].'","'.$obj->EstadoRegistro.'","'.$obj->EstadoEnvio.'","'.$obj->CodigoErrorRegistro.'","'.$obj->DescripcionErrorRegistro.'","'.$obj->urlQR.'")'; //qr = "'.$obj->qr.'"
                    if (!Db::getInstance()->execute($sql)) 
                    {
                        $errorMessage = Db::getInstance()->getMsgError();
                        //echo $errorMessage;
                    }
                }
                
                //Guardamos el registro de facturación ??

            }

            //Guardamos el log
            $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'verifactu_logs (id_order_invoice,verifactuEstadoRegistro,verifactuEstadoEnvio,verifactuCodigoErrorRegistro,verifactuDescripcionErrorRegistro,fechahora) VALUES ("'.$invoice['id_order_invoice'].'","'.$obj->EstadoRegistro.'","'.$obj->EstadoEnvio.'","'.$obj->CodigoErrorRegistro.'","'.$obj->DescripcionErrorRegistro.'","'.date('Y-m-d H:i:s').'")'; 
            if (!Db::getInstance()->execute($sql)) 
            {
                $errorMessage = Db::getInstance()->getMsgError();
                //echo $errorMessage;
            }
            //echo $sql;
        }
                

        return $response;
    }

    public function sendAnulacionVerifactu($id_order)
    {
        //$id_order = Tools::getValue('id_order');


        $order = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_order = "'.$id_order.'"');
        $invoice = Db::getInstance()->getRow('SELECT oi.*,voi.verifactuEstadoRegistro FROM ' . _DB_PREFIX_ . 'order_invoice as oi LEFT JOIN ' . _DB_PREFIX_ . 'verifactu_order_invoice as voi ON oi.id_order_invoice = voi.id_order_invoice WHERE oi.id_order = "'.$id_order.'"');
        /*$address = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'address WHERE id_address = "'.$order['id_address_invoice'].'"');
        $prov = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'state WHERE id_state = "'.$address['id_state'].'"');
        $pais = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'country WHERE id_country = "'.$address['id_country'].'"');
        $currency = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'currency WHERE id_currency = "'.$order['id_currency'].'"');
        $lines = Db::getInstance()->ExecuteS('SELECT * FROM ' . _DB_PREFIX_ . 'order_detail WHERE id_order = "'.$id_order.'"');*/

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
        $data->InvoiceSeriesCode = "A"; //SERIE??
        
        $dataString = json_encode($data);

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