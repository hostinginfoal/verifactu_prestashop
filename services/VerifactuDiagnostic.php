<?php
/**
 * InFoAL S.L.
 *
 * NOTICE OF LICENSE
 * Proprietary - All Rights Reserved.
 * @author    InFoAL S.L. <hosting@infoal.com>
 * @copyright 2025 InFoAL S.L.
 *
 * TODO-14: VerifactuDiagnostic
 * Responsabilidad: recopilación de datos de diagnóstico del módulo.
 * Permite generar el payload para TODO-24 (envío de diagnóstico a InFoAL)
 * y generar el ZIP descargable de fallback.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Servicio de diagnóstico del módulo VeriFactu.
 *
 * Recopila toda la información necesaria para depurar un problema:
 *  - Info del entorno (PS, PHP, extensiones)
 *  - Configuración (con token enmascarado)
 *  - Resultado de checkAndFixDatabase()
 *  - Estado AEAT (checkApiStatus)
 *  - Test de conectividad cURL
 *  - Últimos errores de la BD
 *  - Registros pendientes
 *  - Últimas entradas de ps_log
 */
class VerifactuDiagnostic
{
    /** @var Verifactu */
    private $module;

    /** @var int */
    private $id_shop;

    public function __construct($module, $id_shop = null)
    {
        $this->module  = $module;
        $this->id_shop = $id_shop ?? Shop::getContextShopID();
    }

    /**
     * Genera el payload completo de diagnóstico como array.
     * @return array
     */
    public function collect()
    {
        return [
            'info'            => $this->collectInfo(),
            'config'          => $this->collectConfig(),
            'recent_errors'   => $this->collectRecentErrors(),
            'pending_records' => $this->collectPendingRecords(),
            'debug_log'       => $this->collectDebugLog(),
            'ps_errors'       => $this->collectPsErrors(),
        ];
    }

