<?php
/**
 * InFoAL S.L.
 *
 * NOTICE OF LICENSE
 * Proprietary - All Rights Reserved.
 * @author    InFoAL S.L. <hosting@infoal.com>
 * @copyright 2025 InFoAL S.L.
 *
 * TODO-14: VerifactuListHelper
 * Responsabilidad: construcción de los $fields_list para HelperList de cada pestaña.
 * Extraído de verifactu.php para reducir su tamaño.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Helper que devuelve la definición de columnas ($fields_list) para cada listado.
 *
 * Uso:
 *   require_once _PS_MODULE_DIR_ . 'verifactu/services/VerifactuListHelper.php';
 *   $fieldsList = VerifactuListHelper::getSalesInvoicesFieldsList($module);
 */
class VerifactuListHelper
{
    /**
     * Devuelve la definición de columnas para el listado de facturas de venta.
     *
     * @param Verifactu $module Instancia del módulo (para $module->l()).
     * @return array
     */
    public static function getSalesInvoicesFieldsList($module)
    {
        return [
            'id_order_invoice' => [
                'title'  => $module->l('ID Factura'),
                'filter' => true,
                'class'  => 'fixed-width-xs',
            ],
            'number' => [
                'title'  => $module->l('Nº Factura'),
                'filter' => true,
            ],
            'customer' => [
                'title'  => $module->l('Cliente'),
                'filter' => true,
            ],
            'total_paid_tax_incl' => [
                'title'       => $module->l('Importe'),
                'filter'      => true,
                'type'        => 'price',
                'currency'    => true,
                'class'       => 'fixed-width-sm',
            ],
            'estado' => [
                'title'  => $module->l('Estado Módulo'),
                'filter' => true,
            ],
            'verifactuEstadoRegistro' => [
                'title'   => $module->l('Estado AEAT'),
                'filter'  => true,
                'callback' => 'printEstadoRegistro',
            ],
            'TipoFactura' => [
                'title'  => $module->l('Tipo'),
                'filter' => true,
                'class'  => 'fixed-width-xs',
            ],
            'anulacion' => [
                'title'    => $module->l('Anulada'),
                'callback' => 'printAnulacionTick',
                'class'    => 'fixed-width-xs text-center',
            ],
            'apiMode' => [
                'title'  => $module->l('Modo API'),
                'filter' => true,
                'class'  => 'fixed-width-xs',
            ],
            'list_actions' => [
                'title'    => $module->l('Acciones'),
                'callback' => 'printListActions',
                'class'    => 'fixed-width-sm',
            ],
        ];
    }

    /**
     * Devuelve la definición de columnas para el listado de facturas por abono.
     *
     * @param Verifactu $module
     * @return array
     */
    public static function getCreditSlipsFieldsList($module)
    {
        return [
            'id_order_slip' => [
                'title'  => $module->l('ID Abono'),
                'filter' => true,
                'class'  => 'fixed-width-xs',
            ],
            'number' => [
                'title'  => $module->l('Nº Abono'),
                'filter' => true,
            ],
            'customer' => [
                'title'  => $module->l('Cliente'),
                'filter' => true,
            ],
            'estado' => [
                'title'  => $module->l('Estado Módulo'),
                'filter' => true,
            ],
            'verifactuEstadoRegistro' => [
                'title'    => $module->l('Estado AEAT'),
                'filter'   => true,
                'callback' => 'printEstadoRegistro',
            ],
            'TipoFactura' => [
                'title'  => $module->l('Tipo'),
                'filter' => true,
                'class'  => 'fixed-width-xs',
            ],
            'anulacion' => [
                'title'    => $module->l('Anulada'),
                'callback' => 'printAnulacionTick',
                'class'    => 'fixed-width-xs text-center',
            ],
            'apiMode' => [
                'title'  => $module->l('Modo API'),
                'filter' => true,
                'class'  => 'fixed-width-xs',
            ],
            'list_actions' => [
                'title'    => $module->l('Acciones'),
                'callback' => 'printListActions',
                'class'    => 'fixed-width-sm',
            ],
        ];
    }

    /**
     * Devuelve la definición de columnas para el listado de registros de facturación.
     *
     * @param Verifactu $module
     * @return array
     */
    public static function getRegFactsFieldsList($module)
    {
        return [
            'id_reg_fact' => [
                'title'  => $module->l('ID'),
                'filter' => true,
                'class'  => 'fixed-width-xs',
            ],
            'InvoiceNumber' => [
                'title'  => $module->l('Nº Factura'),
                'filter' => true,
            ],
            'IssueDate' => [
                'title' => $module->l('Fecha'),
                'type'  => 'date',
            ],
            'tipo' => [
                'title'  => $module->l('Tipo'),
                'filter' => true,
                'class'  => 'fixed-width-xs',
            ],
            'EstadoRegistro' => [
                'title'    => $module->l('Estado AEAT'),
                'filter'   => true,
                'callback' => 'printEstadoRegistro',
            ],
            'InvoiceTotal' => [
                'title'    => $module->l('Total'),
                'type'     => 'price',
                'currency' => true,
                'class'    => 'fixed-width-sm',
            ],
            'apiMode' => [
                'title'  => $module->l('Modo API'),
                'filter' => true,
                'class'  => 'fixed-width-xs',
            ],
            'date_sent' => [
                'title' => $module->l('Fecha Envío'),
                'type'  => 'datetime',
            ],
            'list_actions' => [
                'title'    => $module->l('Acciones'),
                'callback' => 'printListActions',
                'class'    => 'fixed-width-sm',
            ],
        ];
    }
}
