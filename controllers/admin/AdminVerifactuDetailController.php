<?php

class AdminVerifactuDetailController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->table = 'verifactu_reg_fact';
        $this->identifier = 'id_reg_fact';
    }

    public function renderView()
    {
        $id_reg_fact = (int)Tools::getValue($this->identifier);

        if (!$id_reg_fact) {
            $this->errors[] = $this->l('ID de registro no válido.');
            return parent::renderView();
        }

        // Cargamos todos los datos del registro de facturación
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from($this->table);
        $sql->where($this->identifier . ' = ' . $id_reg_fact);
        $registro = Db::getInstance()->getRow($sql);

        if (!$registro) {
            $this->errors[] = $this->l('No se ha encontrado el registro de facturación.');
            return parent::renderView();
        }

        // TODO-10: Datos agrupados para la vista mejorada

        $info_factura = [
            'Número de Factura'        => $registro['InvoiceNumber'],
            'Fecha de Emisión'         => $registro['IssueDate'],
            'Tipo de Factura'          => $registro['TipoFactura'],
            'Tipo de Operación'        => $registro['TipoOperacion'],
            'Total Factura'            => $registro['InvoiceTotal'] . ' €',
            'Total Impuestos'          => $registro['TotalTaxOutputs'] . ' €',
            'Simplificada (Art72/73)'  => $registro['FacturaSimplificadaArt7273'],
            'Sin Identif. (Art61d)'    => $registro['FacturaSinIdentifDestinatarioArt61d'],
            'Macrodato'                => $registro['Macrodato'],
            'Cupón'                    => $registro['Cupon'],
            'Calificación Operación'   => $registro['CalificacionOperacion'],
        ];

        $info_destinatario = [
            'Nombre/Razón Social' => $registro['BuyerName'],
            'Razón Social Corp.'  => $registro['BuyerCorporateName'],
            'NIF'                 => $registro['BuyerTaxIdentificationNumber'],
            'País'                => $registro['BuyerCountryCode'],
            'Tipo ID Otro'        => $registro['IDOtroIDType'],
            'ID Otro'             => $registro['IDOtroID'],
        ];

        $info_rectificativa = null;
        if (!empty($registro['TipoRectificativa'])) {
            $info_rectificativa = [
                'Tipo Rectificativa'   => $registro['TipoRectificativa'],
                'Nº Factura Corregida' => $registro['CorrectiveInvoiceNumber'],
                'Serie Corregida'      => $registro['CorrectiveInvoiceSeriesCode'],
                'Fecha Corregida'      => $registro['CorrectiveIssueDate'],
                'Base Corregida'       => $registro['CorrectiveBaseAmount'] . ' €',
                'Impuesto Corregido'   => $registro['CorrectiveTaxAmount'] . ' €',
            ];
        }

        $info_estado = [
            'Estado Envío'      => $registro['EstadoEnvio'],
            'Estado Registro'   => $registro['EstadoRegistro'],
            'Código Error'      => $registro['CodigoErrorRegistro'],
            'Descripción Error' => $registro['DescripcionErrorRegistro'],
            'Modo API'          => $registro['apiMode'],
            'Fecha Registro'    => $registro['fechaHoraRegistro'],
            'FechaHoraHuso'     => $registro['FechaHoraHusoGenRegistro'],
        ];

        // TODO-10: Datos SIF
        $info_sif = [
            'Razón Social SIF'   => $registro['SIFNombreRazon'],
            'NIF SIF'            => $registro['SIFNIF'],
            'Nombre SIF'         => $registro['SIFNombreSIF'],
            'ID SIF'             => $registro['SIFIdSIF'],
            'Versión'            => $registro['SIFVersion'],
            'Nº Instalación'     => $registro['SIFNumeroInstalacion'],
            'Uso Solo VF'        => $registro['SIFTipoUsoPosibleSoloVerifactu'],
            'Uso Multi OT'       => $registro['SIFTipoUsoPosibleMultiOT'],
            'Indicador Multi OT' => $registro['SIFIndicadorMultiplesOT'],
        ];

        // TODO-10: Hash y cadena de encadenamiento
        $info_hash = [
            'Hash'          => $registro['hash'],
            'Hash Anterior' => $registro['AnteriorHash'],
            'Cadena firmada'=> $registro['cadena'],
        ];

        $info_empresa = [
            'Nombre/Razón Social' => $registro['EmpresaNombreRazon'],
            'NIF'                 => $registro['EmpresaNIF'],
        ];

        $id_shop    = (int)$this->context->shop->id;
        $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);

        $this->context->smarty->assign([
            'registro'           => $registro,
            'info_factura'       => $info_factura,
            'info_destinatario'  => $info_destinatario,
            'info_rectificativa' => $info_rectificativa,
            'info_estado'        => $info_estado,
            'info_sif'           => $info_sif,
            'info_hash'          => $info_hash,
            'info_empresa'       => $info_empresa,
            'debug_mode'         => $debug_mode,
            'link'               => $this->context->link,
        ]);

        return $this->context->smarty->fetch($this->getTemplatePath() . 'view_detail.tpl');
    }

    /**
     * Sobrescribimos initToolbar para prevenir que se muestren la barra de herramientas y sus botones.
     */
    public function initToolbar()
    {
        $this->toolbar_btn = [];
    }

    /**
     * Pequeña función de ayuda para obtener la ruta a nuestras plantillas de admin.
     * @return string
     */
    public function getTemplatePath()
    {
        return _PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/';
    }
}