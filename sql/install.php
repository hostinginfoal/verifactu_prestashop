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
$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'verifactu_reg_fact` (
    `id_reg_fact` int(11) NOT NULL AUTO_INCREMENT,
    `id_order_invoice` int(11) NOT NULL,
    `verifactuEstadoEnvio` VARCHAR(100),
    `verifactuEstadoRegistro` VARCHAR(100),
    `verifactuCodigoErrorRegistro` VARCHAR(100),
    `verifactuDescripcionErrorRegistro` TEXT,
    `urlQR` VARCHAR(255),
    PRIMARY KEY  (`id_reg_fact`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'verifactu_logs` (
    `id_log` int(11) NOT NULL AUTO_INCREMENT,
    `id_order_invoice` int(11) NOT NULL,
    `verifactuEstadoEnvio` VARCHAR(100),
    `verifactuEstadoRegistro` VARCHAR(100),
    `verifactuCodigoErrorRegistro` VARCHAR(100),
    `verifactuDescripcionErrorRegistro` TEXT,
    `fechahora` DATETIME,
    PRIMARY KEY  (`id_log`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'verifactu_order_invoice` (
    `id_order_invoice` int(11) NOT NULL,
    `verifactuEstadoEnvio` VARCHAR(100),
    `verifactuEstadoRegistro` VARCHAR(100),
    `verifactuCodigoErrorRegistro` VARCHAR(100),
    `verifactuDescripcionErrorRegistro` TEXT,
    `urlQR` VARCHAR(255),
    `imgQR` TEXT,
    PRIMARY KEY  (`id_order_invoice`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
