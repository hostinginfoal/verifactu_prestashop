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
 * Upgrade script for version 1.5.4.
 *
 * Changes:
 * - TODO-02: Adds `retry_count` and `last_retry_at` columns to
 *   `verifactu_order_invoice` and `verifactu_order_slip` tables
 *   to support exponential backoff on api_error retries.
 */
function upgrade_module_1_5_4($module)
{
    $db  = Db::getInstance();
    $prefix = _DB_PREFIX_;

    $queries = [
        // --- verifactu_order_invoice ---
        "ALTER TABLE `{$prefix}verifactu_order_invoice`
            ADD COLUMN IF NOT EXISTS `retry_count` int(11) NOT NULL DEFAULT 0,
            ADD COLUMN IF NOT EXISTS `last_retry_at` datetime DEFAULT NULL",

        // --- verifactu_order_slip ---
        "ALTER TABLE `{$prefix}verifactu_order_slip`
            ADD COLUMN IF NOT EXISTS `retry_count` int(11) NOT NULL DEFAULT 0,
            ADD COLUMN IF NOT EXISTS `last_retry_at` datetime DEFAULT NULL",

        // --- TODO-16: verifactu_reg_fact: fecha de envío del módulo ---
        "ALTER TABLE `{$prefix}verifactu_reg_fact`
            ADD COLUMN IF NOT EXISTS `date_sent` datetime DEFAULT NULL",

        // --- TODO-02 fix: marcar como 'stalled' los pendientes de más de 7 días ---
        // Resolución puntal para tiendas con registros eternamente en 'pendiente'.
        "UPDATE `{$prefix}verifactu_order_invoice` voi
            INNER JOIN `{$prefix}order_invoice` oi ON voi.id_order_invoice = oi.id_order_invoice
            SET voi.estado = 'stalled',
                voi.verifactuDescripcionErrorRegistro = 'Registro expirado al actualizar a v1.5.4: llevaba más de 7 días en estado pendiente sin confirmación de la AEAT.'
            WHERE voi.estado = 'pendiente'
              AND oi.date_add < DATE_SUB(NOW(), INTERVAL 7 DAY)",

        "UPDATE `{$prefix}verifactu_order_slip` vos
            INNER JOIN `{$prefix}order_slip` os ON vos.id_order_slip = os.id_order_slip
            SET vos.estado = 'stalled',
                vos.verifactuDescripcionErrorRegistro = 'Registro expirado al actualizar a v1.5.4: llevaba más de 7 días en estado pendiente sin confirmación de la AEAT.'
            WHERE vos.estado = 'pendiente'
              AND os.date_add < DATE_SUB(NOW(), INTERVAL 7 DAY)",
    ];

    foreach ($queries as $sql) {
        if (!$db->execute($sql)) {
            // Log the error but don't abort — partial upgrades are recoverable
            // via the "Verificar y Reparar BD" tool.
            PrestaShopLogger::addLog(
                'VeriFactu upgrade-1.5.4: Error executing SQL: ' . $sql . ' — ' . $db->getMsgError(),
                3, null, null, null, true
            );
            return false;
        }
    }

    return true;
}
