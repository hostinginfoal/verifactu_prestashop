{* VeriFactu - Pestaña de Ayuda / FAQ *}
{* TODO-18: Help and FAQ tab *}

<style>
.vf-faq-section { margin-bottom: 20px; }
.vf-faq-section h4 { font-size: 15px; font-weight: bold; color: #2c3e50; margin-bottom: 8px; border-bottom: 2px solid #e9ecef; padding-bottom: 6px; }
.vf-faq-section .vf-faq-item { margin-bottom: 14px; }
.vf-faq-section .vf-faq-q { font-weight: 600; font-size: 13px; cursor: pointer; color: #2980b9; }
.vf-faq-section .vf-faq-q:before { content: '▶ '; font-size: 10px; }
.vf-faq-section .vf-faq-q.open:before { content: '▼ '; }
.vf-faq-section .vf-faq-a { font-size: 13px; color: #555; padding: 8px 12px; background: #f8f9fa; border-left: 3px solid #2980b9; margin-top: 4px; display: none; }
.vf-link-card { border: 1px solid #ddd; border-radius: 4px; padding: 14px; margin-bottom: 12px; background: #fff; }
.vf-link-card h5 { font-size: 14px; margin: 0 0 6px; }
.vf-link-card p { font-size: 12px; color: #666; margin: 0 0 8px; }
</style>

<div class="panel">
    <div class="panel-heading">
        <i class="icon-question-circle"></i>
        {l s='Ayuda y Preguntas Frecuentes (FAQ)' mod='verifactu'}
        <span class="pull-right badge badge-primary">VeriFactu v{$module_version|escape:'html':'UTF-8'}</span>
    </div>
    <div class="panel-body">
        <div class="row">

            {* ---- COL IZQUIERDA: FAQ ---- *}
            <div class="col-xs-12 col-md-7">

                {* FAQ: Primeros pasos *}
                <div class="vf-faq-section">
                    <h4><i class="icon-play-circle"></i> {l s='Primeros pasos' mod='verifactu'}</h4>

                    <div class="vf-faq-item">
                        <div class="vf-faq-q">{l s='¿Qué es Veri*Factu y por qué lo necesito?' mod='verifactu'}</div>
                        <div class="vf-faq-a">{l s='Veri*Factu es el sistema de la AEAT (Agencia Tributaria española) para el registro de facturas. Desde el 1 de julio de 2025, ciertos contribuyentes están obligados a utilizar sistemas informáticos de facturación homologados que envíen datos a la AEAT. Este módulo automatiza ese proceso desde PrestaShop.' mod='verifactu'}</div>
                    </div>
                    <div class="vf-faq-item">
                        <div class="vf-faq-q">{l s='¿Cómo obtengo mi API Token?' mod='verifactu'}</div>
                        <div class="vf-faq-a">{l s='Regístrate en verifactu.infoal.com y accede a tu panel de cliente. Tu API Token estará disponible en la sección "API".' mod='verifactu'}</div>
                    </div>
                    <div class="vf-faq-item">
                        <div class="vf-faq-q">{l s='¿Qué NIF debo poner en la configuración?' mod='verifactu'}</div>
                        <div class="vf-faq-a">{l s='El NIF del EMISOR de las facturas, es decir, el NIF de tu empresa o el tuyo como autónomo. Es el mismo que aparece en la cabecera de tus facturas. Sin guiones ni caracteres especiales (ej: B12345678 o 12345678Z).' mod='verifactu'}</div>
                    </div>
                </div>

                {* FAQ: Errores y estados *}
                <div class="vf-faq-section">
                    <h4><i class="icon-exclamation-triangle"></i> {l s='Errores y estados' mod='verifactu'}</h4>

                    <div class="vf-faq-item">
                        <div class="vf-faq-q">{l s='Una factura aparece como "Incorrecto". ¿Qué hago?' mod='verifactu'}</div>
                        <div class="vf-faq-a">{l s='Ve al listado de Facturas y pulsa el icono "Ver" para ver el error detallado. Los errores frecuentes son: NIF inválido del cliente (usa el botón "Comprobar DNI"), NIF del emisor incorrecto en la configuración, o datos de la factura inválidos. Corrige el problema y pulsa "Reenviar".' mod='verifactu'}</div>
                    </div>
                    <div class="vf-faq-item">
                        <div class="vf-faq-q">{l s='¿Qué significa "AceptadoConErrores"?' mod='verifactu'}</div>
                        <div class="vf-faq-a">{l s='La AEAT ha aceptado el registro (válido legalmente) pero con avisos no bloqueantes. Habitualmente significa que algún campo opcional tiene un valor que AEAT considera mejorable. Revisa los avisos en el detalle del registro para decidir si necesitas actuar.' mod='verifactu'}</div>
                    </div>
                    <div class="vf-faq-item">
                        <div class="vf-faq-q">{l s='Una factura lleva horas en estado "pendiente". ¿Es normal?' mod='verifactu'}</div>
                        <div class="vf-faq-a">{l s='En condiciones normales, el estado "pendiente" se resuelve en pocos minutos. Si persiste, los servidores de la AEAT pueden estar saturados o caídos. Puedes comprobarlo con el botón "Comprobar Estado AEAT" en la pestaña Configuración. El módulo reintentará automáticamente cuando el servicio esté disponible.' mod='verifactu'}</div>
                    </div>
                    <div class="vf-faq-item">
                        <div class="vf-faq-q">{l s='El estado es "api_error" o "failed". ¿Qué hago?' mod='verifactu'}</div>
                        <div class="vf-faq-a">{l s='"api_error" significa que hubo un error de comunicación con nuestra API y se reintentará automáticamente (backoff exponencial). "failed" significa que se agotaron los 5 reintentos automáticos. En "failed", usa el botón "Reenviar" cuando la conexión esté restaurada. Si el problema persiste, usa "Enviar Diagnóstico a InFoAL".' mod='verifactu'}</div>
                    </div>
                </div>

                {* FAQ: Fiscal *}
                <div class="vf-faq-section">
                    <h4><i class="icon-institution"></i> {l s='Configuración fiscal' mod='verifactu'}</h4>

                    <div class="vf-faq-item">
                        <div class="vf-faq-q">{l s='¿Qué es OSS (Ventanilla Única) y cuándo lo activo?' mod='verifactu'}</div>
                        <div class="vf-faq-a">{l s='Activa OSS solo si vendes a consumidores finales (B2C) en otros países de la UE y estás dado de alta en el régimen OSS (Ventanilla Única). Con OSS activo, el módulo usa el tipo impositivo del país del cliente. Si no estás en OSS, las ventas B2C a la UE se tratan como exportaciones.' mod='verifactu'}</div>
                    </div>
                    <div class="vf-faq-item">
                        <div class="vf-faq-q">{l s='Opero desde Canarias / Ceuta / Melilla. ¿Qué configuro?' mod='verifactu'}</div>
                        <div class="vf-faq-a">{l s='Activa la opción "¿Su tienda opera desde Canarias, Ceuta o Melilla?" y configura los impuestos IGIC (para Canarias) o IPSI (para Ceuta/Melilla) en los selectores correspondientes. El módulo tratará automáticamente las ventas a la Península como exportaciones.' mod='verifactu'}</div>
                    </div>
                    <div class="vf-faq-item">
                        <div class="vf-faq-q">{l s='¿Cómo anulo una factura ya enviada como "Correcta"?' mod='verifactu'}</div>
                        <div class="vf-faq-a">{l s='Primero activa el botón de Anulación en la configuración del módulo. Luego ve al pedido y pulsa "Anular en Veri*Factu". Después de anular, si necesitas rectificar el importe, genera una Factura por Abono (Reembolso) desde el pedido en PrestaShop.' mod='verifactu'}</div>
                    </div>
                </div>

                {* FAQ: Listados del módulo *}
                <div class="vf-faq-section">
                    <h4><i class="icon-list"></i> {l s='Los listados del módulo' mod='verifactu'}</h4>

                    <div class="vf-faq-item">
                        <div class="vf-faq-q">{l s='¿Qué son las «Facturas de Venta»?' mod='verifactu'}</div>
                        <div class="vf-faq-a">{l s='Contiene todas las facturas ordinarias generadas desde PrestaShop (las que se emiten cuando el cliente realiza un pedido). Para cada factura puedes ver su estado en Veri*Factu (Correcto, Incorrecto, Pendiente…) y actuar: reenviar, comprobar DNI o anular el registro.' mod='verifactu'}</div>
                    </div>
                    <div class="vf-faq-item">
                        <div class="vf-faq-q">{l s='¿Qué son las «Facturas por Abono»?' mod='verifactu'}</div>
                        <div class="vf-faq-a">{l s='Son los reembolsos o notas de crédito generados en PrestaShop cuando se devuelve total o parcialmente un pedido. En Veri*Factu se registran como facturas rectificativas (tipo R4 o R5). Este listado permite hacer seguimiento de su estado de registro en la AEAT de forma independiente a las facturas originales.' mod='verifactu'}</div>
                    </div>
                    <div class="vf-faq-item">
                        <div class="vf-faq-q">{l s='¿Qué son los «Registros de Facturación»?' mod='verifactu'}</div>
                        <div class="vf-faq-a">
                            {l s='Cada vez que una factura o abono se envía a Veri*Factu, la AEAT devuelve una respuesta y se crea un Registro de Facturación con el resultado: «Correcto», «Incorrecto» o «AceptadoConErrores». Si una factura se reenvía (por ejemplo, tras corregir un error), se genera un nuevo registro.' mod='verifactu'}
                            <br><br>
                            <strong>{l s='Por tanto, una factura o factura por abono puede tener uno o varios registros de facturación' mod='verifactu'}</strong>
                            {l s='(uno por cada intento de envío). El listado «Registros de Facturación» muestra este histórico completo, con el identificador único asignado por la AEAT (id_reg_fact), la fecha de envío, el estado de la cola y el código QR de verificación.' mod='verifactu'}
                        </div>
                    </div>
                    <div class="vf-faq-item">
                        <div class="vf-faq-q">{l s='¿Cuándo debo usar cada listado?' mod='verifactu'}</div>
                        <div class="vf-faq-a">{l s='Para el día a día usa «Facturas de Venta» y «Facturas por Abono». Si necesitas depurar un problema concreto de comunicación (por ejemplo, verificar la fecha exacta de un envío o el estado en la cola de la API), acude a «Registros de Facturación».' mod='verifactu'}</div>
                    </div>
                </div>

            </div>{* /col-left *}

            {* ---- COL DERECHA: Links y soporte ---- *}
            <div class="col-xs-12 col-md-5">

                <h4><i class="icon-book"></i> {l s='Documentación' mod='verifactu'}</h4>

                <div class="vf-link-card">
                    <h5><i class="icon-file-text-o"></i> {l s='Guía de usuario del módulo' mod='verifactu'}</h5>
                    <p>{l s='Instalación, configuración y uso detallado.' mod='verifactu'}</p>
                    <a href="https://verifactu.infoal.com/prestashop/guia-de-usuario-modulo-verifactu-para-prestashop" target="_blank" class="btn btn-default btn-sm">
                        <i class="icon-external-link"></i> {l s='Ver guía' mod='verifactu'}
                    </a>
                </div>

                <div class="vf-link-card">
                    <h5><i class="icon-download"></i> {l s='Última versión del módulo' mod='verifactu'}</h5>
                    <p>{l s='Descarga directa de la release más reciente.' mod='verifactu'}</p>
                    <a href="https://github.com/hostinginfoal/verifactu_prestashop/releases/latest/download/verifactu.zip" target="_blank" class="btn btn-default btn-sm">
                        <i class="icon-github"></i> {l s='Descargar ZIP' mod='verifactu'}
                    </a>
                </div>

                <div class="vf-link-card">
                    <h5><i class="icon-legal"></i> {l s='Consultar registros en la AEAT' mod='verifactu'}</h5>
                    <p>{l s='Accede con tu certificado digital para consultar los registros de facturación enviados a la Sede Electrónica de la AEAT.' mod='verifactu'}</p>
                    <a href="https://sede.agenciatributaria.gob.es/Sede/iva/sistemas-informaticos-facturacion-verifactu/gestiones.html" target="_blank" class="btn btn-default btn-sm">
                        <i class="icon-external-link"></i> {l s='Gestiones AEAT (certificado digital)' mod='verifactu'}
                    </a>
                </div>

                <hr>
                <h4><i class="icon-life-ring"></i> {l s='Soporte técnico' mod='verifactu'}</h4>

                <div class="vf-link-card" style="border-color: #2980b9;">
                    <h5 style="color:#2980b9;"><i class="icon-ticket"></i> {l s='Abrir ticket de soporte' mod='verifactu'}</h5>
                    <p>{l s='Si tienes un problema que no puedes resolver con la FAQ, abre un ticket y nuestro equipo te ayudará.' mod='verifactu'}</p>
                    <a href="https://hosting.infoal.com/submitticket.php?step=2&deptid=1" target="_blank" class="btn btn-primary btn-sm">
                        <i class="icon-envelope"></i> {l s='Contactar soporte' mod='verifactu'}
                    </a>
                </div>

                {if isset($diagnose_url)}
                <div class="vf-link-card" style="border-color: #27ae60;">
                    <h5 style="color:#27ae60;"><i class="icon-stethoscope"></i> {l s='Diagnóstico de soporte' mod='verifactu'}</h5>
                    <p>
                        <strong>{l s='Herramienta de soporte técnico para diagnóstico y resolución de incidencias.' mod='verifactu'}</strong><br>
                        {l s='Recopila información técnica del módulo y la envía a InFoAL para que podamos analizar tu caso. También puedes descargar el ZIP y enviarlo manualmente si te lo indican.' mod='verifactu'}
                    </p>
                    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                        <a href="{$diagnose_url|escape:'html':'UTF-8'}" class="btn btn-success btn-sm">
                            <i class="icon-upload"></i> {l s='Enviar diagnóstico' mod='verifactu'}
                        </a>
                        {if isset($zip_url)}
                        <a href="{$zip_url|escape:'html':'UTF-8'}" class="btn btn-default btn-sm">
                            <i class="icon-download"></i> {l s='Descargar ZIP de diagnóstico' mod='verifactu'}
                        </a>
                        {/if}
                    </div>
                </div>
                {/if}

                {if isset($tools_action_url)}
                <div class="vf-link-card" style="border-color: #8e44ad;">
                    <h5 style="color:#8e44ad;"><i class="icon-wrench"></i> {l s='Herramientas de Mantenimiento' mod='verifactu'}</h5>
                    <p>
                        {l s='Verifica la integridad de la base de datos y añade columnas faltantes sin borrar datos. Comprueba si los servidores de Veri*Factu de la AEAT están operativos.' mod='verifactu'}
                    </p>
                    <form method="post" action="{$tools_action_url|escape:'html':'UTF-8'}" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                        <input type="hidden" name="token" value="{$tools_token|escape:'html':'UTF-8'}">
                        <button type="submit" name="submitCheckDatabase" class="btn btn-default btn-sm">
                            <i class="icon-cogs"></i> {l s='Verificar y Reparar BD' mod='verifactu'}
                        </button>
                        <button type="submit" name="submitCheckApiStatus" class="btn btn-default btn-sm">
                            <i class="icon-signal"></i> {l s='Comprobar Estado AEAT' mod='verifactu'}
                        </button>
                    </form>
                </div>
                {/if}

            </div>{* /col-right *}


        </div>{* /row *}
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Toggle FAQ answers
    $(document).on('click', '.vf-faq-q', function() {
        $(this).toggleClass('open');
        $(this).next('.vf-faq-a').slideToggle(200);
    });
});
</script>
