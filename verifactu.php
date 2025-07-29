<?php
/**
* InFoAL S.L.
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to hosting@infoal.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
* @author    InFoAL S.L. <hosting@infoal.com>
* @copyright InFoAL S.L.
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* International Registered Trademark & Property of InFoAL S.L.
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

use PrestaShop\PrestaShop\Core\Grid\Column\Type\Employee\EmployeeNameWithAvatarColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
//use PrestaShopBundle\Form\Admin\Type\YesAndNoChoiceType;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollection;
use PrestaShopBundle\Form\Admin\Type\SearchAndResetType;
use PrestaShopBundle\Form\Admin\Type\YesAndNoChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Verifactu\VerifactuClasses\ApiVerifactu;
use PrestaShopLogger;

class Verifactu extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'verifactu';
        $this->tab = 'billing_invoicing';
        $this->version = '1.0.3';
        $this->author = 'InFoAL S.L.';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('VeriFactu');
        $this->description = $this->l('Envía los registros de facturación automáticamente al sistema Veri*Factu');

        $this->confirmUninstall = $this->l('Seguro que quieres desinstalar el módulo?');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        //Configuration::updateValue('VERIFACTU_LIVE_MODE', true);

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() 
            //&& $this->installTabs()
            //&& $this->registerHook('displayAdminOrderTabContent')
            //&& $this->registerHook('displayAdminOrderMain')
            && $this->registerHook('displayAdminOrderSide')
            //&& $this->registerHook('displayAdminOrderTop')
            && $this->registerHook('actionAdminControllerSetMedia')
            //&& $this->registerHook('actionValidateOrder')
            //Antiguo
            //&& $this->registerHook('actionAdminOrdersListingFieldsModifier')
            //Synfony
            && $this->registerHook('actionOrderGridDefinitionModifier')
            && $this->registerHook('actionOrderGridQueryBuilderModifier')
            && $this->registerHook('actionSetInvoice')
            //&& $this->registerHook('displayAdminOrderTabLink')
            //&& $this->registerHook('displayAdminProductsMainStepRightColumnBottom')
            //&& $this->registerHook('actionProductSave')
            ;
    }

    public function uninstall()
    {
        //Configuration::deleteByName('VERIFACTU_LIVE_MODE');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $output = '';

        if (((bool)Tools::isSubmit('submitVerifactuModule')) == true) {
            $this->postProcess();
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $tab = Tools::getValue('tab_module_verifactu', 'configure');

        $current_url = $this->context->link->getAdminLink('AdminModules', true) .
                       '&configure=' . $this->name .
                       '&tab_module=' . $this->tab .
                       '&module_name=' . $this->name;

        $this->context->smarty->assign(array(
            'module_name' => $this->name,
            'active_tab' => $tab,
            'current' => $current_url
        ));
        
        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        if ($tab == 'configure') {
            $output .= $this->renderForm();
        } elseif ($tab == 'invoices') {
            $output .= $this->renderInvoicesList();
        } elseif ($tab == 'reg_facts') {
            $output .= $this->renderList();
        } elseif ($tab == 'logs') {
            $output .= $this->renderLogsList();
        }

        return $output;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitVerifactuModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $series_options = [];

        // Añadimos las letras de la A a la Z
        foreach (range('A', 'Z') as $letter) {
            $series_options[] = ['id_option' => $letter, 'name' => $letter];
        }

        // Añadimos los números del 0 al 9
        foreach (range(0, 9) as $number) {
            $series_options[] = ['id_option' => $number, 'name' => $number];
        }

        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Configuración del módulo'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Entorno Real'),
                        'name' => 'VERIFACTU_ENTORNO_REAL',
                        'is_bool' => true,
                        'desc' => $this->l('Envía los registros de facturación al entorno real o al entorno de pruebas de Veri*Factu (Por el momento solo se pueden enviar registros al entorno de pruebas ya que todavía no existe el entorno real de Veri*Factu)'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Activado')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Desactivado')
                            )
                        ),
                        'disabled' => true,
                    ),
                    array(
                        'col' => 8,
                        'type' => 'text',
                        'prefix' => '',
                        'desc' => $this->l('Token de InFoAL Veri*Factu API (Si no dispones de una clave de API, solicita una gratuïta en https://verifactu.infoal.com'),
                        'name' => 'VERIFACTU_API_TOKEN',
                        'label' => $this->l('InFoAL Veri*Factu API Token'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Envío automático a Veri*Factu'),
                        'name' => 'VERIFACTU_LIVE_SEND',
                        'is_bool' => true,
                        'desc' => $this->l('Activa esta opción si quieres que los registros de facturación se envíen automáticamente a Veri*Factu en el momento que se generen las facturas de venta.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Activado')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Desactivado')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '',
                        'desc' => $this->l('Identificador del punto de emisión del registro de facturación (En el caso de tener más de un punto de emisión: Ej: ecommerce1, tpv1, tpv2, etc...'),
                        'name' => 'VERIFACTU_NUMERO_INSTALACION',
                        'label' => $this->l('Id de terminal'),
                    ),
                    array(
                        'col' => 1,
                        'type' => 'select',
                        'desc' => $this->l('(Ej:A,B,C,X)'),
                        'name' => 'VERIFACTU_SERIE_FACTURA',
                        'label' => $this->l('Serie Factura Alta'),
                        'options' => array(
                            'query' => $series_options, // El array que creamos arriba
                            'id' => 'id_option',        // La clave para el 'value' del option
                            'name' => 'name',           // La clave para el texto visible del option
                        ),
                    ),
                    array(
                        'col' => 1,
                        'type' => 'select',
                        'desc' => $this->l('Tiene que ser diferente que la serie de Factura de Alta (Ej:A,B,C,X)'),
                        'name' => 'VERIFACTU_SERIE_FACTURA_ABONO',
                        'label' => $this->l('Serie Factura Abono'),
                        'options' => array(
                            'query' => $series_options, // El array que creamos arriba
                            'id' => 'id_option',        // La clave para el 'value' del option
                            'name' => 'name',           // La clave para el texto visible del option
                        ),
                    ),


                ),
                'submit' => array(
                    'title' => $this->l('Guardar'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'VERIFACTU_ENTORNO_REAL' => Configuration::get('VERIFACTU_ENTORNO_REAL', false),
            'VERIFACTU_API_TOKEN' => Configuration::get('VERIFACTU_API_TOKEN', null),
            'VERIFACTU_NUMERO_INSTALACION' => Configuration::get('VERIFACTU_NUMERO_INSTALACION', '1'),
            'VERIFACTU_SERIE_FACTURA' => Configuration::get('VERIFACTU_SERIE_FACTURA', 'A'),
            'VERIFACTU_SERIE_FACTURA_ABONO' => Configuration::get('VERIFACTU_SERIE_FACTURA_ABONO', 'B'),
            //'VERIFACTU_ACCOUNT_PASSWORD' => Configuration::get('VERIFACTU_ACCOUNT_PASSWORD', null),
            'VERIFACTU_LIVE_SEND' => Configuration::get('VERIFACTU_LIVE_SEND', true),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    //Listado estado facturas----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    public function renderInvoicesList()
    {
        $fields_list = array(
            'id_reg_fact' => array('title' => $this->l('ID'),'type' => 'number'),
            'id_order_invoice' => array('title' => $this->l('ID Factura'), 'type' => 'number'),
            'verifactuEstadoEnvio' => array('title' => $this->l('Estado Envío')),
            'verifactuEstadoRegistro' => array('title' => $this->l('Estado Registro')),
            'verifactuCodigoErrorRegistro' => array('title' => $this->l('Código Error')),
            'verifactuDescripcionErrorRegistro' => array('title' => $this->l('Descripción Error')),
            'urlQR' => array('title' => $this->l('URL QR')),
        );

        $helper = new HelperList();
        $helper->title = $this->l('Estado facturas');
        $helper->table = 'verifactu_order_invoice';
        $helper->identifier = 'id_order_invoice';
        $helper->simple_header = false;
        $helper->actions = array();
        $helper->show_toolbar = true;
        $helper->module = $this;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name. '&tab_module_verifactu=invoices';
        
        $page = (int) Tools::getValue('submitFilter' . $helper->table);
        $page = $page ? $page : 1;
        $pagination = (int) Tools::getValue('pagination', 20);
        $pagination = $pagination ? $pagination : 20;

        $orderBy = Tools::getValue($helper->table . 'Orderby', 'id_order_invoice');
        $orderWay = Tools::getValue($helper->table . 'Orderway', 'DESC');

        $content = $this->getInvoicesListContent($helper->table, $page, $pagination, $orderBy, $orderWay);
        $helper->listTotal = $this->getTotalInvoicesListContent($helper->table);


        return $helper->generateList($content, $fields_list);
    }

    private function getInvoicesListContent($table, $page, $pagination, $orderBy, $orderWay)
    {
        //INNER JOIN AQUIIIIIIIIIIIIII
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from($table, 't');
        $sql->orderBy('`' . bqSQL($orderBy) . '` ' . pSQL($orderWay));
        
        $where = '';
        $filters = Tools::getAllValues();
        foreach ($filters as $key => $value) {
            if (strpos($key, $table . 'Filter_') === 0 && !empty($value)) {
                $field = substr($key, strlen($table . 'Filter_'));
                $where .= ' AND t.`' . bqSQL($field) . '` LIKE "%' . pSQL($value) . '%"';
            }
        }
        if ($where) {
            $sql->where(ltrim($where, ' AND'));
        }

        $sql->limit($pagination, ($page - 1) * $pagination);

        return Db::getInstance()->executeS($sql);
    }

    private function getTotalInvoicesListContent($table)
    {
        $sql = new DbQuery();
        $sql->select('COUNT(*)');
        $sql->from($table, 't');

        $where = '';
        $filters = Tools::getAllValues();
        foreach ($filters as $key => $value) {
            if (strpos($key, $table . 'Filter_') === 0 && !empty($value)) {
                $field = substr($key, strlen($table . 'Filter_'));
                $where .= ' AND t.`' . bqSQL($field) . '` LIKE "%' . pSQL($value) . '%"';
            }
        }
        if ($where) {
            $sql->where(ltrim($where, ' AND'));
        }

        return (int)Db::getInstance()->getValue($sql);
    }

    //Listado de registros de facturación-------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    
    public function renderList()
    {
        $fields_list = array(
            'id_reg_fact' => array('title' => $this->l('ID'),'type' => 'number'),
            'id_order_invoice' => array('title' => $this->l('ID Factura'), 'type' => 'number'),
            'verifactuEstadoEnvio' => array('title' => $this->l('Estado Envío')),
            'verifactuEstadoRegistro' => array('title' => $this->l('Estado Registro')),
            'verifactuCodigoErrorRegistro' => array('title' => $this->l('Código Error')),
            'verifactuDescripcionErrorRegistro' => array('title' => $this->l('Descripción Error')),
            'urlQR' => array('title' => $this->l('URL QR')),
        );

        $helper = new HelperList();
        $helper->title = $this->l('Registros de Facturación');
        $helper->table = 'verifactu_reg_fact';
        $helper->identifier = 'id_reg_fact';
        $helper->simple_header = false;
        $helper->actions = array();
        $helper->show_toolbar = true;
        $helper->module = $this;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name. '&tab_module_verifactu=reg_facts';
        
        $page = (int) Tools::getValue('submitFilter' . $helper->table);
        $page = $page ? $page : 1;
        $pagination = (int) Tools::getValue('pagination', 20);
        $pagination = $pagination ? $pagination : 20;

        $orderBy = Tools::getValue($helper->table . 'Orderby', 'id_reg_fact');
        $orderWay = Tools::getValue($helper->table . 'Orderway', 'DESC');

        $content = $this->getListContent($helper->table, $page, $pagination, $orderBy, $orderWay);
        $helper->listTotal = $this->getTotalListContent($helper->table);


        return $helper->generateList($content, $fields_list);
    }

    private function getListContent($table, $page, $pagination, $orderBy, $orderWay)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from($table, 't');
        $sql->orderBy('`' . bqSQL($orderBy) . '` ' . pSQL($orderWay));
        
        $where = '';
        $filters = Tools::getAllValues();
        foreach ($filters as $key => $value) {
            if (strpos($key, $table . 'Filter_') === 0 && !empty($value)) {
                $field = substr($key, strlen($table . 'Filter_'));
                $where .= ' AND t.`' . bqSQL($field) . '` LIKE "%' . pSQL($value) . '%"';
            }
        }
        if ($where) {
            $sql->where(ltrim($where, ' AND'));
        }

        $sql->limit($pagination, ($page - 1) * $pagination);

        return Db::getInstance()->executeS($sql);
    }

    private function getTotalListContent($table)
    {
        $sql = new DbQuery();
        $sql->select('COUNT(*)');
        $sql->from($table, 't');

        $where = '';
        $filters = Tools::getAllValues();
        foreach ($filters as $key => $value) {
            if (strpos($key, $table . 'Filter_') === 0 && !empty($value)) {
                $field = substr($key, strlen($table . 'Filter_'));
                $where .= ' AND t.`' . bqSQL($field) . '` LIKE "%' . pSQL($value) . '%"';
            }
        }
        if ($where) {
            $sql->where(ltrim($where, ' AND'));
        }

        return (int)Db::getInstance()->getValue($sql);
    }

    //Listado de logs-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    public function renderLogsList()
    {
        $fields_list = array(
            'id_log' => array('title' => $this->l('ID'), 'type' => 'number'),
            'id_order_invoice' => array('title' => $this->l('ID Factura'), 'type' => 'number'),
            'verifactuEstadoEnvio' => array('title' => $this->l('Estado Envío')),
            'verifactuEstadoRegistro' => array('title' => $this->l('Estado Registro')),
            'verifactuCodigoErrorRegistro' => array('title' => $this->l('Código Error')),
            'verifactuDescripcionErrorRegistro' => array('title' => $this->l('Descripción Error')),
            'fechahora' => array('title' => $this->l('Fecha y Hora'), 'type' => 'datetime'),
        );
    
        $helper = new HelperList();
        $helper->title = $this->l('Logs');
        $helper->table = 'verifactu_logs';
        $helper->identifier = 'id_log';
        $helper->simple_header = false;
        $helper->actions = array();
        $helper->show_toolbar = true;
        $helper->module = $this;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module_verifactu=logs';

        // Asignamos nuestra función como callback para las clases de las filas
        //$helper->row_class_callback = array($this, 'getRowClass');
    
        $page = (int)Tools::getValue('submitFilter' . $helper->table, 1);
        $pagination = (int)Tools::getValue($helper->table . '_pagination', 20);
    
        $orderBy = Tools::getValue($helper->table . 'Orderby', 'id_log');
        $orderWay = Tools::getValue($helper->table . 'Orderway', 'DESC');
    
        $content = $this->getLogsListContent($page, $pagination, $orderBy, $orderWay);
        $helper->listTotal = $this->getTotalLogsListContent();
    
        return $helper->generateList($content, $fields_list);
    }

    /*public function getRowClass($row)
    {
        // Comprobamos si el campo 'verifactuEstadoRegistro' existe y si su valor es 'Correcto'
        if (isset($row['verifactuEstadoRegistro']) && $row['verifactuEstadoRegistro'] == 'Correcto') {
            // Si es correcto, devolvemos la clase CSS para el éxito
            return 'verifactu_correct';
        } else {
            // Para cualquier otro caso, devolvemos la clase CSS para el error
            return 'verifactu_error';
        }
    }*/
    
    private function getLogsListContent($page, $pagination, $orderBy, $orderWay)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('verifactu_logs', 'vl');
        $sql->orderBy('`' . bqSQL($orderBy) . '` ' . pSQL($orderWay));
    
        $where = '';
        $filters = Tools::getAllValues();
        foreach ($filters as $key => $value) {
            if (strpos($key, 'verifactu_logsFilter_') === 0 && !empty($value)) {
                $field = substr($key, strlen('verifactu_logsFilter_'));
                $where .= ' AND vl.`' . bqSQL($field) . '` LIKE "%' . pSQL($value) . '%"';
            }
        }
        if ($where) {
            $sql->where(ltrim($where, ' AND'));
        }
    
        $sql->limit($pagination, ($page - 1) * $pagination);
    
        return Db::getInstance()->executeS($sql);
    }
    
    private function getTotalLogsListContent()
    {
        $sql = new DbQuery();
        $sql->select('COUNT(*)');
        $sql->from('verifactu_logs', 'vl');
    
        $where = '';
        $filters = Tools::getAllValues();
        foreach ($filters as $key => $value) {
            if (strpos($key, 'verifactu_logsFilter_') === 0 && !empty($value)) {
                $field = substr($key, strlen('verifactu_logsFilter_'));
                $where .= ' AND vl.`' . bqSQL($field) . '` LIKE "%' . pSQL($value) . '%"';
            }
        }
        if ($where) {
            $sql->where(ltrim($where, ' AND'));
        }
    
        return (int)Db::getInstance()->getValue($sql);
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }


    public function hookActionSetInvoice($params)
    {
        //Si la configuración de envío automático a verifactu está activada
        if (Configuration::get('VERIFACTU_LIVE_SEND', true))
        {
            $order = $params['Order'];
            $id_order = $order->id;
            //PrestaShopLogger::addLog('Se ejecuta '.$id_order .' '.$params['OrderInvoice']->id.' '.$params['OrderInvoice']->id_order, 1);
            $av = new ApiVerifactu();
            $av->sendAltaVerifactu($id_order);
        }
        
    }

    public function hookActionOrderGridDefinitionModifier(array $params)
    {
        /** @var GridDefinitionInterface $definition */
        $definition = $params['definition'];

        $translator = $this->getTranslator();

        $verifactuColumn = new DataColumn('Verifactu');
        $verifactuColumn->setName('Verifactu');
        $verifactuColumn->setOptions([
             'field' => 'verifactu',
        ]);

        $columns = new ColumnCollection();
        $columns->add($verifactuColumn);

        $definition
            ->getColumns()
            ->addAfter(
                'osname',
                $verifactuColumn
            )
        ;
    }

    public function hookActionOrderGridQueryBuilderModifier(array $params)
    {
        /** @var QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];

        $searchCriteria = $params['search_criteria'];

        $searchQueryBuilder->addSelect(
            'IF(ISNULL(vi.verifactuEstadoRegistro),IF(ISNULL(vi.id_order_invoice),"Sin factura","No enviada"),vi.verifactuEstadoRegistro) AS `verifactu`'
        );

        $searchQueryBuilder->leftJoin(
            'o',
            '`' . pSQL(_DB_PREFIX_) . 'order_invoice`',
            'i',
            'i.`id_order` = o.`id_order`'
        );

        $searchQueryBuilder->leftJoin(
            'o',
            '`' . pSQL(_DB_PREFIX_) . 'verifactu_order_invoice`',
            'vi',
            'vi.`id_order_invoice` = i.`id_order_invoice`'
        );

        if ('verifactu' === $searchCriteria->getOrderBy()) {
            $searchQueryBuilder->orderBy('vi.`verifactuEstadoRegistro`', $searchCriteria->getOrderWay());
        }

        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if ('verifactu' === $filterName) {
                $searchQueryBuilder->andWhere('(vi.`verifactuEstadoRegistro` LIKE "%'.$filterValue.'%")');
                $searchQueryBuilder->setParameter('verifactu', $filterValue);

            }
        }
    }
    

    public function hookActionAdminControllerSetMedia($params)
    {
        // On every pages
        $this->context->controller->addCSS('modules/'.$this->name.'/views/css/back.css');

        foreach (Language::getLanguages() as $language) {
            $lang = Tools::strtoupper($language['iso_code']);
        }

        if ($lang == '') $lang = 'ES';

        Media::addJsDef(array('verifactu' => array('lang' => $lang)));

        $this->context->controller->addJS('modules/'.$this->name.'/views/js/back.js');
    }

    public function hookDisplayAdminOrderSide($params)
    {
        //require_once '/home/sandboxmachinepl/public_html/modules/lupiverifactu/libraries/qrcode.php';
        $id_order = $params['id_order'];

        $result = Db::getInstance()->getRow('SELECT voi.*, oi.id_order_invoice FROM ' . _DB_PREFIX_ . 'order_invoice as oi LEFT JOIN ' . _DB_PREFIX_ . 'verifactu_order_invoice as voi ON oi.id_order_invoice = voi.id_order_invoice WHERE oi.id_order = "'.$id_order.'"');
        $verifactuEstadoEnvio = $result['verifactuEstadoEnvio'];
        $verifactuEstadoRegistro = $result['verifactuEstadoRegistro'];
        $verifactuCodigoErrorRegistro = $result['verifactuCodigoErrorRegistro'];
        $verifactuDescripcionErrorRegistro = $result['verifactuDescripcionErrorRegistro'];
        $urlQR = $result['urlQR'];
        $imgQR = $result['imgQR'];

        $urladmin = Context::getContext()->link->getModuleLink( 'verifactu','ajax', array('ajax'=>true) );

         $this->context->smarty->assign(array(
            'urladmin' => $urladmin,
            'verifactuEstadoEnvio' => $verifactuEstadoEnvio,
            'verifactuEstadoRegistro' => $verifactuEstadoRegistro,
            'verifactuCodigoErrorRegistro' => $verifactuCodigoErrorRegistro,
            'verifactuDescripcionErrorRegistro' => $verifactuDescripcionErrorRegistro,
            'id_order' => $id_order,
            'imgQR' => $imgQR,
            'urlQR' => $urlQR,
            'id_order_invoice' => $result['id_order_invoice'],
            'current_url' => 'index.php?controller=AdminModules&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
        ));


        return $this->display(dirname(__FILE__), '/views/templates/admin/order_side.tpl');
    }
}