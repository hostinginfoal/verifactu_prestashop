<?php
use Verifactu\VerifactuClasses\ApiVerifactu;

class AdminVerifactuAjaxController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->ajax = true; // Muy importante para respuestas Ajax
    }

    public function displayAjaxEnviarVerifactu()
    {
        $id_order = Tools::getValue('id_order');

        $av = new ApiVerifactu();
        $response = $av->sendAltaVerifactu($id_order);

        echo $response;
    }

    public function displayAjaxCheckDNI()
    {
        $id_order = Tools::getValue('id_order');

        $av = new ApiVerifactu();
        $response = $av->checkDNI($id_order);

        echo $response;
    }

    public function displayAjaxAnularVerifactu()
    {
        $id_order = Tools::getValue('id_order');

        $av = new ApiVerifactu();
        $response = $av->sendAnulacionVerifactu($id_order);

        echo $response;
    }

    /**
     * Esta es la acción que nuestro JavaScript llamará.
     */
    public function displayAjaxCheckPendingStatus()
    {
        $av = new ApiVerifactu();
        $response = $av->checkPendingInvoices();

        echo $response;
    }
}