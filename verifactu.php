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

class Verifactu extends Module
{
    protected $config_form = false;
    private static $temp_qr_files = [];
    public $is_configurable;

    public function __construct()
    {
        $this->name = 'verifactu';
        $this->tab = 'billing_invoicing';
        $this->version = '1.1.7';
        $this->author = 'InFoAL S.L.';
        $this->need_instance = 0;
        $this->is_configurable = true;

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
        $config_keys = array(
            'VERIFACTU_API_TOKEN',
            'VERIFACTU_NIF_EMISOR',
            'VERIFACTU_DEBUG_MODE',
        );
        foreach ($config_keys as $key) {
            if (!Configuration::hasKey($key)) {
                Configuration::updateValue($key, null);
            }
        }

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() 

            
            && $this->registerHook('displayAdminOrderSide')
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->registerHook('actionOrderGridDefinitionModifier')
            && $this->registerHook('actionOrderGridQueryBuilderModifier')
            && $this->registerHook('actionSetInvoice')
            && $this->registerHook('displayPDFInvoice')
            && $this->registerHook('actionPDFInvoiceRender')
            && $this->registerHook('actionOrderSlipAdd')
            && $this->registerHook('displayPDFCreditSlip')      // Hook para mostrar el QR
            && $this->registerHook('actionPDFCreditSlipRender');
            ;
    }

    public function uninstall()
    {
        //No borramos los campos de configuración al desinstalar
        /*$config_keys = array(
            'VERIFACTU_API_TOKEN',
            'VERIFACTU_NIF_EMISOR',
            'VERIFACTU_DEBUG_MODE',
        );
        foreach ($config_keys as $key) {
            Configuration::deleteByName($key);
        }*/

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Hook para mostrar contenido adicional en el PDF de la factura.
     *
     * @param array $params Parámetros del hook, contiene el objeto OrderInvoice
     * @return string Contenido HTML para inyectar en el PDF
     */
    public function hookDisplayPDFInvoice($params)
    {
        require_once(dirname(__FILE__).'/lib/phpqrcode/qrlib.php');

        $order_invoice = $params['object'];
        if (!Validate::isLoadedObject($order_invoice)) {
            return '';
        }

        $order = new Order($order_invoice->id_order);
        if (!Validate::isLoadedObject($order)) {
            return ''; // No se pudo cargar el pedido, salimos.
        }
        // Ahora obtenemos el id_shop desde el pedido, que sí es una propiedad pública.
        $id_shop = (int) $order->id_shop;

        $sql = new DbQuery();
        $sql->select('urlQR');
        $sql->from('verifactu_order_invoice');
        $sql->where('id_order_invoice = ' . (int)$order_invoice->id);
        $url_to_encode = Db::getInstance()->getValue($sql);

        // Si no tenemos una URL, no hacemos nada.
        if (empty($url_to_encode)) {
            //return '';
            //Generamos la url a partir de los datos que sabemos
            $sql = new DbQuery();
            $sql->select('*');
            $sql->from('order_invoice');
            $sql->where('id_order_invoice = ' . (int)$order_invoice->id);
            $invoice = Db::getInstance()->getRow($sql);

            $api_token = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
            $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);
            $av = new ApiVerifactu($api_token, $debug_mode, $id_shop);
            $numserie = urlencode($av->getFormattedInvoiceNumber($invoice['id_order_invoice']));
            $fecha = date('d-m-Y', strtotime($invoice['date_add']));
            $importe = round((float) $invoice['total_paid_tax_incl'],2);
            $nif_emisor = Configuration::get('VERIFACTU_NIF_EMISOR', null, null, $id_shop);
            $url_to_encode = 'https://www2.agenciatributaria.gob.es/wlpl/TIKE-CONT/ValidarQR?nif='.$nif_emisor.'&numserie='.$numserie.'&fecha='.$fecha.'&importe='.$importe;
        }

        // 4. GENERACIÓN DEL QR Y GUARDADO TEMPORAL
        $qr_code_path = null;
        try {
            // Definimos una ruta y un nombre de archivo únicos en el directorio temporal.
            $tmp_dir = _PS_TMP_IMG_DIR_;
            $tmp_filename = 'verifactu_qr_' . $order_invoice->id . '_' . time() . '.png';
            $qr_code_path = $tmp_dir . $tmp_filename;

            // Usamos la biblioteca para generar el QR y guardarlo como un archivo PNG.
            // Parámetros: (texto, ruta_archivo, corrección_error, tamaño_pixel, margen)
            QRcode::png($url_to_encode, $qr_code_path, QR_ECLEVEL_L, 4, 2);

            // Si el archivo se ha creado, guardamos su ruta para borrarlo después.
            if (file_exists($qr_code_path)) {
                self::$temp_qr_files[] = $qr_code_path;
            } else {
                $qr_code_path = null; // Si falla la creación, no pasamos la ruta.
            }

        } catch (Exception $e) {
            PrestaShopLogger::addLog('Módulo Verifactu: Error al generar el archivo QR: ' . $e->getMessage(), 3, null, null, null, true, $id_shop);
            $qr_code_path = null;
        }
        
        // 5. Asignamos la ruta a la plantilla.
        $this->context->smarty->assign([
            'verifactu_qr_code_path' => $qr_code_path,
            'verifactu_url' => $url_to_encode
        ]);
        
        return $this->context->smarty->fetch('module:verifactu/views/templates/hook/invoice_qr.tpl');
    }

