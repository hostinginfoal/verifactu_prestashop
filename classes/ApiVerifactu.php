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
use Order;
use Validate;

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

        $igic_tax_ids = json_decode(Configuration::get('VERIFACTU_IGIC_TAXES', null, null, $this->id_shop), true) ?: [];
        $ipsi_tax_ids = json_decode(Configuration::get('VERIFACTU_IPSI_TAXES', null, null, $this->id_shop), true) ?: [];

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

        // 1. Comprobar OSS
        $is_oss_invoice = $this->isOrderOSS($order_data, $address);

        // 2. Inicializar las otras banderas
        $is_export_invoice = false;
        $is_b2b_intra_invoice = false;

        if (!$is_oss_invoice) {
            // 3. Si no es OSS, comprobar si es Exportación
            $is_export_invoice = $this->isExportInvoice($order_data);

            if (!$is_export_invoice) {
                // 4. Si no es Exportación, comprobar si es B2B Intracom.
                $is_b2b_intra_invoice = $this->isB2BIntraCommunity($order_data, $address);
            }
        }

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
            $inv->TotalGrossAmountBeforeTaxes = -((float) $totalTaxExcl);
            $inv->TotalTaxesWithheld = -((float) $totalTaxIncl - (float) $totalTaxExcl );
            $inv->InvoiceTotal = -((float) $totalTaxIncl);
            $inv->TotalOutstandingAmount = -((float) $totalTaxIncl);
            $inv->TotalExecutableAmount = -((float) $totalTaxIncl);
            $inv->TotalTaxOutputs = -((float) $totalTaxIncl - (float) $totalTaxExcl );
            $inv->CorrectiveCorrectionMethod  = "01";
            $inv->CorrectiveCorrectionMethodDescription  = "Factura de abono ".$slip['id_order_slip'];
            $inv->CorrectiveInvoiceNumber = $this->getFormattedInvoiceNumber($invoice['id_order_invoice']); 
            $inv->CorrectiveIssueDate = date('Y-m-d', strtotime($invoice['date_add']));

            if ($is_oss_invoice) //Para una operación ventanilla unica N2 (No Sujeta), debes usar la parte de Importe No Sujeto. El "Importe No Sujeto" de una línea OSS es el importe total de esa línea (Base + IVA del otro pais)
            {
                $inv->TotalTaxOutputs = 0;
            }

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
                $line->TaxRate = $l['tax_rate'];
                $line->TaxableBaseAmount = -((float) $l['total_price_tax_excl']);
                $line->TaxAmountTotal = -((float) $l['total_price_tax_incl'] - (float) $l['total_price_tax_excl']);
                $line->ArticleCode = $l['product_reference'];
                $lineTaxTypeCode = '01';
                $line_tax_rate = (float)$l['tax_rate'];
                $id_order_detail = (int)$l['id_order_detail'];
                $legacy_id_tax = 0;
                
                //Comprobación para calcular si es IGIC o IPSI, y a su vez el tax_rate para las versiones anteriores a la 1.7.7.0
                if ($id_order_detail) {
                    $line_tax_sql = new DbQuery();
                    $line_tax_sql->select('id_tax')->from('order_detail_tax')->where('id_order_detail = ' . $id_order_detail);
                    $line_taxes_result = Db::getInstance()->executeS($line_tax_sql);

                    if ($line_taxes_result) {
                        foreach ($line_taxes_result as $tax) {
                            $id_tax = (int)$tax['id_tax'];
                            if ($legacy_id_tax == 0) $legacy_id_tax = $id_tax;

                            if (in_array($id_tax, $igic_tax_ids)) {
                                if (!$is_oss_invoice && !$is_export_invoice && !$is_b2b_intra_invoice && Configuration::get('VERIFACTU_TERRITORIO_ESPECIAL', null, null, $this->id_shop) == 1) 
                                {
                                    $lineTaxTypeCode = '03';
                                }
                                $legacy_id_tax = $id_tax;
                                break; 
                            } elseif (in_array($id_tax, $ipsi_tax_ids)) {
                                if (!$is_oss_invoice && !$is_export_invoice && !$is_b2b_intra_invoice && Configuration::get('VERIFACTU_TERRITORIO_ESPECIAL', null, null, $this->id_shop) == 1) 
                                {
                                    $lineTaxTypeCode = '02';
                                }
                                $legacy_id_tax = $id_tax;
                            }
                        }
                    }
                }

                // LEGACY: Si tax_rate es 0 y tenemos un id_tax, buscamos el rate en la tabla 'tax' por seguridad, porqué hay versiones antiguas de prestashop que no guardan el tax_rate en la tabla order_detail
                if ($line_tax_rate == 0 && $legacy_id_tax > 0 && version_compare(_PS_VERSION_, '1.7.7.0', '<')) {
                    $sql_tax = new DbQuery();
                    $sql_tax->select('rate')->from('tax')->where('id_tax = ' . $legacy_id_tax);
                    $rate_from_tax_table = (float)Db::getInstance()->getValue($sql_tax);
                    
                    if ($rate_from_tax_table > 0) {
                        $line_tax_rate = $rate_from_tax_table;
                    }
                }

                //Calculamos lo parámetros de venta internacional
                if ($is_oss_invoice) 
                {
                    $line->OperationQualification = "N2";
                    $line->RegimeKey = "17";
                } 
                elseif ($is_export_invoice) 
                {
                    if ($line_tax_rate == 0) 
                    {
                        //$line->OperationQualification = "N1";
                        $line->RegimeKey = "02";
                        $line->ExemptOperation = "E2";
                    } 
                    else 
                    {
                        // Lo enviamos como una venta normal S1 para que coincida con la factura.
                        $line->OperationQualification = "S1";
                        $line->RegimeKey = "01";
                    }
                    
                } 
                elseif ($is_b2b_intra_invoice) 
                {
                    if ($line_tax_rate == 0) 
                    {
                        // Es B2B Intra y PrestaShop ha quitado el IVA (Correcto)
                        $line->OperationQualification = "S2";
                        $line->RegimeKey = "01";
                    } 
                    else 
                    {
                        // Es B2B Intra pero PrestaShop HA COBRADO IVA (Tienda mal configurada)
                        // Lo enviamos como una venta normal S1 para que coincida con la factura.
                        $line->OperationQualification = "S1";
                        $line->RegimeKey = "01";
                    }
                }
                else 
                {
                    // Venta Nacional
                    $line->OperationQualification = "S1"; 
                    $line->RegimeKey = "01";
                }

                $line->TaxRate = $line_tax_rate;
                $line->TaxTypeCode = $lineTaxTypeCode;

                if ($is_oss_invoice) //Para una operación ventanilla unica N2 (No Sujeta), debes usar la parte de Importe No Sujeto. El "Importe No Sujeto" de una línea OSS es el importe total de esa línea (Base + IVA del otro pais)
                {
                    $line->TaxRate = 0;
                    $line->TaxableBaseAmount = ((float) $l['total_price_tax_incl']);
                    $line->TaxAmountTotal = 0;
                }

                $seq++;

                $data->invoice->lines[] = $line;
            } 

            // Comprobamos si el pedido tiene gastos de envío. Usamos los datos de la factura para mayor precisión.
            if ((float)$slip['total_shipping_tax_excl'] > 0) {
                $shipping_line = new \stdClass();
                
                $order = new Order((int)$id_order);
                $shipping_tax_rate = 0; // Valor por defecto.

                if (Validate::isLoadedObject($order)) {
                    $shipping_tax_rate = $order->carrier_tax_rate;
                } else {
                    if ((float)$invoice['total_shipping_tax_excl'] > 0) {
                        $calculated_rate = (((float)$invoice['total_shipping_tax_incl'] / (float)$invoice['total_shipping_tax_excl']) - 1) * 100;
                        // Redondeamos al decimal más cercano, no a dos.
                        $shipping_tax_rate = round($calculated_rate, 1);
                    }
                }

                $shipping_line->SequenceNumber = $seq;
                $shipping_line->ItemDescription = 'Gastos de Envío';
                $shipping_line->Quantity = 1;
                $shipping_line->UnitPriceWithoutTax = -((float)$slip['total_shipping_tax_excl']);
                $shipping_line->TotalCost = -((float)$slip['total_shipping_tax_incl']);
                $shipping_line->GrossAmount = -((float)$slip['total_shipping_tax_incl']);
                $shipping_line->TaxTypeCode = '01'; 
                $shipping_line->ArticleCode = 'ENVIO';
                $shipping_line->TaxRate = round($shipping_tax_rate, 1);
                $shipping_line->TaxableBaseAmount = -((float)$slip['total_shipping_tax_excl']);
                $shipping_line->TaxAmountTotal = -((float)$slip['total_shipping_tax_incl'] - (float)$slip['total_shipping_tax_excl']);

                if ($is_oss_invoice) 
                {
                    $shipping_line->OperationQualification = "N2";
                    $shipping_line->RegimeKey = "17";
                } 
                elseif ($is_export_invoice) 
                {
                    if ($shipping_tax_rate == 0) 
                    {
                        //$line->OperationQualification = "N1";
                        $shipping_line->RegimeKey = "02";
                        $shipping_line->ExemptOperation = "E2";
                    } 
                    else 
                    {
                        // Lo enviamos como una venta normal S1 para que coincida con la factura.
                        $shipping_line->OperationQualification = "S1";
                        $shipping_line->RegimeKey = "01";
                    }
                    
                } 
                elseif ($is_b2b_intra_invoice) 
                {
                    if ($shipping_tax_rate == 0) 
                    {
                        // Es B2B Intra y PrestaShop ha quitado el IVA (Correcto)
                        $shipping_line->OperationQualification = "S2";
                        $shipping_line->RegimeKey = "01";
                    } 
                    else 
                    {
                        // Es B2B Intra pero PrestaShop HA COBRADO IVA (Tienda mal configurada)
                        // Lo enviamos como una venta normal S1 para que coincida con la factura.
                        $shipping_line->OperationQualification = "S1";
                        $shipping_line->RegimeKey = "01";
                    } 
                } 
                else 
                {
                    $shipping_line->OperationQualification = "S1";
                    $shipping_line->RegimeKey = "01";
                }

                if ($is_oss_invoice) //Para una operación ventanilla unica N2 (No Sujeta), debes usar la parte de Importe No Sujeto. El "Importe No Sujeto" de una línea OSS es el importe total de esa línea (Base + IVA del otro pais)
                {
                    $shipping_line->TaxRate = 0;
                    $shipping_line->TaxableBaseAmount = ((float) $slip['total_price_tax_incl']);
                    $shipping_line->TaxAmountTotal = 0;
                }
                
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
            $inv->TotalGrossAmountBeforeTaxes = $invoice['total_paid_tax_excl'];
            $inv->TotalTaxesWithheld = ((float) $invoice['total_paid_tax_incl'] - (float) $invoice['total_paid_tax_excl']);
            $inv->InvoiceTotal = ((float) $invoice['total_paid_tax_incl']);
            $inv->TotalOutstandingAmount = $invoice['total_paid_tax_incl'];
            $inv->TotalExecutableAmount = $invoice['total_paid_tax_incl'];
            $inv->TotalTaxOutputs = ((float) $invoice['total_paid_tax_incl'] - (float) $invoice['total_paid_tax_excl']);

            // Buscamos los descuentos aplicados al carrito para este pedido.
            $sql = new DbQuery();
            $sql->select('ocr.*, crl.name');
            $sql->from('order_cart_rule', 'ocr');
            $sql->leftJoin('cart_rule_lang', 'crl', 'ocr.id_cart_rule = crl.id_cart_rule AND crl.id_lang = ' . (int)$order_data['id_lang']);
            $sql->where('ocr.id_order = ' . (int)$id_order);
            $discounts = Db::getInstance()->executeS($sql);

            if ($is_oss_invoice) //Para una operación ventanilla unica N2 (No Sujeta), debes usar la parte de Importe No Sujeto. El "Importe No Sujeto" de una línea OSS es el importe total de esa línea (Base + IVA del otro pais)
            {
                $inv->TotalTaxOutputs = 0;
            }

            $data->invoice = $inv;

            $seq = 1;
            foreach ($lines as $l)
            {
                
                $line = new \stdClass();
                $line->SequenceNumber = $seq;
                $line->ItemDescription = $l['product_name'];
                $line->Quantity = $l['product_quantity'];
                $line->UnitPriceWithoutTax = $l['product_price'];
                $line->TotalCost = $l['total_price_tax_incl'];
                $line->GrossAmount = $l['total_price_tax_incl'];
                $line->ArticleCode = $l['product_reference'];
                $line->TaxRate = $l['tax_rate'];
                $line->TaxableBaseAmount = ((float) $l['total_price_tax_excl']);
                $line->TaxAmountTotal = ((float) $l['total_price_tax_incl'] - (float) $l['total_price_tax_excl']);
                $seq++;

                $lineTaxTypeCode = '01'; // Default IVA
                $line_tax_rate = (float)$l['tax_rate'];
                $id_order_detail = (int)$l['id_order_detail'];
                $legacy_id_tax = 0;

                //Comprobación para calcular si es IGIC o IPSI, y a su vez el tax_rate para las versiones anteriores a la 1.7.7.0
                $line_tax_sql = new DbQuery();
                $line_tax_sql->select('id_tax')->from('order_detail_tax')->where('id_order_detail = ' . $id_order_detail);
                $line_taxes_result = Db::getInstance()->executeS($line_tax_sql);

                if ($line_taxes_result) {
                    foreach ($line_taxes_result as $tax) {
                        $id_tax = (int)$tax['id_tax'];
                        if ($legacy_id_tax == 0) $legacy_id_tax = $id_tax;
                        if (in_array($id_tax, $igic_tax_ids)) {
                            if (!$is_oss_invoice && !$is_export_invoice && !$is_b2b_intra_invoice && Configuration::get('VERIFACTU_TERRITORIO_ESPECIAL', null, null, $this->id_shop) == 1) 
                            {
                                $lineTaxTypeCode = '03'; // IGIC
                            }
                            $legacy_id_tax = $id_tax;
                            break; 
                        } elseif (in_array($id_tax, $ipsi_tax_ids)) {
                            if (!$is_oss_invoice && !$is_export_invoice && !$is_b2b_intra_invoice && Configuration::get('VERIFACTU_TERRITORIO_ESPECIAL', null, null, $this->id_shop) == 1) 
                            {
                                $lineTaxTypeCode = '02'; // IPSI
                            }
                            $legacy_id_tax = $id_tax;
                        }
                    }
                }
                

                // LEGACY: Si tax_rate es 0 y tenemos un id_tax, buscamos el rate en la tabla 'tax' por seguridad, porqué hay versiones antiguas de prestashop que no guardan el tax_rate en la tabla order_detail
                if ($line_tax_rate == 0 && $legacy_id_tax > 0 && version_compare(_PS_VERSION_, '1.7.7.0', '<')) {
                    $sql_tax = new DbQuery();
                    $sql_tax->select('rate')->from('tax')->where('id_tax = ' . $legacy_id_tax);
                    $rate_from_tax_table = (float)Db::getInstance()->getValue($sql_tax);
                    
                    if ($rate_from_tax_table > 0) {
                        $line_tax_rate = $rate_from_tax_table;
                    }
                }

                //Calculamos los parámetros de venta internacional
                if ($is_oss_invoice) 
                {
                    $line->OperationQualification = "N2";
                    $line->RegimeKey = "17";
                } 
                elseif ($is_export_invoice) 
                {
                    if ($line_tax_rate == 0) 
                    {
                        //$line->OperationQualification = "N1";
                        $line->RegimeKey = "02";
                        $line->ExemptOperation = "E2";
                    } 
                    else 
                    {
                        // Lo enviamos como una venta normal S1 para que coincida con la factura.
                        $line->OperationQualification = "S1";
                        $line->RegimeKey = "01";
                    }
                    
                } 
                elseif ($is_b2b_intra_invoice) 
                {
                    if ($line_tax_rate == 0) 
                    {
                        // Es B2B Intra y PrestaShop ha quitado el IVA (Correcto)
                        $line->OperationQualification = "S2";
                        $line->RegimeKey = "01";
                    } 
                    else 
                    {
                        // Es B2B Intra pero PrestaShop HA COBRADO IVA (Tienda mal configurada)
                        // Lo enviamos como una venta normal S1 para que coincida con la factura.
                        $line->OperationQualification = "S1";
                        $line->RegimeKey = "01";
                    } 
                }
                else
                {
                    $line->OperationQualification = "S1";
                    $line->RegimeKey = "01";
                }

                $line->TaxRate = $line_tax_rate;
                $line->TaxTypeCode = $lineTaxTypeCode;

                if ($is_oss_invoice) //Para una operación ventanilla unica N2 (No Sujeta), debes usar la parte de Importe No Sujeto. El "Importe No Sujeto" de una línea OSS es el importe total de esa línea (Base + IVA del otro pais)
                {
                    $line->TaxRate = 0;
                    $line->TaxableBaseAmount = ((float) $l['total_price_tax_incl']);
                    $line->TaxAmountTotal = 0;
                }

                $data->invoice->lines[] = $line;
            } 

            // Comprobamos si el pedido tiene gastos de envío. Usamos los datos de la factura para mayor precisión.
            if ((float)$invoice['total_shipping_tax_excl'] > 0) 
            {
                $shipping_line = new \stdClass();
                
                $order = new Order((int)$id_order);
                $shipping_tax_rate = 0; // Valor por defecto.

                if (Validate::isLoadedObject($order)) {
                    $shipping_tax_rate = $order->carrier_tax_rate;
                } else {
                    if ((float)$invoice['total_shipping_tax_excl'] > 0) {
                        $calculated_rate = (((float)$invoice['total_shipping_tax_incl'] / (float)$invoice['total_shipping_tax_excl']) - 1) * 100;
                        // Redondeamos al decimal más cercano, no a dos.
                        $shipping_tax_rate = round($calculated_rate, 1);
                    }
                }

                $shipping_line->SequenceNumber = $seq;
                $shipping_line->ItemDescription = 'Gastos de Envío';
                $shipping_line->Quantity = 1;
                $shipping_line->UnitPriceWithoutTax = $invoice['total_shipping_tax_excl'];
                $shipping_line->TotalCost = $invoice['total_shipping_tax_incl'];
                $shipping_line->GrossAmount = $invoice['total_shipping_tax_incl'];
                $shipping_line->TaxTypeCode = (isset($line->TaxTypeCode) ? $line->TaxTypeCode : '01'); //Le asignamos el TaxTypeCode de la última linea o IVA por defecto
                $shipping_line->ArticleCode = 'ENVIO';
                $shipping_line->TaxRate = round($shipping_tax_rate, 1);
                $shipping_line->TaxableBaseAmount = (float)$invoice['total_shipping_tax_excl'];
                $shipping_line->TaxAmountTotal = (float)$invoice['total_shipping_tax_incl'] - (float)$invoice['total_shipping_tax_excl'];

                //Calculamos el tipo de impuesto IGIC IPSI
                if ($is_oss_invoice) 
                {
                    $shipping_line->OperationQualification = "N2";
                    $shipping_line->RegimeKey = "17";
                } 
                elseif ($is_export_invoice) 
                {
                    if ($shipping_tax_rate == 0) 
                    {
                        //$line->OperationQualification = "N1";
                        $shipping_line->RegimeKey = "02";
                        $shipping_line->ExemptOperation = "E2";
                    } 
                    else 
                    {
                        // Lo enviamos como una venta normal S1 para que coincida con la factura.
                        $shipping_line->OperationQualification = "S1";
                        $shipping_line->RegimeKey = "01";
                    }
                    
                } 
                elseif ($is_b2b_intra_invoice) 
                {
                    if ($shipping_tax_rate == 0) 
                    {
                        // Es B2B Intra y PrestaShop ha quitado el IVA (Correcto)
                        $shipping_line->OperationQualification = "S2";
                        $shipping_line->RegimeKey = "01";
                    } 
                    else 
                    {
                        // Es B2B Intra pero PrestaShop HA COBRADO IVA (Tienda mal configurada)
                        // Lo enviamos como una venta normal S1 para que coincida con la factura.
                        $shipping_line->OperationQualification = "S1";
                        $shipping_line->RegimeKey = "01";
                    } 

                } 
                else 
                {
                    $shipping_line->OperationQualification = "S1";
                    $shipping_line->RegimeKey = "01";
                }

                if ($is_oss_invoice) //Para una operación ventanilla unica N2 (No Sujeta), debes usar la parte de Importe No Sujeto. El "Importe No Sujeto" de una línea OSS es el importe total de esa línea (Base + IVA del otro pais)
                {
                    $shipping_line->TaxRate = 0;
                    $shipping_line->TaxableBaseAmount = ((float) $invoice['total_price_tax_incl']);
                    $shipping_line->TaxAmountTotal = 0;
                }
                
                $data->invoice->lines[] = $shipping_line;
                $seq++;
            }

            // Después de añadir los productos y el envío, añadimos los descuentos.
            if (!empty($discounts)) 
            {
                // 1. Agrupamos los totales de las líneas de producto por su tipo de IVA.
                $totals_by_tax_rate = [];
                foreach ($lines as $line) 
                {
                    $rate = (float)$line['tax_rate'];
                    if ($rate == 0 && version_compare(_PS_VERSION_, '1.7.7.0', '<')) 
                    {
                        $line_tax_sql = new DbQuery();
                        $line_tax_sql->select('t.rate')
                                     ->from('order_detail_tax', 'odt')
                                     ->leftJoin('tax', 't', 't.id_tax = odt.id_tax')
                                     ->where('odt.id_order_detail = ' . (int)$line['id_order_detail']);
                        // Usamos getValue para obtener la primera tasa, asumiendo una para esta lógica
                        $rate_from_tax_table = (float)Db::getInstance()->getValue($line_tax_sql);
                        if ($rate_from_tax_table > 0) {
                            $rate = $rate_from_tax_table;
                        }
                    }
                    if (!isset($totals_by_tax_rate[$rate])) 
                    {
                        $totals_by_tax_rate[$rate] = 0;
                    }
                    $totals_by_tax_rate[$rate] += (float)$line['total_price_tax_excl'];
                }

                // 2. Calculamos el total de productos sin IVA para poder prorratear.
                $order_total_products_tax_excl = array_sum($totals_by_tax_rate);

                // 3. Recorremos cada cupón de descuento aplicado.
                foreach ($discounts as $discount) {
                    $total_discount_tax_excl = (float)$discount['value_tax_excl'];

                    // Si el descuento total es 0, no hacemos nada.
                    if ($total_discount_tax_excl <= 0) {
                        continue;
                    }

                    // 4. Desglosamos el descuento para cada tipo de IVA.
                    foreach ($totals_by_tax_rate as $rate => $total_for_rate) {
                        
                        // Calculamos la proporción de este tipo de IVA sobre el total.
                        $proportion = ($order_total_products_tax_excl > 0) ? ($total_for_rate / $order_total_products_tax_excl) : 0;
                        
                        // Calculamos qué parte del descuento corresponde a este tipo de IVA.
                        $discount_portion_tax_excl = $total_discount_tax_excl * $proportion;
                        $discount_tax_amount = $discount_portion_tax_excl * ($rate / 100);
                        $discount_portion_tax_incl = $discount_portion_tax_excl + $discount_tax_amount;

                        // Si la porción del descuento es insignificante, la saltamos.
                        if ($discount_portion_tax_excl < 0.01) {
                            continue;
                        }

                        // Creamos una línea de descuento específica para este tipo de IVA.
                        $discount_line = new \stdClass();
                        $discount_line->SequenceNumber = $seq;
                        $discount_line->ItemDescription = 'Descuento: ' . $discount['name'] . ' (' . $rate . '%)';
                        $discount_line->Quantity = 1;
                        
                        // Todos los valores monetarios son negativos.
                        $discount_line->UnitPriceWithoutTax = -round($discount_portion_tax_excl, 2);
                        $discount_line->TotalCost = -round($discount_portion_tax_incl, 2);
                        $discount_line->GrossAmount = -round($discount_portion_tax_incl, 2);
                        $discount_line->TaxRate = $rate; // El tipo de IVA real.
                        $discount_line->TaxableBaseAmount = -round($discount_portion_tax_excl, 2);
                        $discount_line->TaxAmountTotal = -round($discount_tax_amount, 2);
                        $discount_line->TaxTypeCode = (isset($line->TaxTypeCode) ? $line->TaxTypeCode : '01'); //Le asignamos el TaxTypeCode de la última linea o IVA por defecto
                        $discount_line->ArticleCode = 'DESCUENTO';

                        //Calculamos el tipo de impuesto IGIC IPSI
                        if ($is_oss_invoice) 
                        {
                            $discount_line->OperationQualification = "N2";
                            $discount_line->RegimeKey = "17";
                        } 
                        elseif ($is_export_invoice) 
                        {
                            if ($rate == 0) 
                            {
                                //$line->OperationQualification = "N1";
                                $discount_line->RegimeKey = "02";
                                $discount_line->ExemptOperation = "E2";
                            } 
                            else 
                            {
                                // Lo enviamos como una venta normal S1 para que coincida con la factura.
                                $discount_line->OperationQualification = "S1";
                                $discount_line->RegimeKey = "01";
                            }
                            
                        } 
                        elseif ($is_b2b_intra_invoice) 
                        {
                            if ($rate == 0) 
                            {
                                // Es B2B Intra y PrestaShop ha quitado el IVA (Correcto)
                                $discount_line->OperationQualification = "S2";
                                $discount_line->RegimeKey = "01";
                            } 
                            else 
                            {
                                // Es B2B Intra pero PrestaShop HA COBRADO IVA (Tienda mal configurada)
                                // Lo enviamos como una venta normal S1 para que coincida con la factura.
                                $discount_line->OperationQualification = "S1";
                                $discount_line->RegimeKey = "01";
                            } 
                        } 
                        else 
                        {
                            $discount_line->OperationQualification = "S1";
                            $discount_line->RegimeKey = "01";
                        }

                        if ($is_oss_invoice) //Para una operación ventanilla unica N2 (No Sujeta), debes usar la parte de Importe No Sujeto. El "Importe No Sujeto" de una línea OSS es el importe total de esa línea (Base + IVA del otro pais)
                        {
                            $discount_line->TaxRate = 0;
                            $discount_line->TaxableBaseAmount = -round($discount_portion_tax_excl, 2);
                            $discount_line->TaxAmountTotal = 0;
                        }
                        
                        $data->invoice->lines[] = $discount_line;
                        $seq++;
                    }
                }
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
            $apiMode = isset($obj->apiMode) ? pSQL($obj->apiMode) : 'prod';
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
                        'apiMode' => $apiMode,
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
                        'apiMode' => $apiMode,
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
                        'apiMode' => $apiMode,
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
                        'apiMode' => $apiMode,
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
                'InvoiceNumber' => pSQL($obj->InvoiceNumber),
                'urlQR' => pSQL($obj->urlQR),
                'apiMode' => $apiMode,
                'id_shop' => (int)$this->id_shop,
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
            if (isset($obj) && $obj->error)
            {
                $reply['error'] = $obj->error;
            }
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
            $apiMode = isset($obj->apiMode) ? pSQL($obj->apiMode) : 'prod';
            $InvoiceNumber = '';

            $update_data = [
                'estado' => 'pendiente',
                'id_reg_fact' => $id_reg_fact,
                'apiMode' => $apiMode,
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
                'InvoiceNumber' => pSQL($obj->InvoiceNumber),
                'apiMode' => $apiMode,
                'id_shop' => (int)$this->id_shop,
                ];
                if (!Db::getInstance()->insert('verifactu_reg_fact', $reg_fact_data)) {
                     if ($this->debugMode) {
                        PrestaShopLogger::addLog('Módulo Verifactu: CheckPending - Error al insertar en verifactu_reg_fact: ' . Db::getInstance()->getMsgError(), 3, null, null, null, true, $this->id_shop);
                    }
                }

            }

            
            
        $reply['response'] = 'OK';
        }
        else
        {
            if (isset($obj) && $obj->error)
            {
                $reply['error'] = $obj->error;
            }
            $reply['response'] = 'KO';
        }
        
        

        return $response;
    }

    public function checkPendingInvoices()
    {

        // NOTA: La lógica de consulta a tu API externa iría aquí.
        // Por ahora, simularemos una respuesta y actualizaremos la BD.
        
        // 1. Buscar facturas pendientes
        $sql_invoices = new DbQuery();
        $sql_invoices->select('voi.id_order_invoice, voi.id_reg_fact')
            ->from('verifactu_order_invoice', 'voi')
            ->leftJoin('order_invoice', 'oi', 'voi.id_order_invoice = oi.id_order_invoice')
            ->leftJoin('orders', 'o', 'oi.id_order = o.id_order')
            ->where('voi.estado = "pendiente" AND o.id_shop = ' . (int)$this->id_shop);
        $pending_invoices = Db::getInstance()->executeS($sql_invoices);

        // 2. Buscar abonos pendientes
        $sql_slips = new DbQuery();
        $sql_slips->select('vos.id_order_slip, vos.id_reg_fact')
            ->from('verifactu_order_slip', 'vos')
            ->leftJoin('order_slip', 'os', 'vos.id_order_slip = os.id_order_slip')
            ->leftJoin('orders', 'o', 'os.id_order = o.id_order')
            ->where('vos.estado = "pendiente" AND o.id_shop = ' . (int)$this->id_shop);
        $pending_slips = Db::getInstance()->executeS($sql_slips);

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
                                /*else if ($invoice['verifactuEstadoRegistro'] == 'AceptadoConErrores') //Si está marcada como AceptadoConErrores solo modificamos si es AceptadoConErrores o Correcto
                                {
                                    if ($o->EstadoRegistro == 'Correcto' || $o->EstadoRegistro == 'AceptadoConErrores')
                                    { 
                                        $guardar = true;
                                    }
                                }*/
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
                                /*else if ($slip['verifactuEstadoRegistro'] == 'AceptadoConErrores') //Si está marcada como AceptadoConErrores solo modificamos si es AceptadoConErrores o Correcto
                                {
                                    if ($o->EstadoRegistro == 'Correcto' || $o->EstadoRegistro == 'AceptadoConErrores')
                                    { 
                                        $guardar = true;
                                    }
                                }*/
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
        $sql->innerJoin('orders', 'o', 'o.id_order = oi.id_order');
        $sql->where('oi.id_order_invoice = ' . (int)$id_order_invoice);
        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            return null;
        }

        if ($this->debugMode) {
            PrestaShopLogger::addLog(json_encode($result), 1, null, null, null, true, $this->id_shop);
        }

        $id_shop = (int)$result['id_shop'];
        $id_lang = (int)$result['id_lang'];
        $default_lang_id = (int)Configuration::get('PS_LANG_DEFAULT');

        // 3. Obtenemos las variables de configuración de PrestaShop.
        $prefix = Configuration::get('PS_INVOICE_PREFIX', $id_lang, null, $id_shop);
        $use_year = (int)Configuration::get('PS_INVOICE_USE_YEAR', null, $id_shop);
        $year_position = (int)Configuration::get('PS_INVOICE_YEAR_POS', null, $id_shop);

        // 4. Preparamos los componentes del número.
        if (strlen((string)$result['number']) >= 6)
        {
            $padded_number = $result['number'];
        }
        else
        {
            $padded_number = sprintf('%06d', $result['number']);
        }
        
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
        else
        {
            $final_invoice_number .= $padded_number;
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
    public function getFormattedCreditSlipNumber($id_order_slip)
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
        $sql->innerJoin('orders', 'o', 'o.id_order = os.id_order');
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
        if (strlen((string)$id_order_slip) >= 6)
        {
            $padded_number = $id_order_slip;
        }
        else
        {
            $padded_number = sprintf('%06d', $id_order_slip);
        }
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

    /**
     * Comprueba si un pedido cumple los criterios para ser una factura de Ventanilla Única (OSS).
     * (Versión 5 - Hardcoded con lista de ISO codes)
     *
     * @param array $order_data Los datos de la tabla 'ps_orders'
     * @param array $address Los datos de la dirección de FACTURACIÓN ('ps_address')
     * @return bool True si es una factura OSS, false en caso contrario.
     */
    private function isOrderOSS($order_data, $address)
    {
        // Los vendedores de Canarias están fuera del territorio IVA de la UE, no aplican OSS.
        if (Configuration::get('VERIFACTU_TERRITORIO_ESPECIAL', null, null, $this->id_shop) == 1) {
            return false;
        }

        // 1. Comprobar el interruptor maestro del módulo
        if (Configuration::get('VERIFACTU_USA_OSS') != 1) {
            return false;
        }

        // 2. Comprobar si es B2C (usando la dirección de facturación)
        $taxIdentificationNumber = !empty($address['vat_number']) ? $address['vat_number'] : '';
        $is_b2c = empty($taxIdentificationNumber);

        if (!$is_b2c) {
            return false; // Es B2B, nunca puede ser OSS.
        }

        // 3. Obtener el país de la tienda
        $id_shop_country = (int)Configuration::get('PS_COUNTRY_DEFAULT');

        // 4. Obtener el país de ENTREGA del pedido
        $id_delivery_country = 0;
        $sql_delivery_addr = new DbQuery();
        $sql_delivery_addr->select('id_country')
            ->from('address')
            ->where('id_address = ' . (int)$order_data['id_address_delivery']);
        
        $delivery_addr = Db::getInstance()->getRow($sql_delivery_addr);
        
        if ($delivery_addr) {
            $id_delivery_country = (int)$delivery_addr['id_country'];
        }
        
        if ($id_delivery_country == 0) {
            return false;
        }

        // 5. Comparar países usando nuestra función helper
        $shop_is_eu = $this->isCountryInEU($id_shop_country);
        $delivery_is_eu = $this->isCountryInEU($id_delivery_country);

        // La regla final
        if ($shop_is_eu && $delivery_is_eu && $id_shop_country != $id_delivery_country) {
            // Es B2C, Tienda UE -> Cliente UE (diferente país). ¡Es OSS!
            return true;
        }

        return false;
    }

    /**
     * Comprueba si un pedido es una Exportación (B2x a un país fuera de la UE).
     * (Versión 5 - Hardcoded con lista de ISO codes)
     *
     * @param array $order_data Los datos de la tabla 'ps_orders'
     * @return bool True si es una exportación, false en caso contrario.
     */
    private function isExportInvoice($order_data)
    {
        // 1. Obtener país de la tienda
        $id_shop_country = (int)Configuration::get('PS_COUNTRY_DEFAULT');

        // 2. Obtener país y ESTADO de ENTREGA
        $id_delivery_country = 0;
        $id_delivery_state = 0; // <-- AÑADIDO
        
        $sql_delivery_addr = new DbQuery();
        $sql_delivery_addr->select('id_country, id_state'); // <-- MODIFICADO
        $sql_delivery_addr->from('address');
        $sql_delivery_addr->where('id_address = ' . (int)$order_data['id_address_delivery']);
        $delivery_addr = Db::getInstance()->getRow($sql_delivery_addr);
        
        if ($delivery_addr) {
            $id_delivery_country = (int)$delivery_addr['id_country'];
            $id_delivery_state = (int)$delivery_addr['id_state']; // <-- AÑADIDO
        }
        
        if ($id_delivery_country == 0) {
            return false;
        }

        // 3. Comparar países usando nuestra función helper
        $shop_is_eu = $this->isCountryInEU($id_shop_country); // 'ES' da true
        $delivery_is_eu = $this->isCountryInEU($id_delivery_country); // 'ES' da true

        // Comprobar si el vendedor es de Territorio Especial (Canarias, Ceuta, Melilla)
        $is_special_territory_seller = (Configuration::get('VERIFACTU_TERRITORIO_ESPECIAL', null, null, $this->id_shop) == 1);

        // Lógica de exportación estándar: Vendedor UE (Península) -> Cliente No-UE
        $original_export_logic = ($shop_is_eu && !$delivery_is_eu);

        if ($is_special_territory_seller) {
            
            // Caso 1: Venta de Territorio Especial a No-UE (ej. Canarias -> EE.UU.)
            $special_to_non_eu_export = !$delivery_is_eu;

            // Caso 2: Venta de Territorio Especial a otro país UE (ej. Canarias -> Francia)
            $special_to_eu_export = ($delivery_is_eu && $id_shop_country != $id_delivery_country);
            
            // --- INICIO DE LA NUEVA LÓGICA ---
            
            // Caso 3: Venta de Territorio Especial a Península/Baleares
            $special_to_peninsula_export = false;
            // Verificamos si es una venta dentro de España (País Tienda = País Entrega)
            if ($delivery_is_eu && $id_shop_country == $id_delivery_country && $id_delivery_state > 0) 
            {
                // Es ES -> ES. Necesitamos comprobar el estado de entrega.
                if (!class_exists('\State', false)) {
                    require_once(_PS_ROOT_DIR_ . '/classes/State.php');
                }
                $delivery_state_obj = new \State($id_delivery_state);
                
                if (Validate::isLoadedObject($delivery_state_obj)) {
                    $delivery_iso = strtoupper($delivery_state_obj->iso_code);
                    
                    // Si el ISO de estado NO es de un territorio especial, es Península/Baleares
                    // (Asumimos que la tienda del vendedor sí está en un territorio especial)
                    if (!in_array($delivery_iso, ['ES-GC', 'ES-TF', 'ES-CE', 'ES-ML'])) {
                        $special_to_peninsula_export = true;
                    }
                }
            }
            // --- FIN DE LA NUEVA LÓGICA ---

            return ($special_to_eu_export || $special_to_non_eu_export || $special_to_peninsula_export);
        }
        
        // Si no es vendedor de Territorio Especial, se aplica solo la lógica original
        return $original_export_logic;
    }

    /**
     * Comprueba si un pedido es una Venta B2B Intracomunitaria.
     * (Versión 5 - Hardcoded con lista de ISO codes)
     *
     * @param array $order_data Los datos de la tabla 'ps_orders'
     * @param array $address Los datos de la dirección de FACTURACIÓN ('ps_address')
     * @return bool True si es B2B intracomunitaria, false en caso contrario.
     */
    private function isB2BIntraCommunity($order_data, $address)
    {
        // Los vendedores de Canarias están fuera del territorio IVA de la UE, no aplican B2B Intracomunitario.
        if (Configuration::get('VERIFACTU_TERRITORIO_ESPECIAL', null, null, $this->id_shop) == 1) {
            return false;
        }

        // 1. Comprobar si es B2B (TIENE NIF/VAT)
        $taxIdentificationNumber = !empty($address['vat_number']) ? $address['vat_number'] : '';
        $is_b2b = !empty($taxIdentificationNumber);

        if (!$is_b2b) {
            return false; // Es B2C, no puede ser B2B intracomunitaria
        }

        // 2. Obtener país de la tienda
        $id_shop_country = (int)Configuration::get('PS_COUNTRY_DEFAULT');

        // 3. Obtener país de ENTREGA
        $id_delivery_country = 0;
        $sql_delivery_addr = new DbQuery();
        $sql_delivery_addr->select('id_country')
            ->from('address')
            ->where('id_address = ' . (int)$order_data['id_address_delivery']);
        $delivery_addr = Db::getInstance()->getRow($sql_delivery_addr);
        if ($delivery_addr) {
            $id_delivery_country = (int)$delivery_addr['id_country'];
        }
        
        if ($id_delivery_country == 0) {
            return false;
        }

        // 4. Comparar países usando nuestra función helper
        $shop_is_eu = $this->isCountryInEU($id_shop_country);
        $delivery_is_eu = $this->isCountryInEU($id_delivery_country);

        // Lógica B2B Intracomunitaria:
        // Es B2B, Tienda en UE, Entrega en UE, y país tienda != país entrega
        return ($shop_is_eu && $delivery_is_eu && $id_shop_country != $id_delivery_country);
    }

    /**
     * Función helper para comprobar si un país está en la UE
     * basado en una lista hardcoded de códigos ISO.
     *
     * @param int $id_country El ID del país a comprobar.
     * @return bool True si es un país de la UE, false en caso contrario.
     */
    private function isCountryInEU($id_country)
    {
        // Lista hardcoded de códigos ISO 3166-1 alpha-2 de los 27 países de la UE
        // Esta lista es estática para optimizar y no redeclararla en cada llamada.
        static $eu_iso_codes = [
            'AT', // Austria
            'BE', // Bélgica
            'BG', // Bulgaria
            'CY', // Chipre
            'CZ', // República Checa
            'DE', // Alemania
            'DK', // Dinamarca
            'EE', // Estonia
            'ES', // España (Como solicitaste, para las excepciones de Canarias se añadirá lógica después)
            'FI', // Finlandia
            'FR', // Francia
            'GR', // Grecia
            'HR', // Croacia
            'HU', // Hungría
            'IE', // Irlanda
            'IT', // Italia
            'LT', // Lituania
            'LU', // Luxemburgo
            'LV', // Letonia
            'MT', // Malta
            'NL', // Países Bajos
            'PL', // Polonia
            'PT', // Portugal
            'RO', // Rumanía
            'SE', // Suecia
            'SI', // Eslovenia
            'SK', // Eslovaquia
        ];

        // Aseguramos que la clase Country esté cargada (con namespace global)
        if (!class_exists('\Country', false)) {
            require_once(_PS_ROOT_DIR_ . '/classes/Country.php');
        }

        // Usamos el namespace global '\'
        $country_obj = new \Country($id_country);

        // Si el país no se puede cargar, no es de la UE
        if (!Validate::isLoadedObject($country_obj)) {
            return false;
        }

        // Devolvemos true si el iso_code del país (en mayúsculas) está en el array
        return in_array(strtoupper($country_obj->iso_code), $eu_iso_codes, true);
    }

}