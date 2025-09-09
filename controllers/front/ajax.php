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

}
