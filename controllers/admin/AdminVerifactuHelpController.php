<?php
/**
 * Redirect controller para el ítem "Ayuda y soporte" del menú lateral.
 */
class AdminVerifactuHelpController extends ModuleAdminController
{
    public function __construct()
    {
        Tools::redirectAdmin(
            Context::getContext()->link->getAdminLink('AdminModules') .
            '&configure=verifactu&tab_module_verifactu=help'
        );
    }
}
