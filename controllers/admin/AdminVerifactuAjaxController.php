<?php
use Verifactu\VerifactuClasses\ApiVerifactu;
//use PrestaShop\PrestaShop\Adapter\Entity\Order;
//use PrestaShop\PrestaShop\Adapter\Entity\Validate;
//use PrestaShop\PrestaShop\Adapter\Entity\Configuration;

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
        // Obtenemos el tipo desde la llamada AJAX, con 'alta' como valor por defecto.
        $type = Tools::getValue('type', 'alta');

        if (!$id_order || !in_array($type, ['alta', 'abono'])) {
            die(json_encode(['error' => 'Parámetros no válidos.']));
        }

        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            die(json_encode(['error' => 'No se pudo cargar el pedido.']));
        }

        $id_shop = (int)$order->id_shop;
        
        $api_token = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
        $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);

        $av = new ApiVerifactu($api_token, $debug_mode, $id_shop);
        // Pasamos el tipo a la función de envío.
        $response = $av->sendAltaVerifactu($id_order, $type);

        header('Content-Type: application/json');
        die($response);
    }

    public function displayAjaxCheckDNI()
    {
        $id_order = (int)Tools::getValue('id_order');
        if (!$id_order) {
            die(json_encode(['error' => 'ID de pedido no válido.']));
        }

        // CAMBIO MULTITIENDA: Lógica para obtener el contexto de la tienda.
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            die(json_encode(['error' => 'No se pudo cargar el pedido.']));
        }
        
        $id_shop = (int)$order->id_shop;

        $api_token = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
        $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);

        $av = new ApiVerifactu($api_token, $debug_mode, $id_shop);
        $response = $av->checkDNI($id_order);

        header('Content-Type: application/json');
        die($response);
    }

    public function displayAjaxAnularVerifactu()
    {
        $id_order = (int)Tools::getValue('id_order');
        if (!$id_order) {
            die(json_encode(['error' => 'ID de pedido no válido.']));
        }
        
        // CAMBIO MULTITIENDA: Lógica para obtener el contexto de la tienda.
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            die(json_encode(['error' => 'No se pudo cargar el pedido.']));
        }
        
        $id_shop = (int)$order->id_shop;

        $api_token = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
        $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);

        $av = new ApiVerifactu($api_token, $debug_mode, $id_shop);
        $response = $av->sendAnulacionVerifactu($id_order);

        header('Content-Type: application/json');
        die($response);
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
            die(json_encode(['error' => 'Por favor, seleccione una tienda específica para realizar esta acción.']));
        }

        $api_token = Configuration::get('VERIFACTU_API_TOKEN', null, null, $id_shop);
        $debug_mode = (bool)Configuration::get('VERIFACTU_DEBUG_MODE', false, null, $id_shop);

        $av = new ApiVerifactu($api_token, $debug_mode, $id_shop);
        
        // NOTA: Para que esto funcione perfectamente, el método checkPendingInvoices()
        // en ApiVerifactu debería aceptar $id_shop para filtrar solo las facturas de esa tienda.
        // Ver punto clave adicional abajo.
        $response = $av->checkPendingInvoices(); 

        header('Content-Type: application/json');
        die($response);
    }

    /**
     * Maneja la acción 'checkStatus' enviada por AJAX.
     */
    public function displayAjaxCheckStatus()
    {

        // 2. **Lógica**: Obtenemos una instancia de nuestro módulo para poder llamar a sus métodos públicos.
        $module = Module::getInstanceByName('verifactu');
        if (!Validate::isLoadedObject($module)) {
            die(json_encode(['success' => false, 'message' => 'No se pudo cargar el módulo.']));
        }

        // 3. **Ejecución**: Llamamos a la función que creamos en el fichero principal.
        $result = $module->checkApiStatus();
//die(json_encode('hola'));
        // 4. **Respuesta**: Devolvemos el resultado en formato JSON y terminamos la ejecución.
        // ajaxDie es un atajo de PrestaShop que hace echo y die().
        die(json_encode($result));
    }


    protected function ajaxDie($value = null, $controller = null, $method = null)
    {
        header('Content-Type: application/json');
        parent::ajaxDie($value, $controller, $method);
    }
}