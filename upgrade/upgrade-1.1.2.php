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

/**
 * This function updates your module from previous versions to the version 1.1,
 * usefull when you modify your database, or register a new hook ...
 * Don't forget to create one file per version.
 */
function upgrade_module_1_1_2($module)
{
    $sql = array();
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'verifactu_reg_fact` ADD COLUMN `invoice_number` VARCHAR(100) NULL AFTER `id_order_invoice`';
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'verifactu_reg_fact` ADD COLUMN `tipo` VARCHAR(20) NULL AFTER `invoice_number`';
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'verifactu_logs` ADD COLUMN `invoice_number` VARCHAR(100) NULL AFTER `id_order_invoice`';
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'verifactu_logs` ADD COLUMN `tipo` VARCHAR(20) NULL AFTER `invoice_number`';

    foreach ($sql as $query) {
        if (Db::getInstance()->execute($query) == false) {
            return false;
        }
    }

    return true;
}
