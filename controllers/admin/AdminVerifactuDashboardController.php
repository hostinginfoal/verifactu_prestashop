<?php
/**
 * Redirect controller para el ítem "Dashboard" del menú lateral.
 */
class AdminVerifactuDashboardController extends ModuleAdminController
{
    public function __construct()
    {
        Tools::redirectAdmin(
            Context::getContext()->link->getAdminLink('AdminModules') .
            '&configure=verifactu&tab_module_verifactu=dashboard'
        );
    }
}
