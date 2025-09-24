<?php

class AdminVerifactuDetailController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->table = 'verifactu_reg_fact';
        $this->identifier = 'id_reg_fact';
    }

    public function renderView()
    {
        // ... (tu lógica de renderizado se mantiene igual)
        // 1. Obtenemos el ID del registro de la URL.
        $id_reg_fact = (int)Tools::getValue($this->identifier);

        if (!$id_reg_fact) {
            $this->errors[] = $this->l('ID de registro no válido.');
            // Devolvemos el control al padre para que muestre el error en la página vacía.
            return parent::renderView();
        }

        // 2. Buscamos todos los datos de ese registro en la base de datos.
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from($this->table);
        $sql->where($this->identifier . ' = ' . $id_reg_fact);
        $registro = Db::getInstance()->getRow($sql);

        if (!$registro) {
            $this->errors[] = $this->l('No se ha encontrado el registro de facturación.');
            return parent::renderView();
        }

        // 3. Asignamos los datos directamente a Smarty.
        //    Pasamos el objeto 'link' para que el botón "Volver" funcione en la plantilla.
        $this->context->smarty->assign(array(
            'registro' => $registro,
            'link' => $this->context->link
        ));

        // 4. Renderizamos NUESTRA plantilla y devolvemos el HTML.
        //    Esto reemplaza la llamada a parent::renderView() y nos da control total.
        return $this->context->smarty->fetch($this->getTemplatePath() . 'view_detail.tpl');
    }

    /**
     * Sobrescribimos initToolbar para prevenir que se muestren la barra de herramientas y sus botones.
     */
    public function initToolbar()
    {
        // NO llamamos a parent::initToolbar() para evitar que se cargue la barra.
        // Y nos aseguramos de que el array de botones esté vacío.
        $this->toolbar_btn = [];
    }

    /**
     * Pequeña función de ayuda para obtener la ruta a nuestras plantillas de admin.
     * @return string
     */
    public function getTemplatePath()
    {
        return _PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/';
    }
}