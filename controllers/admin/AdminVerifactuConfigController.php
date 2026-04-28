<?php
/**
 * Redirect controller para el ítem "Configuración" del menú lateral.
 */
class AdminVerifactuConfigController extends ModuleAdminController
{
    public function __construct()
    {
        Tools::redirectAdmin(
            Context::getContext()->link->getAdminLink('AdminModules') .
            '&configure=verifactu&tab_module_verifactu=configure'
        );
    }
}