    /**
     * Hook que se ejecuta después de renderizar el PDF de la factura.
     * Se usa para borrar los archivos de QR temporales que hemos creado.
     */
    public function hookActionPDFInvoiceRender($params)
    {
        foreach (self::$temp_qr_files as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        // Reseteamos el array
        self::$temp_qr_files = [];
    }

    /**
     * Hook para mostrar contenido adicional en el PDF de la factura de abono.
     *
     * @param array $params Parámetros del hook, contiene el objeto OrderSlip
     * @return string Contenido HTML para inyectar en el PDF
     */
    public function hookDisplayPDFCreditSlip($params)
    {
        // 1. Incluimos la biblioteca de QR.
        require_once(dirname(__FILE__).'/lib/phpqrcode/qrlib.php');

        // 2. Obtenemos el objeto OrderSlip (factura de abono).
        $order_slip = $params['object'];
        if (!Validate::isLoadedObject($order_slip)) {
            return '';
        }

        $id_shop = (int) $order_slip->id_shop;

        PrestaShopLogger::addLog('Pasa por aqui', 3, null, null, null, true, $id_shop);

        // 3. Obtenemos la URL del QR desde nuestra tabla de abonos.
        $sql = new DbQuery();
        $sql->select('urlQR');
        $sql->from('verifactu_order_slip');
        $sql->where('id_order_slip = ' . (int)$order_slip->id);
        $url_to_encode = Db::getInstance()->getValue($sql);

        if (empty($url_to_encode)) {
            return '';
        }

        // 4. Generamos el archivo de imagen QR temporal.
        $qr_code_path = null;
        try {
            $tmp_dir = _PS_TMP_IMG_DIR_;
            // Usamos un prefijo 'slip' para evitar conflictos con los QR de facturas.
            $tmp_filename = 'verifactu_qr_slip_' . $order_slip->id . '_' . time() . '.png';
            $qr_code_path = $tmp_dir . $tmp_filename;

            QRcode::png($url_to_encode, $qr_code_path, QR_ECLEVEL_L, 4, 2);

            if (file_exists($qr_code_path)) {
                self::$temp_qr_files[] = $qr_code_path;
            } else {
                $qr_code_path = null;
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Módulo Verifactu: Error al generar QR para abono: ' . $e->getMessage(), 3, null, null, null, true, $id_shop);
            $qr_code_path = null;
        }
        
        // 5. Asignamos la ruta a la misma plantilla que ya usas.
        $this->context->smarty->assign([
            'verifactu_qr_code_path' => $qr_code_path
        ]);
        
        // Reutilizamos la plantilla existente.
        return $this->context->smarty->fetch('module:verifactu/views/templates/hook/invoice_qr.tpl');
    }

    /**
     * Hook que se ejecuta después de renderizar el PDF del abono.
     * Se usa para borrar los archivos de QR temporales que hemos creado.
     */
    public function hookActionPDFCreditSlipRender($params)
    {
        // Esta lógica es idéntica a la del hook de facturas y limpiará los QR de ambas.
        foreach (self::$temp_qr_files as $path) {
            if (file_exists($path)) {
                @unlink($path);
            }
        }
        // Reseteamos el array para la siguiente ejecución.
        self::$temp_qr_files = [];
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $output = '';

        // Si se cambia de tienda en el selector, PrestaShop recarga la página.
        if (Tools::isSubmit('changeShopContext')) {
            $this->context->cookie->shopContext = Tools::getValue('shop_id');
            // Redirigir para aplicar el nuevo contexto
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name);
        }

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

        $output .= $this->renderShopList();

        if ($tab == 'configure') 
        {
            $output .= $this->renderForm();
        } 
        elseif ($tab == 'invoices') 
        {
            $output .= $this->renderInvoicesList();
        } 
        elseif ($tab == 'reg_facts') 
        {
            $output .= $this->renderList();
        } 
        /*elseif ($tab == 'logs') 
        {
            $output .= $this->renderLogsList();
        }*/

        return $output;
    }

    public function renderShopList()
    {
        if (!Shop::isFeatureActive() || Shop::getTotalShops(false, null) < 2) {
            return '';
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->table = 'configuration';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->show_toolbar = false;
        
        return $helper->generateForm(array($this->getShopContextForm()));
    }

    // CAMBIO MULTITIENDA: Nuevo método para definir el formulario del selector de tiendas.
    public function getShopContextForm()
    {
        $shops = Shop::getShops(true, null, true);
        $shop_context = $this->context->shop->getContext();

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Shop context'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'shop',
                        'label' => $this->l('Shop context'),
                        'name' => 'shop_id',
                        'values' => $shops,
                    ),
                ),
            ),
        );
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
                        'col' => 8,
                        'type' => 'text',
                        'prefix' => '',
                        'desc' => $this->l('Token de InFoAL Veri*Factu API (Si no dispone de una clave de API, solicitu una gratuïta en https://verifactu.infoal.com)'),
                        'name' => 'VERIFACTU_API_TOKEN',
                        'label' => $this->l('InFoAL Veri*Factu API Token'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '',
                        'desc' => $this->l('NIF del obligado a expedir las facturas (Para generar los códigos QR offline)'),
                        'name' => 'VERIFACTU_NIF_EMISOR',
                        'label' => $this->l('NIF del emisor de las facturas'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Activar modo debug'),
                        'name' => 'VERIFACTU_DEBUG_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Activa esta opción si quieres guardar logs de los eventos en los Registros/Logs de prestashop.'),
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
                        'disabled' => false,
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
        $id_shop_group = Shop::getContextShopGroupID();
        $id_shop = Shop::getContextShopID();

        return array(
            'VERIFACTU_API_TOKEN' => Configuration::get('VERIFACTU_API_TOKEN', null, $id_shop_group, $id_shop),
            'VERIFACTU_DEBUG_MODE' => Configuration::get('VERIFACTU_DEBUG_MODE', 0, $id_shop_group, $id_shop),
            'VERIFACTU_NIF_EMISOR' => Configuration::get('VERIFACTU_NIF_EMISOR', null, $id_shop_group, $id_shop),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        $shops = Tools::getValue('checkBoxShopAsso_configuration');
        
        if (empty($shops)) {
            // Si no se selecciona ninguna tienda, se guarda en el contexto actual.
            $id_shop_group = Shop::getContextShopGroupID();
            $id_shop = Shop::getContextShopID();
            foreach (array_keys($form_values) as $key) {
                Configuration::updateValue($key, Tools::getValue($key), false, $id_shop_group, $id_shop);
            }
        } else {
            // Si se seleccionan tiendas específicas.
            foreach ($shops as $id_shop) {
                $id_shop_group = Shop::getGroupFromShop($id_shop);
                foreach (array_keys($form_values) as $key) {
                    Configuration::updateValue($key, Tools::getValue($key), false, $id_shop_group, $id_shop);
                }
            }
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
    $db = Db::getInstance();
    $sql = new DbQuery();
    $sql->select('*');
    $sql->from($table, 't');

    // Validamos que los campos de ordenación estén en una lista blanca.
    $allowedOrderBy = ['id_reg_fact', 'id_order_invoice', 'verifactuEstadoEnvio', 'verifactuEstadoRegistro'];
    if (!in_array($orderBy, $allowedOrderBy)) {
        $orderBy = 'id_order_invoice'; // Valor por defecto seguro
    }
    $orderWay = strtoupper($orderWay) === 'ASC' ? 'ASC' : 'DESC'; // Validar dirección
    $sql->orderBy('`' . pSQL($orderBy) . '` ' . pSQL($orderWay));

    $whereClauses = [];
    $filters = Tools::getAllValues();
    foreach ($filters as $key => $value) {
        if (strpos($key, $table . 'Filter_') === 0 && !empty($value)) {
            $field = substr($key, strlen($table . 'Filter_'));
            // --- CORRECCIÓN DE FILTRADO ---
            // Validamos el campo contra una lista blanca.
            $allowedFilters = ['id_reg_fact', 'id_order_invoice', 'verifactuEstadoRegistro'];
            if (in_array($field, $allowedFilters)) {
                // Usamos pSQL para escapar el valor, que es seguro para cláusulas LIKE.
                $whereClauses[] = 't.`' . pSQL($field) . '` LIKE "%' . pSQL($value) . '%"';
            }
            // --- FIN CORRECCIÓN ---
        }
    }
    if (!empty($whereClauses)) {
        $sql->where(implode(' AND ', $whereClauses));
    }

    $sql->limit($pagination, ($page - 1) * $pagination);

    return $db->executeS($sql);
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
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from($table, 't');

        $allowedOrderBy = ['id_reg_fact', 'id_order_invoice', 'verifactuEstadoEnvio', 'verifactuEstadoRegistro', 'verifactuCodigoErrorRegistro'];
        if (!in_array($orderBy, $allowedOrderBy)) {
            $orderBy = 'id_reg_fact'; // Valor por defecto seguro
        }
        $orderWay = strtoupper($orderWay) === 'ASC' ? 'ASC' : 'DESC'; // Validar dirección
        $sql->orderBy('`' . pSQL($orderBy) . '` ' . pSQL($orderWay));

        $whereClauses = [];
        $filters = Tools::getAllValues();
        foreach ($filters as $key => $value) {
            if (strpos($key, $table . 'Filter_') === 0 && !empty($value)) {
                $field = substr($key, strlen($table . 'Filter_'));
                
                $allowedFilters = ['id_reg_fact', 'id_order_invoice', 'verifactuEstadoRegistro', 'verifactuDescripcionErrorRegistro'];
                if (in_array($field, $allowedFilters)) {
                    // Usamos pSQL() para escapar el valor de forma segura para la cláusula LIKE
                    $whereClauses[] = 't.`' . pSQL($field) . '` LIKE "%' . pSQL($value) . '%"';
                }
            }
        }
        if (!empty($whereClauses)) {
            $sql->where(implode(' AND ', $whereClauses));
        }

        $sql->limit($pagination, ($page - 1) * $pagination);

        return $db->executeS($sql);
    }

    private function getTotalListContent($table)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('COUNT(*)');
        $sql->from($table, 't');

        $whereClauses = [];
        $filters = Tools::getAllValues();
        foreach ($filters as $key => $value) {
            if (strpos($key, $table . 'Filter_') === 0 && !empty($value)) {
                $field = substr($key, strlen($table . 'Filter_'));
                
                $allowedFilters = ['id_reg_fact', 'id_order_invoice', 'verifactuEstadoRegistro', 'verifactuDescripcionErrorRegistro'];
                if (in_array($field, $allowedFilters)) {
                    $whereClauses[] = 't.`' . pSQL($field) . '` LIKE "%' . pSQL($value) . '%"';
                }
            }
        }
        if (!empty($whereClauses)) {
            $sql->where(implode(' AND ', $whereClauses));
        }

        return (int)$db->getValue($sql);
    }

    //Listado de logs-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    /*public function renderLogsList()
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
    }*/

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
    
    /*private function getLogsListContent($page, $pagination, $orderBy, $orderWay)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('verifactu_logs', 'vl');

        $allowedOrderBy = ['id_log', 'id_order_invoice', 'verifactuEstadoEnvio', 'verifactuEstadoRegistro', 'verifactuCodigoErrorRegistro', 'fechahora'];
        if (!in_array($orderBy, $allowedOrderBy)) {
            $orderBy = 'id_log'; // Valor por defecto seguro
        }
        $orderWay = strtoupper($orderWay) === 'ASC' ? 'ASC' : 'DESC';
        $sql->orderBy('`' . pSQL($orderBy) . '` ' . pSQL($orderWay));

        $whereClauses = [];
        $filters = Tools::getAllValues();
        foreach ($filters as $key => $value) {
            if (strpos($key, 'verifactu_logsFilter_') === 0 && !empty($value)) {
                $field = substr($key, strlen('verifactu_logsFilter_'));
                
                $allowedFilters = ['id_log', 'id_order_invoice', 'verifactuEstadoRegistro', 'verifactuDescripcionErrorRegistro'];
                if (in_array($field, $allowedFilters)) {
                    $whereClauses[] = 'vl.`' . pSQL($field) . '` LIKE "%' . pSQL($value) . '%"';
                }
            }
        }
        if (!empty($whereClauses)) {
            $sql->where(implode(' AND ', $whereClauses));
        }
    
        $sql->limit($pagination, ($page - 1) * $pagination);
    
        return $db->executeS($sql);
    }
    
    private function getTotalLogsListContent()
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('COUNT(*)');
        $sql->from('verifactu_logs', 'vl');
    
        $whereClauses = [];
        $filters = Tools::getAllValues();
        foreach ($filters as $key => $value) {
            if (strpos($key, 'verifactu_logsFilter_') === 0 && !empty($value)) {
                $field = substr($key, strlen('verifactu_logsFilter_'));
                
                $allowedFilters = ['id_log', 'id_order_invoice', 'verifactuEstadoRegistro', 'verifactuDescripcionErrorRegistro'];
                if (in_array($field, $allowedFilters)) {
                    $whereClauses[] = 'vl.`' . pSQL($field) . '` LIKE "%' . pSQL($value) . '%"';
                }
            }
        }
        if (!empty($whereClauses)) {
            $sql->where(implode(' AND ', $whereClauses));
        }
    
        return (int)$db->getValue($sql);
    }*/

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

        $order = $params['Order'];
        $id_shop = (int)$order->id_shop;
        $api_token = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
        $nif_emisor = Configuration::get('VERIFACTU_NIF_EMISOR', null, null, $id_shop);
        
        if (!empty($api_token) && !empty($nif_emisor)) {
             $id_order = $order->id;
             $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);
             $av = new ApiVerifactu($api_token, $debug_mode, $id_shop);
             $av->sendAltaVerifactu($id_order, 'alta');
        } else {
             PrestaShopLogger::addLog(
                'Módulo Verifactu: No se envía la factura para el pedido ' . $order->id . ' porque falta configuración (API Token o NIF) para la tienda ID: ' . $id_shop,
                2, null, null, null, true, $id_shop
            );
        }

        
    }

    public function hookActionOrderSlipAdd ($params)
    {

        $order = $params['order'];
        $id_shop = (int)$order->id_shop;
        $api_token = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
        $nif_emisor = Configuration::get('VERIFACTU_NIF_EMISOR', null, null, $id_shop);

        if (!empty($api_token) && !empty($nif_emisor)) {
            $id_order = $order->id;
            $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);
            $av = new ApiVerifactu($api_token, $debug_mode, $id_shop);
            $av->sendAltaVerifactu($id_order, 'abono');
        } else {
             PrestaShopLogger::addLog(
                'Módulo Verifactu: No se envía el abono para el pedido ' . $order->id . ' porque falta configuración (API Token o NIF) para la tienda ID: ' . $id_shop,
                2, null, null, null, true, $id_shop
            );
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
            'IF(ISNULL(vi.verifactuEstadoRegistro),IF(ISNULL(i.id_order_invoice),"Sin factura","No enviada"),vi.verifactuEstadoRegistro) AS `verifactu`'
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
            $searchQueryBuilder->andWhere('vi.`verifactuEstadoRegistro` LIKE :verifactu_filter');
            $searchQueryBuilder->setParameter('verifactu_filter', '%' . $filterValue . '%');
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
        $this->context->controller->addJS('https://cdn.jsdelivr.net/npm/sweetalert2@11');

        Media::addJsDef([
            'verifactu_ajax_url' => $this->context->link->getAdminLink('AdminVerifactuAjax'),
            'verifactu_token' => Tools::getAdminTokenLite('AdminVerifactuAjax')
        ]);
    }

    public function hookDisplayAdminOrderSide($params)
{
    require_once(dirname(__FILE__).'/lib/phpqrcode/qrlib.php');

    $id_order = (int) $params['id_order'];

    // 1. La consulta a la base de datos
    $sql = new DbQuery();
    $sql->select('voi.*, oi.id_order_invoice');
    $sql->from('order_invoice', 'oi');
    $sql->leftJoin('verifactu_order_invoice', 'voi', 'oi.id_order_invoice = voi.id_order_invoice');
    $sql->where('oi.id_order = ' . $id_order);
    $result = Db::getInstance()->getRow($sql);

    // 2. La comprobación CLAVE: verificamos si $result NO es false
    if ($result) {
        // El pedido SÍ tiene una factura, asignamos los valores desde la base de datos.
        $urlQR = $result['urlQR'];
        $verifactuEstadoEnvio = $result['verifactuEstadoEnvio'];
        $verifactuEstadoRegistro = $result['verifactuEstadoRegistro'];
        $verifactuCodigoErrorRegistro = $result['verifactuCodigoErrorRegistro'];
        $verifactuDescripcionErrorRegistro = $result['verifactuDescripcionErrorRegistro'];
        $anulacion = $result['anulacion'];
        $estado = $result['estado'];
        $TipoFactura = $result['TipoFactura'];
        $id_order_invoice = $result['id_order_invoice'];
    } else {
        // El pedido NO tiene factura, asignamos valores por defecto seguros.
        $urlQR = null;
        $verifactuEstadoEnvio = 'Sin factura';
        $verifactuEstadoRegistro = 'N/A';
        $verifactuCodigoErrorRegistro = null;
        $verifactuDescripcionErrorRegistro = null;
        $anulacion = null;
        $estado = null;
        $TipoFactura = null;
        $id_order_invoice = null;
    }

    // 3. Inicialización segura de la variable del QR
    $imgQR = null;
    
    // 4. Esta lógica solo se ejecuta si encontramos una URL en el paso 2
    if (!empty($urlQR)) {
        try {
            $tmp_dir = _PS_TMP_IMG_DIR_;
            $tmp_filename = 'verifactu_qr_' . $id_order . '_' . time() . '.png';
            $imgQR_path = $tmp_dir . $tmp_filename;

            QRcode::png($urlQR, $imgQR_path, QR_ECLEVEL_L, 4, 2);

            if (file_exists($imgQR_path)) {
                self::$temp_qr_files[] = $imgQR_path;
                $imgQR = '/img/tmp/'. $tmp_filename;
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Módulo Verifactu: Error al generar el archivo QR: ' . $e->getMessage(), 3);
            $imgQR = null; // Nos aseguramos de que siga siendo null en caso de error
        }
    }

    // 5. Asignación a la plantilla. Todas las variables tienen un valor definido.
     $this->context->smarty->assign(array(
        'verifactuEstadoEnvio' => $verifactuEstadoEnvio,
        'verifactuEstadoRegistro' => $verifactuEstadoRegistro,
        'verifactuCodigoErrorRegistro' => $verifactuCodigoErrorRegistro,
        'verifactuDescripcionErrorRegistro' => $verifactuDescripcionErrorRegistro,
        'anulacion' => $anulacion,
        'estado' => $estado,
        'id_order' => $id_order,
        'TipoFactura' => $TipoFactura,
        'imgQR' => $imgQR,
        'urlQR' => $urlQR,
        'id_order_invoice' => $id_order_invoice,
        'current_url' => 'index.php?controller=AdminModules&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
    ));

    return $this->display(dirname(__FILE__), '/views/templates/admin/order_side.tpl');
}
}