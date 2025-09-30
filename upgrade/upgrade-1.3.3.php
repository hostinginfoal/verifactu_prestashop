<?php
/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
if (!defined('_PS_VERSION_')) {
    exit;
}

function addColumnIfNotExists($tableName, $columnName, $columnDefinition)
{
    $db = Db::getInstance();
    $prefix = _DB_PREFIX_;
    $dbName = _DB_NAME_; // Nombre de la base de datos de PrestaShop

    // Consulta para verificar si la columna ya existe en el INFORMATION_SCHEMA
    $sqlCheck = "SELECT COUNT(*)
                 FROM `INFORMATION_SCHEMA`.`COLUMNS`
                 WHERE `TABLE_SCHEMA` = '" . pSQL($dbName) . "'
                 AND `TABLE_NAME` = '" . pSQL($prefix . $tableName) . "'
                 AND `COLUMN_NAME` = '" . pSQL($columnName) . "'";

    // Si getValue() devuelve 0, la columna no existe.
    if ((int)$db->getValue($sqlCheck) == 0) {
        // La columna no existe, procedemos a crearla
        $sqlAlter = "ALTER TABLE `" . pSQL($prefix . $tableName) . "` ADD COLUMN `" . pSQL($columnName) . "` " . $columnDefinition;
        
        // Ejecutamos el ALTER y devolvemos el resultado.
        return $db->execute($sqlAlter);
    }

    // Si la columna ya existía, no hacemos nada y devolvemos 'true' (éxito)
    return true;
}

function upgrade_module_1_3_3($module)
{
    $sql = array();

    if (!addColumnIfNotExists('verifactu_order_slip', 'urlQR', 'VARCHAR(255) NULL AFTER `verifactuDescripcionErrorRegistro`')) {
        return false;
    }

    return true;
}
