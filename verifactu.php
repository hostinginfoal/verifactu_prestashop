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
*  @author    InFoAL S.L. <hosting@infoal.com>
*  @copyright InFoAL S.L.
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of InFoAL S.L.
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
        $this->version = '1.0.1';
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
        Configuration::updateValue('VERIFACTU_LIVE_MODE', false);

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
        Configuration::deleteByName('VERIFACTU_LIVE_MODE');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitVerifactuModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
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
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '',
                        'desc' => $this->l('Token de InFoAL Veri*Factu API (Si no dispones de una clave de API, solicita una gratuïta en https://verifactu.infoal.com'),
                        'name' => 'VERIFACTU_API_TOKEN',
                        'label' => $this->l('InFoAL Veri*Factu API Token'),
                    ),
                    /*array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Dirección de email'),
                        'name' => 'VERIFACTU_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),*/
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
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
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
            //'VERIFACTU_ACCOUNT_EMAIL' => Configuration::get('VERIFACTU_ACCOUNT_EMAIL', ''),
            //'VERIFACTU_ACCOUNT_PASSWORD' => Configuration::get('VERIFACTU_ACCOUNT_PASSWORD', null),
            'VERIFACTU_LIVE_SEND' => Configuration::get('VERIFACTU_LIVE_SEND', false),
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

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    /*public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookActionAdminControllerSetMedia()
    {
        
    }

    public function hookDisplayAdminOrderContentOrder()
    {
        
    }*/


    //LUPI --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    public function hookActionSetInvoice($params)
    {
        $order = $params['Order'];
        $id_order = $order->id;
        //PrestaShopLogger::addLog('Se ejecuta '.$id_order .' '.$params['OrderInvoice']->id.' '.$params['OrderInvoice']->id_order, 1);
        $av = new ApiVerifactu();
        $av->sendAltaVerifactu($id_order);
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

        /*$definition->getFilters()->add(
            (new Filter('verifactu', TextType::class))
            ->setAssociatedColumn('Verifactu')
            ->setTypeOptions([
                'required' => false,
                'attr' => [
                    'placeholder' => $this->trans('Verifactu', [], 'Admin.Actions'),
                ],
            ])
        );*/
        //die(print_r($definition->getFilters()));
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

        /*if ($this->context->employee->id_profile == 1)
        {
            $this->context->controller->addJS('modules/'.$this->name.'/views/js/lupigestionventas.js');
        }
        else
        {*/
            foreach (Language::getLanguages() as $language) {
                $lang = Tools::strtoupper($language['iso_code']);
            }

            if ($lang == '') $lang = 'ES';

            Media::addJsDef(array('verifactu' => array('lang' => $lang)));

            $this->context->controller->addJS('modules/'.$this->name.'/views/js/back.js');
            
        //}
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
        $qr = $result['qr'];

        $urladmin = Context::getContext()->link->getModuleLink( 'verifactu','ajax', array('ajax'=>true) );

         $this->context->smarty->assign(array(
            'urladmin' => $urladmin,
            'verifactuEstadoEnvio' => $verifactuEstadoEnvio,
            'verifactuEstadoRegistro' => $verifactuEstadoRegistro,
            'verifactuCodigoErrorRegistro' => $verifactuCodigoErrorRegistro,
            'verifactuDescripcionErrorRegistro' => $verifactuDescripcionErrorRegistro,
            'id_order' => $id_order,
            'qr' => $qr,
            'id_order_invoice' => $result['id_order_invoice'],
            'current_url' => 'index.php?controller=AdminModules&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
        ));


        return $this->display(dirname(__FILE__), '/views/templates/admin/order_side.tpl');
        
        
    }
}