    /**
     * Info del entorno.
     */
    private function collectInfo()
    {
        return [
            'module_version'  => $this->module->version,
            'ps_version'      => _PS_VERSION_,
            'php_version'     => PHP_VERSION,
            'php_extensions'  => [
                'curl'       => extension_loaded('curl'),
                'zip'        => extension_loaded('zip'),
                'json'       => extension_loaded('json'),
                'mbstring'   => extension_loaded('mbstring'),
            ],
            'api_mode'        => Configuration::get('VERIFACTU_API_MODE', null, null, $this->id_shop) ?: 'test',
            'id_shop'         => $this->id_shop,
            'generated_at'    => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Configuración del módulo (token enmascarado).
     */
    private function collectConfig()
    {
        $token = Configuration::get('VERIFACTU_API_TOKEN', null, null, $this->id_shop);

        return [
            'api_token'               => $token ? '***' . substr($token, -4) : '(vacío)',
            'nif_emisor'              => Configuration::get('VERIFACTU_NIF_EMISOR', null, null, $this->id_shop),
            'debug_mode'              => (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $this->id_shop),
            'log_to_file'             => (bool)Configuration::get('VERIFACTU_LOG_TO_FILE', false, null, $this->id_shop),
            'usa_oss'                 => (bool)Configuration::get('VERIFACTU_USA_OSS', false, null, $this->id_shop),
            'territorio_especial'     => (bool)Configuration::get('VERIFACTU_TERRITORIO_ESPECIAL', false, null, $this->id_shop),
            'recargo_compat'          => Configuration::get('VERIFACTU_RECARGO_COMPAT', null, null, $this->id_shop),
            'qr_hide_default'         => (bool)Configuration::get('VERIFACTU_QR_HIDE_DEFAULT', false, null, $this->id_shop),
            'qr_width'                => Configuration::get('VERIFACTU_QR_WIDTH', null, null, $this->id_shop),
            'show_anulacion_button'   => (bool)Configuration::get('VERIFACTU_SHOW_ANULACION_BUTTON', false, null, $this->id_shop),
            'lock_order_if_correct'   => (bool)Configuration::get('VERIFACTU_LOCK_ORDER_IF_CORRECT', false, null, $this->id_shop),
        ];
    }

    /**
     * Últimos 50 registros con error (Incorrecto o api_error) de los últimos 30 días.
     */
    private function collectRecentErrors()
    {
        $sql = new DbQuery();
        $sql->select('rf.id_reg_fact, rf.InvoiceNumber, rf.IssueDate, rf.EstadoRegistro, rf.CodigoErrorRegistro,
                      rf.DescripcionErrorRegistro, rf.estado_queue, rf.apiMode, rf.date_sent, rf.id_shop');
        $sql->from('verifactu_reg_fact', 'rf');
        $sql->where('(rf.EstadoRegistro = \'Incorrecto\' OR rf.estado_queue = \'api_error\')');
        $sql->where('rf.id_shop = ' . (int)$this->id_shop);
        $sql->where('rf.date_sent >= \'' . pSQL(date('Y-m-d H:i:s', strtotime('-30 days'))) . '\'');
        $sql->orderBy('rf.date_sent DESC');
        $sql->limit(50);

        return Db::getInstance()->executeS($sql) ?: [];
    }

    /**
     * Registros pendientes de resolución.
     */
    private function collectPendingRecords()
    {
        $pending = [];

        // Facturas pendientes o con error
        $sql_i = 'SELECT id_order_invoice, estado, retry_count, last_retry_at
                  FROM `' . _DB_PREFIX_ . 'verifactu_order_invoice`
                  WHERE estado IN (\'pendiente\', \'api_error\', \'failed\')
                  LIMIT 50';
        $pending['invoices'] = Db::getInstance()->executeS($sql_i) ?: [];

        // Abonos pendientes o con error
        $sql_s = 'SELECT id_order_slip, estado, retry_count, last_retry_at
                  FROM `' . _DB_PREFIX_ . 'verifactu_order_slip`
                  WHERE estado IN (\'pendiente\', \'api_error\', \'failed\')
                  LIMIT 50';
        $pending['slips'] = Db::getInstance()->executeS($sql_s) ?: [];

        return $pending;
    }

    /**
     * Últimas 500 entradas del log del módulo.
     * Intenta leer del fichero en disco primero; si no, de ps_log.
     */
    private function collectDebugLog()
    {
        $log_file = _PS_MODULE_DIR_ . 'verifactu/logs/verifactu.log';

        if (file_exists($log_file) && filesize($log_file) > 0) {
            $lines = array_slice(file($log_file), -500);
            return implode('', $lines);
        }

        // Fallback: ps_log
        $sql = 'SELECT date_add, severity, message
                FROM `' . _DB_PREFIX_ . 'log`
                WHERE message LIKE \'%VeriFactu%\' OR message LIKE \'%Veri*Factu%\'
                ORDER BY id_log DESC
                LIMIT 500';
        $rows = Db::getInstance()->executeS($sql) ?: [];

        return array_map(function ($r) {
            return '[' . $r['date_add'] . '] [' . $r['severity'] . '] ' . $r['message'];
        }, $rows);
    }

    /**
     * Últimas 50 entradas de ps_log con severity >= 3 relacionadas con el módulo.
     */
    private function collectPsErrors()
    {
        $sql = 'SELECT id_log, severity, message, date_add
                FROM `' . _DB_PREFIX_ . 'log`
                WHERE severity >= 3
                  AND (message LIKE \'%VeriFactu%\' OR message LIKE \'%Veri*Factu%\' OR message LIKE \'%verifactu%\')
                ORDER BY id_log DESC
                LIMIT 50';

        return Db::getInstance()->executeS($sql) ?: [];
    }

    /**
     * Genera un ZIP descargable con todos los datos de diagnóstico.
     * Si ZipArchive no está disponible, devuelve un JSON plano.
     *
     * @param array $payload Datos de diagnóstico ya recopilados.
     * @param string $filename Nombre del archivo descargable (sin extensión).
     */
    public function downloadAsZip(array $payload, $filename = 'verifactu-diagnostico')
    {
        $filename = $filename . '-' . date('Ymd-His');

        if (!class_exists('ZipArchive')) {
            // Fallback: JSON plano
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.json"');
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $zip_path = sys_get_temp_dir() . '/' . $filename . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            // Fallback si no se puede crear el ZIP
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.json"');
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Separamos cada sección en un fichero dentro del ZIP
        foreach ($payload as $section => $data) {
            if (is_string($data)) {
                $zip->addFromString($section . '.log', $data);
            } else {
                $zip->addFromString($section . '.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '.zip"');
        header('Content-Length: ' . filesize($zip_path));
        header('Pragma: no-cache');
        readfile($zip_path);
        @unlink($zip_path);
        exit;
    }
}
