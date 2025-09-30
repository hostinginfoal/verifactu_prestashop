<?php
/**
 * Este controlador no muestra contenido. Su única función es redirigir
 * a la página de configuración principal del módulo. Actúa como el
 * punto de entrada para el enlace del menú de administración.
 */
class AdminVerifactuCreditSlipsController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        // Redirige a la configuración del módulo, forzando la pestaña 'credit_slips'
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => $this->module->name,
            'tab_module_verifactu' => 'credit_slips', // <-- La clave está aquí
            'token' => Tools::getAdminTokenLite('AdminModules')
        ]));
    }
}