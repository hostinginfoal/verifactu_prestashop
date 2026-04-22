<?php
/**
 * InFoAL S.L.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to a Commercial License (EULA)
 * that is bundled with this package in the file LICENSE.txt.
 *
 * @author    InFoAL S.L. <hosting@infoal.com>
 * @copyright 2025 InFoAL S.L.
 * @license   Proprietary - All Rights Reserved
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Upgrade script for version 1.6.0.
 *
 * Changes:
 * - Fase 27: Creates table `verifactu_facturae` to track generated
 *   Facturae 3.2.2 electronic invoices (.xsig / .xml).
 */
function upgrade_module_1_6_0($module)
{
    $db     = Db::getInstance();
    $prefix = _DB_PREFIX_;

    // Check if table already exists
    $exists = (int)$db->getValue(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME   = \'' . pSQL($prefix . 'verifactu_facturae') . '\''
    );

    if (!$exists) {
        $sql = 'CREATE TABLE `' . $prefix . 'verifactu_facturae` (
            `id`               int(11) NOT NULL AUTO_INCREMENT,
            `id_order_invoice` int(11) DEFAULT NULL,
            `id_order_slip`    int(11) DEFAULT NULL,
            `id_shop`          int(11) NOT NULL DEFAULT 1,
            `id_facturae_api`  int(11) DEFAULT NULL,
            `invoice_number`   varchar(64) NOT NULL,
            `buyer_nif`        varchar(32) DEFAULT NULL,
            `buyer_name`       varchar(128) DEFAULT NULL,
            `total_amount`     decimal(10,2) DEFAULT NULL,
            `issue_date`       date DEFAULT NULL,
            `face_sent`        tinyint(1) NOT NULL DEFAULT 0,
            `face_estado`      varchar(32) DEFAULT \'pendiente\',
            `face_registro`    varchar(64) DEFAULT NULL,
            `face_mensaje`     varchar(255) DEFAULT NULL,
            `date_add`         datetime NOT NULL,
            PRIMARY KEY  (`id`),
            KEY `id_order_invoice` (`id_order_invoice`),
            KEY `id_order_slip` (`id_order_slip`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

        if (!$db->execute($sql)) {
            Verifactu::writeLog('upgrade-1.6.0: Error creando tabla verifactu_facturae: ' . $db->getMsgError(), 3);
            return false;
        }
    }

    return true;
}
