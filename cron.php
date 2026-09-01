<?php
/**
 * VeriFactu Cronjob Script
 * Permite ejecutar las tareas de sincronización de manera automática y desatendida.
 */

// Evitamos cachés y configuramos tiempos límite.
@set_time_limit(0);
@ini_set('max_execution_time', '0');

// Incluimos la configuración principal de PrestaShop.
require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');

// Verificamos el token de seguridad para el cron.
$token = Tools::getValue('token');
$savedToken = Configuration::get('VERIFACTU_CRON_TOKEN');

if (empty($savedToken) || $token !== $savedToken) {
    header('HTTP/1.0 403 Forbidden');
    die('Forbidden: Invalid cron token.');
}

// Verificamos que el módulo existe y está activo
$module = Module::getInstanceByName('verifactu');
if (!$module || !$module->active) {
    die('Error: Module VeriFactu is not active or not installed.');
}

// Requerimos las dependencias necesarias.
require_once(dirname(__FILE__).'/verifactu.php');

$total_expired = 0;
$total_updated = 0;
$total_retried = 0;

// Ejecutamos las tareas para cada tienda activa.
$shops = Shop::getShops(true, null, true);
foreach ($shops as $id_shop) {
    $api_token  = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
    $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);

    if (empty($api_token)) {
        continue; // No hay token para esta tienda, pasamos a la siguiente.
    }

    try {
        $av = new \Verifactu\VerifactuClasses\ApiVerifactu($api_token, $debug_mode, $id_shop);
        $result = $av->runBackgroundTasks('cron_desatendido');
        
        $total_expired += isset($result['expired']) ? $result['expired'] : 0;
        $total_updated += isset($result['updated']) ? $result['updated'] : 0;
        $total_retried += isset($result['retried']) ? $result['retried'] : 0;
        
    } catch (Exception $e) {
        Verifactu::writeLog('Cron Error (Shop ' . $id_shop . '): ' . $e->getMessage(), 3, $id_shop);
    }
}

// Retornamos el resultado en formato JSON.
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Cron executed successfully',
    'stats' => [
        'expired' => $total_expired,
        'updated' => $total_updated,
        'retried' => $total_retried
    ],
    'time' => date('Y-m-d H:i:s')
]);
