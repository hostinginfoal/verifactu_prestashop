<?php

use Verifactu\VerifactuClasses\ApiVerifactu;

class VerifactuAjaxModuleFrontController extends ModuleFrontController 
{
	public function initContent()
      {
        $this->ajax = true;
        $this->auth = true;
        parent::initContent();
      }
      // displayAjax for FrontEnd Invoke the ajax action
      // ajaxProcess for BackEnd Invoke the ajax action

	public function ajaxProcessEnviarVerifactu()
    {
        echo json_encode('foo');//something you want to return
    }

    /*public function displayAjaxResponseVerifactu()
    {
        echo json_encode('foo');//something you want to return
    }*/

    public function displayAjaxEnviarVerifactu()
    {
        $id_order = Tools::getValue('id_order');
        die('kkkkkkk');

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
}
