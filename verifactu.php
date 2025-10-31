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
        $this->version = '1.3.9';
        $this->author = 'InFoAL S.L.';
        $this->need_instance = 0;
        $this->is_configurable = true;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('VeriFactu');
        $this->description = $this->l('Automatiza el envío de registros de facturación al sistema Veri*Factu de la AEAT, añade el código QR a tus facturas y te permite hacer seguimiento de cada envío.');

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
            'VERIFACTU_USA_OSS',
            'VERIFACTU_TERRITORIO_ESPECIAL',
        );
        foreach ($config_keys as $key) {
            if (!Configuration::hasKey($key)) {
                $default_value = null;
                if ($key === 'VERIFACTU_USA_OSS' || $key === 'VERIFACTU_DEBUG_MODE' || $key === 'VERIFACTU_TERRITORIO_ESPECIAL') {
                    $default_value = 0;
                }
                Configuration::updateValue($key, $default_value);
            }
        }

        // 1. Creamos la pestaña PADRE. Esta será la nueva entrada en el menú principal.
        // Le asignamos un controlador ficticio que no existe para que actúe solo como contenedor.
        $parentTab = new Tab();
        $parentTab->active = 1;
        $parentTab->class_name = 'AdminVerifactuParent'; // Controlador ficticio para el menú padre
        $parentTab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $parentTab->name[$lang['id_lang']] = 'VeriFactu';
        }
        // IMPORTANTE: id_parent = 0 lo sitúa en la raíz del menú.
        // Si quisieras que estuviera dentro de 'VENDER', usarías (int)Tab::getIdFromClassName('SELL')
        $parentTab->id_parent = 0;
        $parentTab->module = $this->name;
        if (!$parentTab->add()) {
            return false;
        }

        // 2. Creamos las pestañas HIJA. Este será el enlace real que el usuario verá
        $childTab = new Tab();
        $childTab->active = 1;
        $childTab->class_name = 'AdminVerifactuSalesInvoices'; // Este sí es nuestro controlador de redirección
        $childTab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            // Le damos un nombre como 'Dashboard' o 'Configuración'
            $childTab->name[$lang['id_lang']] = 'Facturas';
        }
        $childTab->id_parent = (int)$parentTab->id;
        $childTab->icon = 'receipt';
        $childTab->module = $this->name;
        if (!$childTab->add()) {
            return false;
        }

        $childTab = new Tab();
        $childTab->active = 1;
        $childTab->class_name = 'AdminVerifactuCreditSlips'; // Este sí es nuestro controlador de redirección
        $childTab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            // Le damos un nombre como 'Dashboard' o 'Configuración'
            $childTab->name[$lang['id_lang']] = 'Facturas por abono';
        }
        $childTab->id_parent = (int)$parentTab->id;
        $childTab->icon = 'receipt';
        $childTab->module = $this->name;
        if (!$childTab->add()) {
            return false;
        }

        $childTab = new Tab();
        $childTab->active = 1;
        $childTab->class_name = 'AdminVerifactuRegFacts'; // Este sí es nuestro controlador de redirección
        $childTab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            // Le damos un nombre como 'Dashboard' o 'Configuración'
            $childTab->name[$lang['id_lang']] = 'Registros de facturación';
        }
        $childTab->id_parent = (int)$parentTab->id;
        $childTab->icon = 'receipt';
        $childTab->module = $this->name;
        if (!$childTab->add()) {
            return false;
        }

        //Menu Oculto para la vista detalle de los registros de facturación
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminVerifactuDetail'; // El nombre de nuestra nueva clase
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Detalle VeriFactu';
        }
        $tab->id_parent = -1; // No se muestra en el menú
        $tab->module = $this->name;
        if (!$tab->add()) {
            return false;
        }

        //Fin menu

        include(dirname(__FILE__).'/sql/install.php');

        if (!parent::install()) {
            return false;
        }

        // --- INICIO DE LA LÓGICA CONDICIONAL DE HOOKS ---

        // Registra los hooks para versiones modernas (1.7.7.0 y superiores)
        if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) {
            $this->registerHook('displayAdminOrderSide');
            $this->registerHook('actionOrderGridDefinitionModifier');
            $this->registerHook('actionOrderGridQueryBuilderModifier');
        } 
        // Registra los hooks para versiones antiguas (de 1.7.0.0 a 1.7.6.9)
        else {
            $this->registerHook('displayAdminOrder'); // Sustituto de displayAdminOrderSide
            $this->registerHook('actionAdminOrdersListingFieldsModifier'); // Sustituto de los hooks de Grid
        }

        // Registra los hooks comunes que funcionan en todas las versiones
        $this->registerHook('actionAdminControllerSetMedia');
        $this->registerHook('actionSetInvoice');
        $this->registerHook('displayPDFInvoice');
        $this->registerHook('actionPDFInvoiceRender');
        $this->registerHook('actionOrderSlipAdd');
        $this->registerHook('displayPDFOrderSlip');
        $this->registerHook('actionPDFOrderSlipRender');

        return true;
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

        // Siempre es buena práctica eliminar los hijos primero
        $child_tab_id = (int)Tab::getIdFromClassName('AdminVerifactuSalesInvoices');
        if ($child_tab_id) {
            $tab = new Tab($child_tab_id);
            $tab->delete();
        }

        $child_tab_id = (int)Tab::getIdFromClassName('AdminVerifactuCreditSlips');
        if ($child_tab_id) {
            $tab = new Tab($child_tab_id);
            $tab->delete();
        }

        $child_tab_id = (int)Tab::getIdFromClassName('AdminVerifactuRegFacts');
        if ($child_tab_id) {
            $tab = new Tab($child_tab_id);
            $tab->delete();
        }

        // Ahora eliminamos el padre
        $parent_tab_id = (int)Tab::getIdFromClassName('AdminVerifactuParent');
        if ($parent_tab_id) {
            $tab = new Tab($parent_tab_id);
            $tab->delete();
        }

        // Eliminamos la pestaña oculta 
        $id_tab = (int)Tab::getIdFromClassName('AdminVerifactuDetail');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            $tab->delete();
        }

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
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

        //Comprobamos si el contexto actual es "Todas las tiendas"
        if (Shop::getContext() == Shop::CONTEXT_ALL) {
            // Si lo es, mostramos un mensaje de advertencia y no continuamos renderizando el resto.
            $output .= $this->displayWarning($this->l('La configuración de VeriFactu es específica para cada tienda. Por favor, seleccione una tienda o un grupo de tiendas en el selector superior para continuar.'));
            
            return $output; // Devolvemos solo el mensaje y terminamos la ejecución del método.
        }

        
        if (((bool)Tools::isSubmit('submitCheckApiStatus')) == true) { // Procesar la acción de comprobar el estado de la API
            $result = $this->checkApiStatus();
            if ($result['success']) {
                $output .= $this->displayConfirmation($result['message']);
            } else {
                $output .= $this->displayError($result['message']);
            }
        }
        else if (((bool)Tools::isSubmit('submitCheckDatabase')) == true) { // Procesar la acción de verificar la base de datos
            $result = $this->checkAndFixDatabase();
            if ($result['success']) {
                $output .= $this->displayConfirmation($result['message']);
            } else {
                $output .= $this->displayError($result['message']);
            }
        }
        else if (((bool)Tools::isSubmit('submitVerifactuModule')) == true) { // Procesar la acción de guardar el formulario de configuración
            $this->postProcess();
            $output .= $this->displayConfirmation($this->l('Configuración actualizada'));
        }

        $update_info = $this->checkForUpdate();

        $this->context->smarty->assign('module_dir', $this->_path);

        $tab = Tools::getValue('tab_module_verifactu', 'configure');

        $current_url = $this->context->link->getAdminLink('AdminModules', true) .
                       '&configure=' . $this->name .
                       '&tab_module=' . $this->tab .
                       '&module_name=' . $this->name;

        $this->context->smarty->assign(array(
            'module_name' => $this->name,
            'active_tab' => $tab,
            'current' => $current_url,
            'update_available' => $update_info['update_available'],
            'latest_version' => $update_info['latest_version'],
            'github_releases_url' => 'https://github.com/hostinginfoal/verifactu_prestashop/releases/latest/download/verifactu.zip' // URL a tus releases
        ));
        
        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        //$output .= $this->renderShopList();

        if ($tab == 'configure') {
            $output .= $this->renderForm();
        } elseif ($tab == 'sales_invoices') { // Nueva pestaña
            $output .= $this->renderSalesInvoicesList();
        } elseif ($tab == 'credit_slips') { // Nueva pestaña
            $output .= $this->renderCreditSlipsList();
        } elseif ($tab == 'reg_facts') {
            $output .= $this->renderList();
        }

        return $output;
    }

    /**
     * Define el esquema completo y actual de las tablas del módulo.
     * Este es el "mapa" que se usará para verificar la integridad.
     * @return array
     */
    private function getDatabaseSchema()
    {
        return [
            'verifactu_reg_fact' => [
                'id_reg_fact' => 'int(11) NOT NULL',
                'id_order_invoice' => 'int(11) NOT NULL',
                'tipo' => 'varchar(20) DEFAULT NULL',
                'EstadoEnvio' => 'varchar(100) DEFAULT NULL',
                'EstadoRegistro' => 'varchar(100) DEFAULT NULL',
                'CodigoErrorRegistro' => 'varchar(100) DEFAULT NULL',
                'DescripcionErrorRegistro' => 'text',
                'urlQR' => 'varchar(255) DEFAULT NULL',
                'estado_queue' => 'varchar(20) DEFAULT NULL',
                'InvoiceNumber' => 'varchar(50) DEFAULT NULL',
                'IssueDate' => 'date DEFAULT NULL',
                'TipoOperacion' => 'varchar(45) DEFAULT NULL',
                'EmpresaNombreRazon' => 'varchar(45) DEFAULT NULL',
                'EmpresaNIF' => 'varchar(20) DEFAULT NULL',
                'hash' => 'varchar(255) DEFAULT NULL',
                'cadena' => 'text',
                'AnteriorHash' => 'varchar(255) DEFAULT NULL',
                'TipoFactura' => 'varchar(45) DEFAULT NULL',
                'FacturaSimplificadaArt7273' => 'varchar(45) DEFAULT NULL',
                'FacturaSinIdentifDestinatarioArt61d' => 'varchar(45) DEFAULT NULL',
                'CalificacionOperacion' => 'varchar(45) DEFAULT NULL',
                'Macrodato' => 'varchar(45) DEFAULT NULL',
                'Cupon' => 'varchar(45) DEFAULT NULL',
                'TotalTaxOutputs' => 'decimal(15,2) DEFAULT NULL',
                'InvoiceTotal' => 'decimal(15,2) DEFAULT NULL',
                'BuyerName' => 'varchar(255) DEFAULT NULL',
                'BuyerCorporateName' => 'varchar(255) DEFAULT NULL',
                'BuyerTaxIdentificationNumber' => 'varchar(45) DEFAULT NULL',
                'BuyerCountryCode' => 'varchar(10) DEFAULT NULL',
                'IDOtroIDType' => 'varchar(45) DEFAULT NULL',
                'IDOtroID' => 'varchar(45) DEFAULT NULL',
                'TipoRectificativa' => 'varchar(10) DEFAULT NULL',
                'CorrectiveInvoiceNumber' => 'varchar(50) DEFAULT NULL',
                'CorrectiveInvoiceSeriesCode' => 'varchar(10) DEFAULT NULL',
                'CorrectiveIssueDate' => 'date DEFAULT NULL',
                'CorrectiveBaseAmount' => 'decimal(15,2) DEFAULT NULL',
                'CorrectiveTaxAmount' => 'decimal(15,2) DEFAULT NULL',
                'FechaHoraHusoGenRegistro' => 'varchar(45) DEFAULT NULL',
                'fechaHoraRegistro' => 'datetime DEFAULT NULL',
                'SIFNombreRazon' => 'varchar(255) DEFAULT NULL',
                'SIFNIF' => 'varchar(45) DEFAULT NULL',
                'SIFNombreSIF' => 'varchar(45) DEFAULT NULL',
                'SIFIdSIF' => 'varchar(45) DEFAULT NULL',
                'SIFVersion' => 'varchar(45) DEFAULT NULL',
                'SIFNumeroInstalacion' => 'varchar(45) DEFAULT NULL',
                'SIFTipoUsoPosibleSoloVerifactu' => 'varchar(45) DEFAULT NULL',
                'SIFTipoUsoPosibleMultiOT' => 'varchar(45) DEFAULT NULL',
                'SIFIndicadorMultiplesOT' => 'varchar(45) DEFAULT NULL',
                'apiMode' => 'varchar(20) DEFAULT NULL',
                'id_shop' => 'int(11) NOT NULL',
            ],
            'verifactu_order_invoice' => [
                'id_order_invoice' => 'int(11) NOT NULL',
                'estado' => 'VARCHAR(40) NULL',
                'id_reg_fact' => 'int(11) NOT NULL',
                'verifactuEstadoEnvio' => 'VARCHAR(100) NULL',
                'verifactuEstadoRegistro' => 'VARCHAR(100) NULL',
                'verifactuCodigoErrorRegistro' => 'VARCHAR(100) NULL',
                'verifactuDescripcionErrorRegistro' => 'TEXT NULL',
                'urlQR' => 'VARCHAR(255) NULL',
                'anulacion' => 'int(11) NOT NULL',
                'TipoFactura' => 'VARCHAR(100) NULL',
                'avisos' => 'TEXT NULL',
                'apiMode' => 'varchar(20) DEFAULT NULL',
            ],
            'verifactu_order_slip' => [
                'id_order_slip' => 'int(11) NOT NULL',
                'estado' => 'VARCHAR(40) NULL',
                'id_reg_fact' => 'int(11) NOT NULL',
                'verifactuEstadoEnvio' => 'VARCHAR(100) NULL',
                'verifactuEstadoRegistro' => 'VARCHAR(100) NULL',
                'verifactuCodigoErrorRegistro' => 'VARCHAR(100) NULL',
                'verifactuDescripcionErrorRegistro' => 'TEXT NULL',
                'urlQR' => 'VARCHAR(255) NULL',
                'anulacion' => 'int(11) NOT NULL',
                'TipoFactura' => 'VARCHAR(100) NULL',
                'avisos' => 'TEXT NULL',
                'apiMode' => 'varchar(20) DEFAULT NULL',
            ],
        ];
    }
    
    /**
     * Función principal que se ejecuta al pulsar el botón de verificación.
     * @return array con el resultado de la operación.
     */
    public function checkAndFixDatabase()
    {
        $schema = $this->getDatabaseSchema();
        $db = Db::getInstance();
        $prefix = _DB_PREFIX_;
        $dbName = _DB_NAME_;
        $errors = [];
        $fixes = 0;

        foreach ($schema as $tableName => $columns) {
            // Primero, comprobamos si la tabla existe
            $sqlTableCheck = "SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`TABLES`
                              WHERE `TABLE_SCHEMA` = '" . pSQL($dbName) . "'
                              AND `TABLE_NAME` = '" . pSQL($prefix . $tableName) . "'";
            
            if ((int)$db->getValue($sqlTableCheck) == 0) {
                // La tabla no existe, la creamos usando el sql/install.php
                // Esto es una medida de seguridad extra.
                include(dirname(__FILE__).'/sql/install.php');
                $errors[] = sprintf($this->l('La tabla %s no existía y ha sido creada. Por favor, vuelva a ejecutar la comprobación.'), $prefix . $tableName);
                continue; // Pasamos a la siguiente tabla
            }

            // Si la tabla existe, comprobamos cada columna
            foreach ($columns as $columnName => $columnDefinition) {
                $sqlColumnCheck = "SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`COLUMNS`
                                   WHERE `TABLE_SCHEMA` = '" . pSQL($dbName) . "'
                                   AND `TABLE_NAME` = '" . pSQL($prefix . $tableName) . "'
                                   AND `COLUMN_NAME` = '" . pSQL($columnName) . "'";
                
                if ((int)$db->getValue($sqlColumnCheck) == 0) {
                    // La columna no existe, la añadimos
                    $sqlAlter = "ALTER TABLE `" . pSQL($prefix . $tableName) . "` ADD `" . pSQL($columnName) . "` " . $columnDefinition;
                    if ($db->execute($sqlAlter)) {
                        $fixes++;
                    } else {
                        $errors[] = sprintf($this->l('Error al intentar añadir la columna %s a la tabla %s.'), $columnName, $prefix . $tableName);
                    }
                }
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode('<br>', $errors)];
        }

        if ($fixes > 0) {
            return ['success' => true, 'message' => sprintf($this->l('¡Reparación completada! Se han añadido %d columna(s) que faltaban.'), $fixes)];
        }

        return ['success' => true, 'message' => $this->l('¡Todo correcto! La estructura de las tablas de VeriFactu está completa.')];
    }

    /**
     * Comprueba en GitHub si hay una nueva versión del módulo.
     * @return array con información sobre la actualización.
     */
    private function checkForUpdate()
    {
        $github_url = 'https://raw.githubusercontent.com/hostinginfoal/verifactu_prestashop/main/version.json';
        
        // Usamos file_get_contents con un timeout para no ralentizar el back office si GitHub no responde.
        $context = stream_context_create(['http' => ['timeout' => 3]]);
        $json_content = Tools::file_get_contents($github_url, false, $context);

        if ($json_content === false) {
            return ['update_available' => false, 'latest_version' => ''];
        }
        
        $data = json_decode($json_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['version'])) {
            return ['update_available' => false, 'latest_version' => ''];
        }

        $latest_version = $data['version'];
        $current_version = $this->version;

        // version_compare() es la forma correcta de comparar números de versión.
        // Devuelve 1 si la primera versión es mayor, -1 si es menor, 0 si son iguales.
        if (version_compare($latest_version, $current_version, '>')) {
            return [
                'update_available' => true,
                'latest_version' => $latest_version
            ];
        }

        return ['update_available' => false, 'latest_version' => $latest_version];
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

        $output = $helper->generateForm(array($this->getConfigForm()));

        // --- INICIO DEL NUEVO CÓDIGO ---
        // Creamos un segundo formulario solo para el botón de herramientas
        $helperTools = new HelperForm();
        $helperTools->show_toolbar = false;
        $helperTools->table = $this->table;
        $helperTools->module = $this;
        $helperTools->submit_action = 'submitCheckDatabase'; // Acción específica para este botón
        $helperTools->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helperTools->token = Tools::getAdminTokenLite('AdminModules');

        $formTools = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Herramientas de Mantenimiento'),
                    'icon' => 'icon-wrench',
                ),
                'buttons' => array(
                        'check_api_status' => array(
                        'title' => $this->l('Comprobar Estado AEAT'),
                        'name' => 'submitCheckApiStatus',
                        'type' => 'submit',
                        'class' => 'btn btn-default pull-right',
                        'icon' => 'process-icon-signal' // Icono de señal
                    ),
                    'check_db' => array(
                        'title' => $this->l('Verificar y Reparar Base de Datos'),
                        'name' => 'submitCheckDatabase',
                        'type' => 'submit',
                        'class' => 'btn btn-default pull-right',
                        'icon' => 'process-icon-cogs'
                    )
                ),
                'description' => $this->l('- Verificar y Reparar Base de datos: Usa este botón si sospechas que al módulo le falta alguna columna en la base de datos debido a una actualización fallida. Esta herramienta comprobará la integridad de las tablas y añadirá las columnas que falten sin borrar ningún dato. - Comprobar estado AEAT: Comprueba si los servidores de Veri*Factu de la AEAT están operativos o por el contrario están caídos temporalmente.')
            ),
        );

        $output .= $helperTools->generateForm(array($formTools));
        
        return $output;
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $taxes = Tax::getTaxes($this->context->language->id, true);
        $tax_options = [];
        foreach ($taxes as $tax) {
            $tax_options[] = [
                'id_tax' => $tax['id_tax'],
                'name' => $tax['name'] . ' (' . rtrim(number_format($tax['rate'], 2), '0.') . '%)'
            ];
        }

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
                        'desc' => $this->l('NIF del obligado a expedir las facturas (Necesario para generar los códigos QR antes del envío a Veri*Factu). Sin guiones ni caractéres especiales. Ej: B11111111 o 40404040D'),
                        'name' => 'VERIFACTU_NIF_EMISOR',
                        'label' => $this->l('NIF del emisor de las facturas'),
                    ),
                    array(
                        'type' => 'html',
                        'name' => 'verifactu_separator_1', // Nombre único
                        'html_content' => '<hr>',
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('¿Su tienda opera desde Canarias, Ceuta o Melilla?'),
                        'name' => 'VERIFACTU_TERRITORIO_ESPECIAL', // <-- CAMBIADO
                        'is_bool' => true,
                        'desc' => $this->l('Active esta opción si su NIF emisor está domiciliado en Canarias, Ceuta o Melilla. Esto desactivará la lógica de OSS y B2B Intracomunitario, y tratará las ventas a la Península y UE como exportaciones.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Sí')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            )
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Impuestos de tipo IGIC'),
                        'name' => 'VERIFACTU_IGIC_TAXES[]', // El [] permite la selección múltiple
                        'desc' => $this->l('Selecciona los impuestos que corresponden al IGIC Canario. Mantén pulsado Ctrl para seleccionar varios. No obligatorio si no trabajas con IGIC.'),
                        'multiple' => true, // Habilita la selección múltiple
                        'options' => array(
                            'query' => $tax_options,
                            'id' => 'id_tax',
                            'name' => 'name'
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Impuestos de tipo IPSI'),
                        'name' => 'VERIFACTU_IPSI_TAXES[]',
                        'desc' => $this->l('Selecciona los impuestos que corresponden al IPSI de Ceuta y Melilla. No obligatorio si no trabajas con IPSI.'),
                        'multiple' => true,
                        'options' => array(
                            'query' => $tax_options,
                            'id' => 'id_tax',
                            'name' => 'name'
                        ),
                    ),
                    
                    array(
                        'type' => 'switch',
                        'label' => $this->l('¿Gestión de Ventanilla Única (OSS)?'),
                        'name' => 'VERIFACTU_USA_OSS',
                        'is_bool' => true,
                        'desc' => $this->l('Active esta opción SÓLO si realiza ventas B2C a otros países de la UE y está dado de alta en el régimen de Ventanilla Única (OSS).'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Activado')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Desactivado')
                            )
                        )
                    ),
                    array(
                        'type' => 'html',
                        'name' => 'verifactu_separator_1', // Nombre único
                        'html_content' => '<hr>',
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Activar modo debug'),
                        'name' => 'VERIFACTU_DEBUG_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Activa esta opción para guardar los logs de los eventos en los Registros/Logs de prestashop. No la actives si no sabes lo que estás haciendo. Dejar activada esta opción puede hacer que tu tabla ps_log augmente mucho de tamaño en poco tiempo.'),
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

        // Obtenemos los valores guardados como JSON y los decodificamos a un array para el formulario.
        $igic_taxes = json_decode(Configuration::get('VERIFACTU_IGIC_TAXES', null, $id_shop_group, $id_shop), true);
        $ipsi_taxes = json_decode(Configuration::get('VERIFACTU_IPSI_TAXES', null, $id_shop_group, $id_shop), true);

        return array(
            'VERIFACTU_API_TOKEN' => Configuration::get('VERIFACTU_API_TOKEN', null, $id_shop_group, $id_shop),
            'VERIFACTU_DEBUG_MODE' => Configuration::get('VERIFACTU_DEBUG_MODE', 0, $id_shop_group, $id_shop),
            'VERIFACTU_NIF_EMISOR' => Configuration::get('VERIFACTU_NIF_EMISOR', null, $id_shop_group, $id_shop),
            'VERIFACTU_IGIC_TAXES[]' => is_array($igic_taxes) ? $igic_taxes : [],
            'VERIFACTU_IPSI_TAXES[]' => is_array($ipsi_taxes) ? $ipsi_taxes : [],
            'VERIFACTU_USA_OSS' => Configuration::get('VERIFACTU_USA_OSS', 0, $id_shop_group, $id_shop),
            'VERIFACTU_TERRITORIO_ESPECIAL' => Configuration::get('VERIFACTU_TERRITORIO_ESPECIAL', 0, $id_shop_group, $id_shop),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        // Tu validación del API token se mantiene igual
        $apiToken = Tools::getValue('VERIFACTU_API_TOKEN');
        if (!empty($apiToken)) {
            $validation = $this->validateApiToken($apiToken);

            if ($validation['success']) {
                // CORRECTO: Añadimos el mensaje al array 'confirmations' del controlador.
                $this->context->controller->confirmations[] = $this->l('¡Perfecto! La conexión con la API de VeriFactu se ha realizado correctamente.');
            } else {
                // CORRECTO: Añadimos el mensaje al array 'errors' del controlador.
                $this->context->controller->errors[] = $this->l('Error de conexión: No se pudo validar la clave de API. Por favor, compruebe que sea correcta. Detalle: ') . $validation['message'];
            }
        } else {
            $this->context->controller->warnings[] = $this->l('El campo "API Token" está vacío.');
        }

        // Obtenemos los valores de los campos del formulario.
        $verifactu_api_token = Tools::getValue('VERIFACTU_API_TOKEN');
        $verifactu_nif_emisor = Tools::getValue('VERIFACTU_NIF_EMISOR');
        $verifactu_debug_mode = Tools::getValue('VERIFACTU_DEBUG_MODE');
        
        // Obtenemos los arrays de los selectores múltiples. Pueden ser 'false' si no se selecciona nada.
        $verifactu_igic_taxes = Tools::getValue('VERIFACTU_IGIC_TAXES', []);
        $verifactu_ipsi_taxes = Tools::getValue('VERIFACTU_IPSI_TAXES', []);
        $verifactu_usa_oss = Tools::getValue('VERIFACTU_USA_OSS');
        $verifactu_territorio_especial = Tools::getValue('VERIFACTU_TERRITORIO_ESPECIAL');

        // Convertimos los arrays a JSON para guardarlos. Si son 'false', los guardamos como un array vacío.
        $igic_json = json_encode(is_array($verifactu_igic_taxes) ? $verifactu_igic_taxes : []);
        $ipsi_json = json_encode(is_array($verifactu_ipsi_taxes) ? $verifactu_ipsi_taxes : []);

        // Tu lógica para guardar en multitienda se mantiene, pero ahora guardamos los nuevos valores.
        $shops = Tools::getValue('checkBoxShopAsso_configuration');
        
        if (empty($shops)) {
            // Si no se selecciona ninguna tienda, se guarda en el contexto actual.
            $id_shop_group = Shop::getContextShopGroupID();
            $id_shop = Shop::getContextShopID();

            Configuration::updateValue('VERIFACTU_API_TOKEN', $verifactu_api_token, false, $id_shop_group, $id_shop);
            Configuration::updateValue('VERIFACTU_NIF_EMISOR', $verifactu_nif_emisor, false, $id_shop_group, $id_shop);
            Configuration::updateValue('VERIFACTU_DEBUG_MODE', $verifactu_debug_mode, false, $id_shop_group, $id_shop);
            Configuration::updateValue('VERIFACTU_IGIC_TAXES', $igic_json, false, $id_shop_group, $id_shop);
            Configuration::updateValue('VERIFACTU_IPSI_TAXES', $ipsi_json, false, $id_shop_group, $id_shop);
            Configuration::updateValue('VERIFACTU_USA_OSS', $verifactu_usa_oss, false, $id_shop_group, $id_shop);
            Configuration::updateValue('VERIFACTU_TERRITORIO_ESPECIAL', $verifactu_territorio_especial, false, $id_shop_group, $id_shop);

        } else {
            // Si se seleccionan tiendas específicas.
            foreach ($shops as $id_shop) {
                $id_shop_group = Shop::getGroupFromShop($id_shop);
                Configuration::updateValue('VERIFACTU_API_TOKEN', $verifactu_api_token, false, $id_shop_group, $id_shop);
                Configuration::updateValue('VERIFACTU_NIF_EMISOR', $verifactu_nif_emisor, false, $id_shop_group, $id_shop);
                Configuration::updateValue('VERIFACTU_DEBUG_MODE', $verifactu_debug_mode, false, $id_shop_group, $id_shop);
                Configuration::updateValue('VERIFACTU_IGIC_TAXES', $igic_json, false, $id_shop_group, $id_shop);
                Configuration::updateValue('VERIFACTU_IPSI_TAXES', $ipsi_json, false, $id_shop_group, $id_shop);
                Configuration::updateValue('VERIFACTU_USA_OSS', $verifactu_usa_oss, false, $id_shop_group, $id_shop);
                Configuration::updateValue('VERIFACTU_TERRITORIO_ESPECIAL', $verifactu_territorio_especial, false, $id_shop_group, $id_shop);
            }
        }
    }

    /**
     * Valida un token de API contra el endpoint TESTKEY.
     *
     * @param string $apiToken El token a validar.
     * @return array Un array con el resultado ['success' => bool, 'message' => string].
     */
    private function validateApiToken($apiToken)
    {
        // La URL de tu nuevo endpoint.
        $apiUrl = 'https://verifactu.infoal.io/api_v2/verifactu/testkey';

        // Inicializamos cURL para hacer la petición.
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true); // Es una petición POST
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiToken, // Cabecera de autorización.
            'Content-Type: application/json'
        ]);
        // Aunque no enviemos cuerpo, es buena práctica especificarlo para peticiones POST.
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([])); 
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Comprobamos si hubo un error de cURL (p.ej., no se pudo conectar al servidor).
        if ($error) {
            return ['success' => false, 'message' => 'Error de cURL: ' . $error];
        }

        // Decodificamos la respuesta JSON.
        $responseData = json_decode($response, true);

        // Verificamos si la respuesta es la esperada (HTTP 200 y response=OK).
        if ($httpCode === 200 && isset($responseData['response']) && $responseData['response'] === 'OK') {
            return ['success' => true, 'message' => 'Token válido.'];
        } else {
            // Si no, construimos un mensaje de error a partir de la respuesta de la API.
            $errorMessage = 'Código de respuesta HTTP: ' . $httpCode;
            if (isset($responseData['error'])) {
                $errorMessage .= ' - ' . $responseData['error'];
            }
            return ['success' => false, 'message' => $errorMessage];
        }
    }

    /**
     * Comprueba el estado de la API de la AEAT a través de nuestro endpoint /status.
     *
     * @return array Un array con el resultado ['success' => bool, 'message' => string].
     */
    public function checkApiStatus()
    {
        // Obtenemos el token de la configuración para la tienda actual
        $id_shop_group = Shop::getContextShopGroupID();
        $id_shop = Shop::getContextShopID();
        $apiToken = Configuration::get('VERIFACTU_API_TOKEN', null, $id_shop_group, $id_shop);

        if (empty($apiToken)) {
            return ['success' => false, 'message' => $this->l('No se ha configurado un API Token.')];
        }
        
        // La URL de tu nuevo endpoint de estado
        $apiUrl = 'https://verifactu.infoal.io/api_v2/verifactu/status';

        $token = Configuration::get('VERIFACTU_API_TOKEN', false, null, $id_shop);

        $headers = [
            'Authorization: Bearer '.$token,
            'Content-Type: application/json',
        ];


        $ch = curl_init();
        curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => 'utf-8',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2TLS,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_HTTPHEADER     => $headers,
            ]
        );

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => 'Error de cURL: ' . $error];
        }

        $responseData = json_decode($response, true);

        $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);
        if ($debug_mode) {
            PrestaShopLogger::addLog('Módulo Verifactu: checkApiStatus - Respuesta de API: ' . $response, 1, null, null, null, true, $id_shop);
        }
        
        // Asumimos que un código 200 y una respuesta con 'status' => 'ok' es un éxito.
        // Adapta esta lógica si tu API devuelve una estructura diferente.
        if ($httpCode === 200 && isset($responseData['estado']) && $responseData['estado'] === 'OPERATIVO') {
            return ['success' => true, 'message' => $this->l('El servicio de la AEAT está operativo.')];
        } else {
            $errorMessage = isset($responseData['message']) ? $responseData['message'] : $this->l('Respuesta inesperada del servidor.');
            return ['success' => false, 'message' => $this->l('El servicio de la AEAT no responde correctamente. Los registros de facturación se enviarán automáticamente cuando se reestablezca el servicio. ') /*. $errorMessage*/];
        }
    }

    // =================================================================
    // LISTADO DE FACTURAS DE VENTA
    // =================================================================
    
    public function renderSalesInvoicesList()
    {
        $fields_list = array(
            'id_order' => array(
                'title' => $this->l('ID Pedido'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
                'search' => false,
            ),
            'number' => array(
                'title' => $this->l('Nº Factura'),
                'callback' => 'getFormattedInvoiceNumberForList',
                'callback_object' => $this,
                'search' => false,
            ),
            'customer' => array('title' => $this->l('Cliente'), 'search' => false, 'orderby' => false),
            'total_paid_tax_incl' => array('title' => $this->l('Total'), 'search' => false, 'type' => 'price'),
            'estado' => array('title' => $this->l('Estado Sinc.'), 'search' => false),
            'verifactuEstadoRegistro' => array('title' => $this->l('Estado VeriFactu'), 'callback' => 'colorEncodeState', 'callback_object' => $this, 'search' => false, 'escape' => false),
            'apiMode' => array('title' => $this->l('Modo API'),'align' => 'text-center','search' => false,),
            'TipoFactura' => array('title' => $this->l('Simplificada'), 'type' => 'bool', 'callback' => 'printSimplifiedInvoiceTick', 'callback_object' => $this, 'search' => false, 'align' => 'center'),
            'anulacion' => array('title' => $this->l('Anulada'), 'type' => 'bool', 'callback' => 'printAnulacionTick', 'callback_object' => $this, 'search' => false, 'align' => 'center'),
            'list_actions' => array('title' => $this->l('Acciones'), 'type' => 'text', 'orderby' => false, 'search' => false, 'callback' => 'printSimpleActions', 'callback_object' => $this, 'search' => false, 'escape' => false)
        );

        $helper = new HelperList();
        $helper->title = $this->l('Estado de facturas de venta');
        $helper->table = 'verifactu_order_invoice';
        $helper->identifier = 'id_order_invoice';
        $helper->simple_header = false;
        $helper->show_toolbar = true;
        $helper->module = $this;
        $helper->no_link = true;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module_verifactu=sales_invoices';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->actions = array();
        //$helper->row_class_callback = array($this, 'getRowClass');
        
        $page = (int) Tools::getValue('page', 1);
        $pagination = (int) Tools::getValue($helper->table . '_pagination', 50);
        $orderBy = Tools::getValue($helper->table . 'Orderby', 'id_order_invoice');
        $orderWay = Tools::getValue($helper->table . 'Orderway', 'DESC');

        $content = $this->getSalesInvoicesListContent($page, $pagination, $orderBy, $orderWay);
        $helper->listTotal = $this->getTotalSalesInvoicesListContent();

        return $helper->generateList($content, $fields_list);
    }

    private function getSalesInvoicesListContent($page, $pagination, $orderBy, $orderWay)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('t.*, oi.number, oi.total_paid_tax_incl, CONCAT(c.firstname, " ", c.lastname) as customer, o.id_order, "view" as list_actions');
        $sql->from('verifactu_order_invoice', 't');
        $sql->leftJoin('order_invoice', 'oi', 't.id_order_invoice = oi.id_order_invoice');
        $sql->leftJoin('orders', 'o', 'oi.id_order = o.id_order');
        $sql->leftJoin('customer', 'c', 'o.id_customer = c.id_customer');

        $whereClauses = [];
        if (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP) {
            $whereClauses[] = 'o.id_shop = ' . (int)$this->context->shop->id;
        }
        
        // Aquí puedes añadir más filtros si los necesitas en el futuro
        // ...

        if (!empty($whereClauses)) {
            $sql->where(implode(' AND ', $whereClauses));
        }

        $sql->orderBy('t.`' . pSQL($orderBy) . '` ' . pSQL($orderWay));
        $sql->limit($pagination, ($page - 1) * $pagination);
        
        return $db->executeS($sql);
    }

    private function getTotalSalesInvoicesListContent()
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('COUNT(t.id_order_invoice)');
        $sql->from('verifactu_order_invoice', 't');
        $sql->leftJoin('order_invoice', 'oi', 't.id_order_invoice = oi.id_order_invoice');
        $sql->leftJoin('orders', 'o', 'oi.id_order = o.id_order');
        $sql->leftJoin('customer', 'c', 'o.id_customer = c.id_customer');
        
        $whereClauses = [];
        if (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP) {
            $whereClauses[] = 'o.id_shop = ' . (int)$this->context->shop->id;
        }

        if (!empty($whereClauses)) {
            $sql->where(implode(' AND ', $whereClauses));
        }

        return (int)$db->getValue($sql);
    }

    // =================================================================
    // LISTADO DE FACTURAS DE ABONO
    // =================================================================

    public function renderCreditSlipsList()
    {
        $fields_list = array(
            'id_order' => array(
                'title' => $this->l('ID Pedido'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
                'search' => false,
            ),
            'id_order_slip' => array(
                'title' => $this->l('Nº Abono'),
                'callback' => 'getFormattedSlipNumberForList',
                'callback_object' => $this,
                'search' => false,
            ),
            'customer' => array('title' => $this->l('Cliente'), 'search' => false, 'orderby' => false),
            'total_products_tax_incl' => array('title' => $this->l('Total'), 'search' => false, 'type' => 'price'),
            'estado' => array('title' => $this->l('Estado Sinc.'), 'search' => false,),
            'verifactuEstadoRegistro' => array('title' => $this->l('Estado VeriFactu'), 'callback' => 'colorEncodeState', 'callback_object' => $this, 'search' => false, 'escape' => false),
            'apiMode' => array('title' => $this->l('Modo API'),'align' => 'text-center','search' => false,),
            'TipoFactura' => array('title' => $this->l('Simplificada'), 'type' => 'bool', 'callback' => 'printSimplifiedInvoiceTick', 'callback_object' => $this, 'search' => false, 'align' => 'center'),
            'anulacion' => array('title' => $this->l('Anulada'), 'type' => 'bool', 'callback' => 'printAnulacionTick', 'callback_object' => $this, 'search' => false, 'align' => 'center'),
            'list_actions' => array('title' => $this->l('Acciones'), 'type' => 'text', 'orderby' => false, 'search' => false, 'callback' => 'printSimpleActions', 'callback_object' => $this, 'search' => false, 'escape' => false)
        );

        $helper = new HelperList();
        $helper->title = $this->l('Estado de facturas por abono');
        $helper->table = 'verifactu_order_slip';
        $helper->identifier = 'id_order_slip';
        $helper->simple_header = false;
        $helper->show_toolbar = true;
        $helper->module = $this;
        $helper->no_link = true;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module_verifactu=credit_slips';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->actions = array();
        //$helper->row_class_callback = array($this, 'getRowClass');
        
        $page = (int) Tools::getValue('page', 1);
        $pagination = (int) Tools::getValue($helper->table . '_pagination', 50);
        $orderBy = Tools::getValue($helper->table . 'Orderby', 'id_order_slip');
        $orderWay = Tools::getValue($helper->table . 'Orderway', 'DESC');

        $content = $this->getCreditSlipsListContent($page, $pagination, $orderBy, $orderWay);
        $helper->listTotal = $this->getTotalCreditSlipsListContent();

        return $helper->generateList($content, $fields_list);
    }

    private function getCreditSlipsListContent($page, $pagination, $orderBy, $orderWay)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('t.*, os.id_order_slip, os.total_products_tax_incl, CONCAT(c.firstname, " ", c.lastname) as customer, o.id_order , "view" as list_actions');
        $sql->from('verifactu_order_slip', 't');
        $sql->leftJoin('order_slip', 'os', 't.id_order_slip = os.id_order_slip');
        $sql->leftJoin('orders', 'o', 'os.id_order = o.id_order');
        $sql->leftJoin('customer', 'c', 'o.id_customer = c.id_customer');
        
        $whereClauses = [];
        if (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP) {
            $whereClauses[] = 'o.id_shop = ' . (int)$this->context->shop->id;
        }

        if (!empty($whereClauses)) {
            $sql->where(implode(' AND ', $whereClauses));
        }

        $sql->orderBy('t.`' . pSQL($orderBy) . '` ' . pSQL($orderWay));
        $sql->limit($pagination, ($page - 1) * $pagination);
        
        return $db->executeS($sql);
    }

    private function getTotalCreditSlipsListContent()
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('COUNT(t.id_order_slip)');
        $sql->from('verifactu_order_slip', 't');
        $sql->leftJoin('order_slip', 'os', 't.id_order_slip = os.id_order_slip');
        $sql->leftJoin('orders', 'o', 'os.id_order = o.id_order');
        $sql->leftJoin('customer', 'c', 'o.id_customer = c.id_customer');
        
        $whereClauses = [];
        if (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP) {
            $whereClauses[] = 'o.id_shop = ' . (int)$this->context->shop->id;
        }

        if (!empty($whereClauses)) {
            $sql->where(implode(' AND ', $whereClauses));
        }

        return (int)$db->getValue($sql);
    }

    

    // =================================================================
    // LISTADO DE REGISTROS DE FACTURACION
    // =================================================================
    
    /**
     * Renderiza la lista de Registros de Facturación (Versión Corregida).
     */
    public function renderList()
    {
        // --- INICIO DE LA MODIFICACIÓN ---
        // 1. Definimos la nueva estructura de columnas.
        $fields_list = array(
            'InvoiceNumber' => array(
                'title' => $this->l('Nº Factura'),
                'type' => 'text',
                'orderby' => true,
                'search' => false,
            ),
            'BuyerName' => array(
                'title' => $this->l('Cliente'),
                'type' => 'text',
                'search' => false,
            ),
            'TipoOperacion' => array(
                'title' => $this->l('Operación'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
            ),
            'TipoFactura' => array(
                'title' => $this->l('Tipo'),
                'type' => 'text',
                'align' => 'center',
                'search' => false,
                'orderby' => false,
            ),
            'FacturaSinIdentifDestinatarioArt61d' => array(
                'title' => $this->l('Simplif.'),
                'type' => 'bool',
                'active' => 'status',
                'align' => 'center',
                'search' => false,
                'orderby' => false,
            ),
            
            'TotalTaxOutputs' => array(
                'title' => $this->l('Impuestos'),
                'type' => 'price',
                'search' => false,
            ),
            'InvoiceTotal' => array(
                'title' => $this->l('Total'),
                'type' => 'price',
                'search' => false,
            ),
            'EstadoRegistro' => array(
                'title' => $this->l('Estado'),
                'type' => 'text',
                'search' => false,
                'callback' => 'colorEncodeState', // Nombre de nuestra nueva función
                'callback_object' => $this,      // Le decimos que la función está en este objeto
                'escape' => false 
            ),
            'apiMode' => array(
                'title' => $this->l('Modo API'),
                'align' => 'text-center',
                'search' => false, // Puedes ponerlo a true y añadir 'apiMode' a la lista $allowedFilters más abajo
            ),
            'DescripcionErrorRegistro' => array(
                'title' => $this->l('Error'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
                'callback' => 'printErrorColumn',
                'callback_object' => $this,
                'escape' => false,
            ),
            // 2. Añadimos la columna de acciones con su propio callback.
            'list_actions' => array(
                'title' => $this->l('Acciones'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
                'callback' => 'printActionsColumn',
                'callback_object' => $this,
                'escape' => false,
            )
        );

        $helper = new HelperList();

        $helper->title = $this->l('Registros de facturación remitidos a Veri*Factu');
        $helper->table = 'verifactu_reg_fact';
        $helper->identifier = 'id_reg_fact'; // El campo ID de la fila
        $helper->simple_header = false;
        $helper->show_toolbar = true;
        $helper->module = $this;
        $helper->no_link = true;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name. '&tab_module_verifactu=reg_facts';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->actions = array();
        
        $page = (int) Tools::getValue('page', 1);
        $pagination = (int) Tools::getValue($helper->table . '_pagination', 50);
        $orderBy = Tools::getValue($helper->table . 'Orderby', 'id_reg_fact');
        $orderWay = Tools::getValue($helper->table . 'Orderway', 'DESC');

        $content = $this->getListContent($helper->table, $page, $pagination, $orderBy, $orderWay);
        $helper->listTotal = $this->getTotalListContent($helper->table);

        return $helper->generateList($content, $fields_list);
        // --- FIN DE LA MODIFICACIÓN ---
    }

    /**
     * Obtiene los datos para el HelperList.
     */
    private function getListContent($table, $page, $pagination, $orderBy, $orderWay)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        // --- INICIO MODIFICACIÓN QUERY ---
        // Seleccionamos solo los campos que necesitamos.
        $sql->select('
            t.*, o.id_order, "view" as list_actions
        ');
        $sql->from($table, 't');
        // Hacemos un LEFT JOIN para poder obtener el id_order desde la factura o el abono
        $sql->leftJoin('order_invoice', 'oi', 't.id_order_invoice = oi.id_order_invoice AND t.tipo = "alta"');
        $sql->leftJoin('order_slip', 'os', 't.id_order_invoice = os.id_order_slip AND t.tipo = "abono"');
        $sql->leftJoin('orders', 'o', 'o.id_order = IF(t.tipo = "alta", oi.id_order, os.id_order)');


        // Lista blanca de campos permitidos para ordenar
        $allowedOrderBy = [
            'id_reg_fact', 'InvoiceNumber', 'BuyerName', 'InvoiceTotal', 'EstadoRegistro'
        ];
        if (!in_array($orderBy, $allowedOrderBy)) {
            $orderBy = 'id_reg_fact'; // Valor por defecto seguro
        }
        $orderWay = strtoupper($orderWay) === 'ASC' ? 'ASC' : 'DESC';
        $sql->orderBy('`' . pSQL($orderBy) . '` ' . pSQL($orderWay));

        $whereClauses = [];

        if (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP) {
            $whereClauses[] = 'o.id_shop = ' . (int)$this->context->shop->id;
        }

        $filters = Tools::getAllValues();
        foreach ($filters as $key => $value) {
            if (strpos($key, $table . 'Filter_') === 0 && !empty($value)) {
                $field = substr($key, strlen($table . 'Filter_'));
                
                // Lista blanca de campos permitidos para filtrar
                $allowedFilters = ['InvoiceNumber', 'BuyerName', 'EstadoRegistro'];
                if (in_array($field, $allowedFilters)) {
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

       $sql->leftJoin('order_invoice', 'oi', 't.id_order_invoice = oi.id_order_invoice AND t.tipo = "alta"');
        $sql->leftJoin('order_slip', 'os', 't.id_order_invoice = os.id_order_slip AND t.tipo = "abono"');
        $sql->leftJoin('orders', 'o', 'o.id_order = IF(t.tipo = "alta", oi.id_order, os.id_order)');

        $whereClauses = [];

        if (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP) {
            $whereClauses[] = 'o.id_shop = ' . (int)$this->context->shop->id;
        }

        $filters = Tools::getAllValues();
        foreach ($filters as $key => $value) {
            if (strpos($key, $table . 'Filter_') === 0 && !empty($value)) {
                $field = substr($key, strlen($table . 'Filter_'));

                // Usar la misma lista blanca de campos que en getListContent
                $allowedFilters = ['InvoiceNumber', 'BuyerName', 'EstadoRegistro'];
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


    // =================================================================
    // FUNCIONES CALLBACK REUTILIZABLES
    // =================================================================

    /**
     * Callback para mostrar un icono de tick/cruz para el estado de anulación.
     *
     * @param int $value El valor del campo 'anulacion' (0 o 1).
     * @param array $row La fila completa de datos.
     * @return string El HTML para el icono.
     */
    public function printAnulacionTick($value, $row)
    {
        if ($value) {
            return '<a class="list-action-enable action-enabled"><i class="icon-check"></i></a>';
        } else {
            return '<a class="list-action-enable action-disabled"><i class="icon-remove"></i></a>';
        }
    }

    /**
     * Callback para mostrar un icono de tick si la factura es simplificada ('F2').
     *
     * @param string $value El valor del campo 'TipoFactura'.
     * @param array $row La fila completa de datos.
     * @return string El HTML para el icono.
     */
    public function printSimplifiedInvoiceTick($value, $row)
    {
        // La factura simplificada se marca con el código "F2"
        if ($value === 'F2' || $value === 'R5') {
            return '<a class="list-action-enable action-enabled"><i class="icon-check"></i></a>';
        } else {
            return '<a class="list-action-enable action-disabled"><i class="icon-remove"></i></a>';
        }
    }

    /**
     * Callback para los botones de acción de los listados de facturas y abonos (sin "Ver Detalle").
     */
    public function printSimpleActions($value, $row)
    {
        $actions = '';

        // Botón Enlace QR
        if (!empty($row['urlQR'])) {
            $actions .= '<a class="btn btn-default" href="' . Tools::safeOutput($row['urlQR']) . '" target="_blank" title="' . $this->l('Ver QR') . '"><i class="icon-qrcode"></i></a> ';
        }

        // Botón Corregir
        if (!empty($row['id_order'])) {
            $order_url = $this->context->link->getAdminLink('AdminOrders', true, [], [
                'id_order' => (int)$row['id_order'],
                'vieworder' => ''
            ]);
            $actions .= '<a class="btn btn-default" href="' . $order_url . '" target="_blank" title="' . $this->l('Ir al Pedido') . '"><i class="icon-pencil"></i></a>';
        }

        // Botón Reenviar
        if (isset($row['verifactuEstadoRegistro']) && isset($row['estado']) && $row['verifactuEstadoRegistro'] !== 'Correcto' && $row['estado'] == 'sincronizado') {
            // Determinamos si es una factura de venta (alta) o un abono
            $type = isset($row['id_order_invoice']) ? 'alta' : 'abono';

            $actions .= '<a class="btn btn-default button-resend-verifactu"  data-id_order="' . (int)$row['id_order'] . '" data-type="' . $type . '" title="' . $this->l('Reenviar a VeriFactu') . '"><i class="icon-refresh"></i></a>';
        }

        return $actions;
    }

    /**
     * Callback para dar formato y color a la celda de estado.
     *
     * @param string $value El valor del campo 'EstadoRegistro'.
     * @param array $row La fila completa de datos.
     * @return string El HTML para la celda.
     */
    public function colorEncodeState($value, $row)
    {
        if (isset($row['estado']) && $row['estado'] == 'pendiente')
        {
            $class = 'verifactu_pendiente';
            return '<span class="' . $class . '">' . Tools::safeOutput($value) . '</span>';
        }
        else
        {
            if ($value)
            {
                $class = '';
                switch ($value) {
                    case 'Correcto':
                        $class = 'verifactu_correct';
                        break;
                    case 'Incorrecto':
                        $class = 'verifactu_error';
                        break;
                    case 'AceptadoConErrores':
                        $class = 'verifactu_warning';
                        break;
                }

                // Devolvemos el texto del estado dentro de un span con la clase correspondiente.
                return '<span class="' . $class . '">' . Tools::safeOutput($value) . '</span>';
            }
            else
            {
                return '--';
            }
        }
        
        
    }

    /**
     * Función callback para renderizar la columna de error.
     * @param string $value El valor del campo (no se usa directamente aquí).
     * @param array $row La fila completa de datos.
     * @return string El HTML para la celda.
     */
    public function printErrorColumn($value, $row)
    {
        $errorCode = $row['CodigoErrorRegistro'];
        $errorDesc = $row['DescripcionErrorRegistro'];

        if (!empty($errorCode) || !empty($errorDesc)) {
            return trim($errorCode . ' - ' . $errorDesc);
        }
        return '--';
    }

    /**
     * Función callback para generar los botones de acción para cada fila.
     * @param int $id El ID del registro actual (no se usa directamente, lo obtenemos de la fila).
     * @param array $row La fila completa de datos.
     * @return string El HTML para los botones.
     */
    public function printActionsColumn($value, $row)
    {
        $actions = '';

        // Botón Ver Detalle (usando la acción 'view' que ya funciona)
        $detail_url = $this->context->link->getAdminLink('AdminVerifactuDetail', true, [], [
            'id_reg_fact' => $row['id_reg_fact'],
            'viewverifactu_reg_fact' => ''
        ]);
        $actions .= '<a class="btn btn-default" href="' . $detail_url . '" title="' . $this->l('Ver detalle') . '"><i class="icon-eye"></i></a> ';

        // Botón Enlace QR (condicional)
        // Se mostrará solo si el estado es 'Correcto' y si el campo 'urlQR' no está vacío.
        if (!empty($row['urlQR']) && ($row['EstadoRegistro'] === 'Correcto' || $row['EstadoRegistro'] === 'AceptadoConErrores')) {
            $actions .= '<a class="btn btn-default" href="' . Tools::safeOutput($row['urlQR']) . '" target="_blank" title="' . $this->l('Ver QR') . '"><i class="icon-qrcode"></i></a> ';
        }

        // Botón Corregir (condicional)
        if (!empty($row['id_order'])) {
            // Construimos el enlace a la página de edición del pedido
            $order_url = $this->context->link->getAdminLink('AdminOrders', true, [], [
                'id_order' => (int)$row['id_order'],
                'vieworder' => ''
            ]);
            
            // Añadimos el botón con target="_blank" para que se abra en una nueva pestaña
            $actions .= '<a class="btn btn-default" href="' . $order_url . '" target="_blank" title="' . $this->l('Corregir en el pedido') . '"><i class="icon-pencil"></i> </a>';
        }

        return $actions;
    }

    /**
     * Función callback para generar los botones de acción para cada fila.
     * @param int $id El ID del registro actual.
     * @param array $row La fila completa de datos.
     * @return string El HTML para los botones.
     */
    public function displayActionsColumn($id, $row)
    {
        $actions = '';

        // Botón Ver Detalle (enlace de ejemplo, podrías apuntar a un controlador específico)
        $detailUrl = '#'; // TODO: Reemplazar con el enlace real al detalle si lo implementas.
        $actions .= '<a class="btn btn-default" href="' . $detailUrl . '" title="' . $this->l('Ver detalle') . '" onclick="alert(\'Detalle del registro ID: ' . (int)$id . '\'); return false;"><i class="icon-eye-open"></i></a> ';

        // Botón Enlace QR
        if (!empty($row['urlQR'])) {
            $actions .= '<a class="btn btn-default" href="' . $row['urlQR'] . '" title="' . $this->l('Enlace QR') . '" target="_blank"><i class="icon-qrcode"></i></a>';
        }

        return $actions;
    }

    /**
     * Callback para mostrar el número de factura formateado.
     *
     * @param int $id_order_invoice El ID de la factura.
     * @param array $row La fila completa de datos.
     * @return string El número de factura formateado.
     */
    public function getFormattedInvoiceNumberForList($value, $row)
    {
        // Reutilizamos la lógica que ya tienes en la clase ApiVerifactu
        $api_verifactu = new ApiVerifactu(null, false, $this->context->shop->id);
        return $api_verifactu->getFormattedInvoiceNumber($row['id_order_invoice']);
    }

    /**
     * Callback para mostrar el número de abono formateado.
     *
     * @param int $id_order_slip El ID del abono.
     * @param array $row La fila completa de datos.
     * @return string El número de abono formateado.
     */
    public function getFormattedSlipNumberForList($value, $row)
    {
        // Reutilizamos la lógica que ya tienes en la clase ApiVerifactu
        $api_verifactu = new ApiVerifactu(null, false, $this->context->shop->id);
        return $api_verifactu->getFormattedCreditSlipNumber($row['id_order_slip']);
    }

    /**
     * Callback para mostrar un texto por defecto si el valor es nulo o vacío.
     *
     * @param mixed $value El valor de la celda.
     * @return string El valor o un guión si está vacío.
     */
    public function displayDefaultText($value)
    {
        if (empty($value)) {
            return '--';
        }
        return Tools::safeOutput($value);
    }


    // =================================================================
    // HOOKS
    // =================================================================
    

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

        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) 
        {
            if ('verifactu' === $filterName) {
                $searchQueryBuilder->andWhere('vi.`verifactuEstadoRegistro` LIKE :verifactu_filter');
                $searchQueryBuilder->setParameter('verifactu_filter', '%' . $filterValue . '%');
            }
        }
    }
    

    public function hookActionAdminControllerSetMedia($params)
    {
        // On every pages
        $this->context->controller->addCSS($this->_path.'views/css/back.css');
        $this->context->controller->addJS($this->_path.'views/js/back.js');
        $this->context->controller->addJS('https://cdn.jsdelivr.net/npm/sweetalert2@11');

        //foreach (Language::getLanguages() as $language) {
        //    $lang = Tools::strtoupper($language['iso_code']);
        //}

        $lang = Tools::strtoupper($this->context->language->iso_code);

        if ($lang == '') $lang = 'ES';

        Media::addJsDef(array('verifactu' => array('lang' => $lang)));

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

        $show_status_check_button = false;
        if ($result && isset($result['estado']) && $result['estado'] == 'pendiente') {
            $show_status_check_button = true;
        }

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
                    $imgQR = __PS_BASE_URI__ . 'img/tmp/' . $tmp_filename;
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
            'show_status_check_button' => $show_status_check_button,
            'current_url' => 'index.php?controller=AdminModules&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
        ));

        return $this->display(__FILE__, 'views/templates/admin/order_side.tpl');
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
        $qr_code_path_for_smarty = null; // Variable que pasaremos a Smarty
        // Definimos el nombre de archivo fuera del try para usarlo al construir la URL
        $tmp_filename = 'verifactu_qr_' . $order_invoice->id . '_' . time() . '.png';

        try {
            // Ruta del sistema de archivos (para escribir el archivo)
            $tmp_dir = _PS_TMP_IMG_DIR_;
            $qr_code_path = $tmp_dir . $tmp_filename;

            QRcode::png($url_to_encode, $qr_code_path, QR_ECLEVEL_L, 4, 2);
            
            // Seguridad adicional: Aseguramos permisos de lectura
            @chmod($qr_code_path, 0644);

            if (file_exists($qr_code_path)) {
                // Guardamos la ruta del *archivo* para borrarlo después
                self::$temp_qr_files[] = $qr_code_path;
                
                // *** LA SOLUCIÓN ***
                // Construimos la URL pública completa y esto es lo que pasamos a Smarty
                $qr_code_path_for_smarty = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'img/tmp/' . $tmp_filename;

            } else {
                // Si falla, la variable de Smarty seguirá siendo null
                PrestaShopLogger::addLog('Módulo Verifactu: El archivo QR se generó pero no se encontró en ' . $qr_code_path, 2, null, null, null, true, $id_shop);
            }

        } catch (Exception $e) {
            PrestaShopLogger::addLog('Módulo Verifactu: Error al generar el archivo QR: ' . $e->getMessage(), 3, null, null, null, true, $id_shop);
            // $qr_code_path_for_smarty sigue siendo null
        }
        
        // 5. Asignamos la ruta a la plantilla.
        $this->context->smarty->assign([
            'verifactu_qr_code_path' => $qr_code_path_for_smarty, // Pasamos la URL pública
            'verifactu_url' => $url_to_encode
        ]);
        
        
        if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) 
        {
            return $this->context->smarty->fetch('module:verifactu/views/templates/hook/invoice_qr.tpl');
        }
        else
        {
            return $this->display(__FILE__, 'views/templates/hook/invoice_qr.tpl');
        }
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
    public function hookDisplayPDFOrderSlip($params)
    {

        // 1. Obtenemos el objeto OrderSlip del array de parámetros.
        $order_slip = $params['object'];
        if (!Validate::isLoadedObject($order_slip)) {
            return '';
        }

        // 2. Cargamos el objeto Order asociado al abono para acceder a id_shop.
        $order = new Order($order_slip->id_order);
        if (!Validate::isLoadedObject($order)) {
            return ''; // Si no se puede cargar el pedido, no continuamos.
        }
        // 3. Obtenemos el id_shop de forma segura desde la propiedad pública del objeto Order.
        $id_shop = (int)$order->id_shop;


        // El resto de la lógica es la que ya tenías, ¡y era correcta!
        require_once(dirname(__FILE__).'/lib/phpqrcode/qrlib.php');

        //$id_shop = (int)$order_slip->id_shop;

        $sql = new DbQuery();
        $sql->select('urlQR');
        $sql->from('verifactu_order_slip');
        $sql->where('id_order_slip = ' . (int)$order_slip->id);
        $url_to_encode = Db::getInstance()->getValue($sql);

        if (empty($url_to_encode)) {
            return '';
        }

        $qr_code_path_for_smarty = null; // Variable que pasaremos a Smarty
        // Definimos el nombre de archivo fuera del try para usarlo al construir la URL
        $tmp_filename = 'verifactu_qr_slip_' . $order_slip->id . '_' . time() . '.png';
        
        try {
            // Ruta del sistema de archivos (para escribir el archivo)
            $tmp_dir = _PS_TMP_IMG_DIR_;
            $qr_code_path = $tmp_dir . $tmp_filename;

            QRcode::png($url_to_encode, $qr_code_path, QR_ECLEVEL_L, 4, 2);
            
            // Seguridad adicional: Aseguramos permisos de lectura
            @chmod($qr_code_path, 0644);

            if (file_exists($qr_code_path)) {
                // Guardamos la ruta del *archivo* para borrarlo después
                self::$temp_qr_files[] = $qr_code_path;

                // *** LA SOLUCIÓN ***
                // Construimos la URL pública completa y esto es lo que pasamos a Smarty
                $qr_code_path_for_smarty = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'img/tmp/' . $tmp_filename;

            } else {
                 PrestaShopLogger::addLog('Módulo Verifactu: El archivo QR (abono) se generó pero no se encontró en ' . $qr_code_path, 2, null, null, null, true, $id_shop);
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Módulo Verifactu: Error al generar QR para abono: ' . $e->getMessage(), 3, null, null, null, true, $id_shop);
            // $qr_code_path_for_smarty sigue siendo null
        }
        
        $this->context->smarty->assign([
            'verifactu_qr_code_path' => $qr_code_path_for_smarty // Pasamos la URL pública
        ]);
        
        if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) 
        {
            return $this->context->smarty->fetch('module:verifactu/views/templates/hook/invoice_qr.tpl');
        }
        else
        {
            return $this->display(__FILE__, 'views/templates/hook/invoice_qr.tpl');
        }
    }

    /**
     * Hook que se ejecuta después de renderizar el PDF del abono.
     * Se usa para borrar los archivos de QR temporales que hemos creado.
     */
    public function hookActionPDFOrderSlipRender($params)
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
     * Hook para modificar la lista de pedidos en versiones de PrestaShop < 1.7.7.0
     */
    public function hookActionAdminOrdersListingFieldsModifier($params)
    {
        if (isset($params['fields']['total_paid_tax_incl'])) {
            $params['fields']['total_paid_tax_incl']['filter_key'] = 'a!total_paid_tax_incl';
        }

        // 1. Añadir la nueva columna "Verifactu" a la definición de la lista.
        $params['fields']['verifactu'] = [
            'title' => $this->l('Verifactu'),
            'align' => 'text-center',
            'class' => 'fixed-width-xs',
            'orderby' => false, // No se puede ordenar fácilmente en legacy
            'search' => false,
        ];

        // 2. Modificar la consulta SQL para obtener los datos de la nueva columna.
        // Asegúrate de que `verifactuEstadoRegistro` es el nombre correcto en tu tabla.
        $params['select'] .= ', IFNULL(vi.verifactuEstadoRegistro, IF(i.id_order_invoice IS NULL, "Sin factura", "No enviada")) AS verifactu';
        
        // 3. Añadir los JOINS necesarios a la consulta.
        // El JOIN a 'order_invoice' debe hacerse sobre el alias 'a' de la tabla de pedidos.
        $params['join'] .= ' LEFT JOIN `' . _DB_PREFIX_ . 'order_invoice` i ON (a.`id_order` = i.`id_order`)';
        $params['join'] .= ' LEFT JOIN `' . _DB_PREFIX_ . 'verifactu_order_invoice` vi ON (i.`id_order_invoice` = vi.`id_order_invoice`)';
    }
    
    //---------------FUNCIONES LEGACY PARA VERSIONES ENTRE 1.7.0 y 1.7.7.0 -----------------------------------------------------------------
    
    /**
     * Hook para mostrar el panel de VeriFactu en la página de un pedido (versiones < 1.7.7.0).
     * Este es el equivalente "legacy" de displayAdminOrderSide.
     *
     * @param array $params Parámetros del hook.
     * @return string HTML renderizado.
     */
    public function hookDisplayAdminOrder($params)
    {
        // 1. Obtener el ID del pedido de forma segura desde los parámetros del hook.
        $id_order = 0;
        if (isset($params['id_order'])) {
            $id_order = (int)$params['id_order'];
        } elseif (isset($params['order']) && Validate::isLoadedObject($params['order'])) {
            $id_order = (int)$params['order']->id;
        }

        // Si no podemos obtener un ID de pedido válido, no mostramos nada.
        if (!$id_order) {
            return '';
        }

        // 2. Incluir la librería para generar códigos QR.
        require_once(dirname(__FILE__).'/lib/phpqrcode/qrlib.php');

        // 3. Consultar la base de datos para obtener el estado de VeriFactu de la factura del pedido.
        $sql = new DbQuery();
        $sql->select('voi.*, oi.id_order_invoice');
        $sql->from('order_invoice', 'oi');
        $sql->leftJoin('verifactu_order_invoice', 'voi', 'oi.id_order_invoice = voi.id_order_invoice');
        $sql->where('oi.id_order = ' . $id_order);
        $result = Db::getInstance()->getRow($sql);

        // 4. Preparar las variables para la plantilla, gestionando el caso de que no exista factura.
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
            // El pedido AÚN NO tiene factura, asignamos valores por defecto.
            $urlQR = null;
            $verifactuEstadoEnvio = 'Sin factura';
            $verifactuEstadoRegistro = 'N/A';
            $verifactuCodigoErrorRegistro = null;
            $verifactuDescripcionErrorRegistro = null;
            $anulacion = 0;
            $estado = null;
            $TipoFactura = null;
            $id_order_invoice = null;
        }

        // 5. Generar la imagen del código QR si existe la URL.
        $imgQR = null;
        if (!empty($urlQR)) {
            try {
                $tmp_dir = _PS_TMP_IMG_DIR_;
                $tmp_filename = 'verifactu_qr_' . $id_order . '_' . time() . '.png';
                $imgQR_path = $tmp_dir . $tmp_filename;

                QRcode::png($urlQR, $imgQR_path, QR_ECLEVEL_L, 4, 2);

                if (file_exists($imgQR_path)) {
                    // Guardamos la ruta del archivo temporal para poder borrarlo después.
                    self::$temp_qr_files[] = $imgQR_path;
                    // Creamos la URL pública para mostrar la imagen.
                    $imgQR = __PS_BASE_URI__ . 'img/tmp/' . $tmp_filename;
                }
            } catch (Exception $e) {
                PrestaShopLogger::addLog('Módulo Verifactu: Error al generar el archivo QR: ' . $e->getMessage(), 3);
                $imgQR = null;
            }
        }
        
        // Botón para comprobar estado (solo si está pendiente en la API)
        $show_status_check_button = ($result && isset($result['estado']) && $result['estado'] == 'pendiente');

        // 6. Asignar todas las variables a la plantilla Smarty.
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
            'show_status_check_button' => $show_status_check_button,
        ));

        // 7. Renderizar y devolver el contenido de la plantilla.
        // Usamos la misma plantilla que para displayAdminOrderSide.
        return $this->display(__FILE__, 'views/templates/admin/order_legacy.tpl');
    }
    
}