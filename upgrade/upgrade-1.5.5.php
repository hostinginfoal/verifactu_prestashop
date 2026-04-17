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
 * Upgrade script for version 1.5.5.
 *
 * Changes:
 * - Registers the 'actionCronJob' hook (PS 1.7+) for existing installations
 *   so they benefit from the native PS pseudo-cron without having to reinstall
 *   the module. The hook replaces the JS-triggered AJAX as the primary mechanism
 *   for running background tasks (retrying api_error invoices, checking pending
 *   statuses, expiring stalled records).
 * - The hookDisplayBackOfficeHeader fallback (for PS < 1.7) needs no DB change,
 *   as it only relies on ps_configuration and fires automatically.
 *
 * NOTE: No closures / anonymous functions — PHP 5.2 compatible syntax.
 */
function upgrade_module_1_5_5($module)
{
    // Register actionCronJob only for PS 1.7+.
    // On older versions the fallback in hookDisplayBackOfficeHeader takes over.
    if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
        if (!$module->registerHook('actionCronJob')) {
            // Non-fatal: log and continue. The AJAX mechanism still works as backup.
            PrestaShopLogger::addLog(
                'Verifactu upgrade 1.5.5: Could not register actionCronJob hook.',
                2, // warning
                null, 'verifactu', null, true
            );
        }
    }

    return true;
}
