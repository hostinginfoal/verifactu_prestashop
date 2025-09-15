<?php
use Verifactu\VerifactuClasses\ApiVerifactu;
use PrestaShop\PrestaShop\Adapter\Entity\Order;
use PrestaShop\PrestaShop\Adapter\Entity\Validate;
use PrestaShop\PrestaShop\Adapter\Entity\Configuration;

class AdminVerifactuAjaxController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->ajax = true; // Muy importante para respuestas Ajax
    }

    public function displayAjaxEnviarVerifactu()
    {
        $id_order = (int)Tools::getValue('id_order');
        if (!$id_order) {
            $this->ajaxDie(json_encode(['error' => 'ID de pedido no válido.']));
        }

        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            $this->ajaxDie(json_encode(['error' => 'No se pudo cargar el pedido.']));
        }

        $id_shop = (int)$order->id_shop;
        
        // Obtenemos la configuración para la tienda del pedido.
        $api_token = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
        $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);

        // Instanciamos ApiVerifactu con la configuración correcta.
        $av = new ApiVerifactu($api_token, $debug_mode, $id_shop);
        $response = $av->sendAltaVerifactu($id_order);

        // Usamos ajaxDie para una respuesta limpia en JSON.
        $this->ajaxDie($response);
    }

    public function displayAjaxEnviarSustitutivaVerifactu()
    {
        $id_order = (int)Tools::getValue('id_order');
        if (!$id_order) {
            $this->ajaxDie(json_encode(['error' => 'ID de pedido no válido.']));
        }

        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            $this->ajaxDie(json_encode(['error' => 'No se pudo cargar el pedido.']));
        }

        $id_shop = (int)$order->id_shop;
        
        // Obtenemos la configuración para la tienda del pedido.
        $api_token = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
        $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);

        // Instanciamos ApiVerifactu con la configuración correcta.
        $av = new ApiVerifactu($api_token, $debug_mode, $id_shop);
        $response = $av->sendAltaVerifactu($id_order,'sustitutiva');

        // Usamos ajaxDie para una respuesta limpia en JSON.
        $this->ajaxDie($response);
    }

    public function displayAjaxCheckDNI()
    {
        $id_order = (int)Tools::getValue('id_order');
        if (!$id_order) {
            $this->ajaxDie(json_encode(['error' => 'ID de pedido no válido.']));
        }

        // CAMBIO MULTITIENDA: Lógica para obtener el contexto de la tienda.
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            $this->ajaxDie(json_encode(['error' => 'No se pudo cargar el pedido.']));
        }
        
        $id_shop = (int)$order->id_shop;

        $api_token = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
        $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);

        $av = new ApiVerifactu($api_token, $debug_mode, $id_shop);
        $response = $av->checkDNI($id_order);

        $this->ajaxDie($response);
    }

    public function displayAjaxAnularVerifactu()
    {
        $id_order = (int)Tools::getValue('id_order');
        if (!$id_order) {
            $this->ajaxDie(json_encode(['error' => 'ID de pedido no válido.']));
        }
        
        // CAMBIO MULTITIENDA: Lógica para obtener el contexto de la tienda.
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            $this->ajaxDie(json_encode(['error' => 'No se pudo cargar el pedido.']));
        }
        
        $id_shop = (int)$order->id_shop;

        $api_token = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
        $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);

        $av = new ApiVerifactu($api_token, $debug_mode, $id_shop);
        $response = $av->sendAnulacionVerifactu($id_order);

        $this->ajaxDie($response);
    }

    /**
     * Esta es la acción que nuestro JavaScript llamará.
     */
    public function displayAjaxCheckPendingStatus()
    {
        $id_shop = (int)$this->context->shop->id;
        
        if (!$id_shop) {
            // Si el contexto es "Todas las tiendas", podríamos decidir no hacer nada o manejarlo de alguna manera.
            // Por ahora, asumimos que siempre se opera en el contexto de una tienda específica.
            $this->ajaxDie(json_encode(['error' => 'Por favor, seleccione una tienda específica para realizar esta acción.']));
        }

        $api_token = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
        $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);

        $av = new ApiVerifactu($api_token, $debug_mode, $id_shop);
        
        // NOTA: Para que esto funcione perfectamente, el método checkPendingInvoices()
        // en ApiVerifactu debería aceptar $id_shop para filtrar solo las facturas de esa tienda.
        // Ver punto clave adicional abajo.
        $response = $av->checkPendingInvoices(); 

        $this->ajaxDie($response);
    }

    protected function ajaxDie($value = null, $controller = null, $method = null)
    {
        header('Content-Type: application/json');
        parent::ajaxDie($value, $controller, $method);
    }
}