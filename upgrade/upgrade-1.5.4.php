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
 * - TODO-16: Adds `date_sent` column to `verifactu_reg_fact` table.
 *
 * NOTE: Uses INFORMATION_SCHEMA checks instead of "ADD COLUMN IF NOT EXISTS"
 * for compatibility with MySQL 5.6/5.7 (which does not support that syntax).
 */
function upgrade_module_1_5_4($module)
{
    $db     = Db::getInstance();
    $prefix = _DB_PREFIX_;

    // Helper: comprueba si una columna existe en una tabla
    $columnExists = function ($table, $column) use ($db) {
        $sql = 'SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = \'' . pSQL($table) . '\'
                  AND COLUMN_NAME  = \'' . pSQL($column) . '\'';
        return (int)$db->getValue($sql) > 0;
    };

    $success = true;

    // ---------------------------------------------------------------
    // verifactu_order_invoice: retry_count
    // ---------------------------------------------------------------
    if (!$columnExists($prefix . 'verifactu_order_invoice', 'retry_count')) {
        $sql = 'ALTER TABLE `' . $prefix . 'verifactu_order_invoice`
                ADD COLUMN `retry_count` int(11) NOT NULL DEFAULT 0';
        if (!$db->execute($sql)) {
            Verifactu::writeLog('upgrade-1.5.4: Error añadiendo retry_count a verifactu_order_invoice: ' . $db->getMsgError(), 3);
            $success = false;
        }
    }

    // ---------------------------------------------------------------
    // verifactu_order_invoice: last_retry_at
    // ---------------------------------------------------------------
    if (!$columnExists($prefix . 'verifactu_order_invoice', 'last_retry_at')) {
        $sql = 'ALTER TABLE `' . $prefix . 'verifactu_order_invoice`
                ADD COLUMN `last_retry_at` datetime DEFAULT NULL';
        if (!$db->execute($sql)) {
            Verifactu::writeLog('upgrade-1.5.4: Error añadiendo last_retry_at a verifactu_order_invoice: ' . $db->getMsgError(), 3);
            $success = false;
        }
    }

    // ---------------------------------------------------------------
    // verifactu_order_slip: retry_count
    // ---------------------------------------------------------------
    if (!$columnExists($prefix . 'verifactu_order_slip', 'retry_count')) {
        $sql = 'ALTER TABLE `' . $prefix . 'verifactu_order_slip`
                ADD COLUMN `retry_count` int(11) NOT NULL DEFAULT 0';
        if (!$db->execute($sql)) {
            Verifactu::writeLog('upgrade-1.5.4: Error añadiendo retry_count a verifactu_order_slip: ' . $db->getMsgError(), 3);
            $success = false;
        }
    }

    // ---------------------------------------------------------------
    // verifactu_order_slip: last_retry_at
    // ---------------------------------------------------------------
    if (!$columnExists($prefix . 'verifactu_order_slip', 'last_retry_at')) {
        $sql = 'ALTER TABLE `' . $prefix . 'verifactu_order_slip`
                ADD COLUMN `last_retry_at` datetime DEFAULT NULL';
        if (!$db->execute($sql)) {
            Verifactu::writeLog('upgrade-1.5.4: Error añadiendo last_retry_at a verifactu_order_slip: ' . $db->getMsgError(), 3);
            $success = false;
        }
    }

    // ---------------------------------------------------------------
    // verifactu_reg_fact: date_sent (TODO-16)
    // ---------------------------------------------------------------
    if (!$columnExists($prefix . 'verifactu_reg_fact', 'date_sent')) {
        $sql = 'ALTER TABLE `' . $prefix . 'verifactu_reg_fact`
                ADD COLUMN `date_sent` datetime DEFAULT NULL';
        if (!$db->execute($sql)) {
            Verifactu::writeLog('upgrade-1.5.4: Error añadiendo date_sent a verifactu_reg_fact: ' . $db->getMsgError(), 3);
            $success = false;
        }
    }

    if (!$success) {
        // Columnas de estado evolucionaron — no abortamos; la herramienta
        // "Verificar y Reparar BD" puede resolver columnas faltantes en caliente.
        return false;
    }

    // ---------------------------------------------------------------
    // TODO-02 fix: marcar como 'stalled' los pendientes de más de 7 días
    // Resolución puntual para tiendas con registros eternamente en 'pendiente'.
    // ---------------------------------------------------------------
    $db->execute(
        'UPDATE `' . $prefix . 'verifactu_order_invoice` voi
         INNER JOIN `' . $prefix . 'order_invoice` oi ON voi.id_order_invoice = oi.id_order_invoice
         SET voi.estado = \'stalled\',
             voi.verifactuDescripcionErrorRegistro = \'Registro expirado al actualizar a v1.5.4: llevaba más de 7 días en estado pendiente sin confirmación de la AEAT.\'
         WHERE voi.estado = \'pendiente\'
           AND oi.date_add < DATE_SUB(NOW(), INTERVAL 7 DAY)'
    );

    $db->execute(
        'UPDATE `' . $prefix . 'verifactu_order_slip` vos
         INNER JOIN `' . $prefix . 'order_slip` os ON vos.id_order_slip = os.id_order_slip
         SET vos.estado = \'stalled\',
             vos.verifactuDescripcionErrorRegistro = \'Registro expirado al actualizar a v1.5.4: llevaba más de 7 días en estado pendiente sin confirmación de la AEAT.\'
         WHERE vos.estado = \'pendiente\'
           AND os.date_add < DATE_SUB(NOW(), INTERVAL 7 DAY)'
    );

    return true;
}
