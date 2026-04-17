{*
* Fichero de plantilla para la vista de pedido en versiones "legacy" de PrestaShop (< 1.7.7)
* Utiliza la estructura de <div class="panel"> en lugar de <div class="card">
*}

<script>
    var id_order = '{$id_order}';
</script>

{* --- ESTILOS CSS PARA EL TIMELINE (Legacy) --- *}
<style>
    .verifactu-timeline {
        position: relative;
        padding-left: 20px;
        margin-top: 15px;
        margin-bottom: 15px;
        border-left: 2px solid #e9ecef;
    }
    .verifactu-timeline-item {
        position: relative;
        margin-bottom: 15px;
        padding-left: 15px;
    }
    .verifactu-timeline-item:last-child {
        margin-bottom: 0;
    }
    .verifactu-timeline-marker {
        position: absolute;
        left: -27px;
        top: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #6c757d;
        box-shadow: 0 0 0 3px #fff;
    }
    .verifactu-timeline-content {
        padding-bottom: 5px;
    }
    .verifactu-timeline-title {
        font-weight: bold;
        font-size: 13px;
        color: #333;
    }
    .verifactu-timeline-date {
        display: block;
        color: #888;
        font-size: 11px;
        margin-top: 2px;
    }
    .verifactu-timeline-detail {
        display: block;
        color: #dc3545;
        font-size: 11px;
        margin-top: 4px;
        background: #fff5f5;
        padding: 4px;
        border-radius: 4px;
    }
    /* Estilos Badge para PS 1.6 */
    .badge-success   { background-color: #5cb85c !important; color: white !important; }
    .badge-danger    { background-color: #d9534f !important; color: white !important; }
    .badge-warning   { background-color: #f0ad4e !important; color: white !important; }
    .badge-info      { background-color: #8b87d1 !important; color: white !important; }
    .badge-light     { background-color: #f8f9fa !important; color: #212529 !important; border: 1px solid #ddd !important; }
    .badge-dark      { background-color: #343a40 !important; color: white !important; }
    /* api_error: naranja/ámbar — error transitorio, se reintentará automáticamente */
    .badge-api-error { background-color: #e67e22 !important; color: white !important; }
</style>


{if $verifactu_invoice}

    {if $verifactu_invoice.verifactuEstadoRegistro == 'Correcto'}
        <div class="alert alert-success d-print-none">
            <div class="alert-text">
                <strong>AVISO LEGAL OBLIGATORIO - INALTERABILIDAD DE FACTURA</strong><br>
                La normativa española prohíbe modificar una factura cuyo registro de facturación ha sido aceptado correctamente en Veri*Factu. Para corregirla debe emitirse una Factura por Abono (Reembolso).
            </div>
        </div>
    {/if}

    {* --- BLOQUE PARA LA FACTURA PRINCIPAL --- *}
    <div class="panel" id="formVerifactu">
        <div class="panel-heading" style="color: #333;">
            <i class="icon-receipt"></i> {l s='Veri*Factu' mod='verifactu'} 
            <span class="text-muted">({$verifactu_invoice.formatted_number|escape:'htmlall':'UTF-8'})</span>
        </div>

        <div class="panel-body">
            <div class="row">
                {* --- COLUMNA IZQUIERDA: ESTADO Y TIMELINE --- *}
                <div class="col-md-8">
                    <div class="form-group">
                        Estado actual: <span class="badge {if $verifactu_invoice.estado == 'pendiente'}badge-info{elseif $verifactu_invoice.verifactuEstadoRegistro == "Correcto"}badge-success{elseif $verifactu_invoice.verifactuEstadoRegistro == "AceptadoConErrores"}badge-warning{elseif $verifactu_invoice.estado == "api_error" || $verifactu_invoice.estado == "stalled"}badge-api-error{else}badge-danger{/if}" style="font-size: 1.1em; padding: 5px 10px;">
                            {if $verifactu_invoice.anulacion == "1"}
                                    {l s='Registro Anulado' mod='verifactu'}
                            {else}
                                {if $verifactu_invoice.estado == "pendiente"}
                                    {l s='Enviado — En espera de confirmación de la AEAT' mod='verifactu'}
                                {elseif $verifactu_invoice.estado == "api_error" || $verifactu_invoice.estado == "stalled"}
                                    {l s='Error de conexión temporal con la API — Reintento automático pendiente' mod='verifactu'}
                                {else}
                                    {if $verifactu_invoice.verifactuEstadoRegistro == ""}
                                        {l s='No enviado' mod='verifactu'}
                                    {elseif $verifactu_invoice.verifactuEstadoRegistro == "Correcto"}
                                        {l s='Correcto' mod='verifactu'}
                                    {else}
                                        {$verifactu_invoice.verifactuEstadoRegistro|escape:'htmlall':'UTF-8'}
                                    {/if}
                                {/if}
                            {/if}
                        </span>
                        
                        {if $verifactu_invoice.anulacion == "1"}
                             <span class="badge badge-dark">{l s='Registro Anulado' mod='verifactu'}</span>
                        {/if}

                        {if $verifactu_invoice.verifactuEstadoRegistro == "Correcto" || $verifactu_invoice.verifactuEstadoRegistro == "AceptadoConErrores"}
                             - Tipo factura: {if $verifactu_invoice.TipoFactura == "F2"}
                                <span class="badge badge-light border">{l s='Simplificada' mod='verifactu'}</span>
                            {else}
                                <span class="badge badge-light border">{l s='Completa' mod='verifactu'}</span>
                            {/if}
                        {/if}
                    </div>

                    {* --- COUNTDOWN AUTOMÁTICO (solo en pendiente y api_error) --- *}
                    {if $verifactu_invoice.estado == 'pendiente'}
                    <div class="alert alert-info" style="padding: 8px 12px; margin-top: 8px; margin-bottom: 0; font-size: 13px;">
                        <i class="icon-info-circle"></i>
                        {l s='El registro ha sido enviado correctamente a Veri*Factu y está pendiente de validación por la AEAT. La verificación del resultado se realizará de forma automática; puede continuar navegando con normalidad.' mod='verifactu'}
                        <div class="vf-countdown-wrap" style="margin-top:5px; opacity:0.85;">
                            <i class="icon-refresh"></i>
                            {l s='Próxima comprobación automática en' mod='verifactu'}
                            <strong id="vf-countdown-inv">{$vf_seconds_until_next_check}s</strong>
                        </div>
                    </div>
                    {elseif $verifactu_invoice.estado == 'api_error' || $verifactu_invoice.estado == 'stalled'}
                    <div class="alert alert-warning" style="padding: 8px 12px; margin-top: 8px; margin-bottom: 0; font-size: 13px;">
                        <i class="icon-exclamation-triangle"></i>
                        {l s='No se ha podido contactar con la API en este momento. El envío se reintentará automáticamente cuando el servicio esté disponible. No es necesaria ninguna acción por su parte.' mod='verifactu'}
                        {if $verifactu_invoice.retry_count}<span class="text-muted"> &middot; {$verifactu_invoice.retry_count} {l s='intentos' mod='verifactu'}</span>{/if}
                        <div class="vf-countdown-wrap" style="margin-top:5px; opacity:0.85;">
                            <i class="icon-clock-o"></i>
                            {l s='Próximo reintento automático en' mod='verifactu'}
                            <strong id="vf-countdown-inv">{$vf_seconds_until_next_check}s</strong>
                        </div>
                    </div>
                    {/if}

                    {* --- TIMELINE --- *}
                    {if isset($verifactu_invoice.timeline) && $verifactu_invoice.timeline|@count > 0}
                        <h4 class="text-muted text-uppercase" style="font-size: 11px; letter-spacing: 0.5px; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 15px; margin-top: 20px;">
                            <i class="icon-time"></i> {l s='Historial de registros enviados' mod='verifactu'}
                        </h4>
                        
                        <div class="verifactu-timeline">
                            {foreach from=$verifactu_invoice.timeline item=event}
                                <div class="verifactu-timeline-item">
                                    <div class="verifactu-timeline-marker" style="border-color: {$event.color}; background-color: {$event.color};"></div>
                                    <div class="verifactu-timeline-content">
                                        <div class="verifactu-timeline-title" style="color: {$event.color}">
                                            {if isset($event.icon)}<i class="{$event.icon}"></i>{/if} {$event.title}
                                        </div>
                                        {if isset($event.date) && $event.date}
                                            <span class="verifactu-timeline-date">
                                                {$event.date|date_format:"%d/%m/%Y %H:%M:%S"}
                                            </span>
                                        {/if}
                                        {if isset($event.detail) && $event.detail}
                                            <span class="verifactu-timeline-detail">
                                                {$event.detail}
                                            </span>
                                        {/if}
                                    </div>
                                </div>
                            {/foreach}
                        </div>
                    {/if}
                    {* --- FIN TIMELINE --- *}
                </div>

                {* --- COLUMNA DERECHA: QR --- *}
                <div class="col-md-4">
                    {if $verifactu_invoice.imgQR != ''}
                        <div class="form-group text-center" style="background: #f9f9f9; padding: 10px; border: 1px solid #eee;">
                            <a href="{$verifactu_invoice.urlQR|escape:'htmlall':'UTF-8'}" target="_blank"><img src="{$verifactu_invoice.imgQR|escape:'htmlall':'UTF-8'}" width="100"></a>
                            <div style="margin-top:5px; font-size: 0.9em; color: #777;">{l s='Clic para verificar en AEAT' mod='verifactu'}</div>
                        </div>
                    {/if}
                </div>
            </div>
            
            <div id="estado_envio_verifactu" style="display:none;" class="alert alert-success">
                <div class="alert-text"></div>
            </div>

            {if $verifactu_invoice.estado == 'pendiente' || $verifactu_invoice.estado == 'api_error' || $verifactu_invoice.estado == 'stalled'}
            <script>
            (function() {
                var remaining = {$vf_seconds_until_next_check|intval};
                var wraps = document.querySelectorAll('.vf-countdown-wrap');
                var els   = document.querySelectorAll('#vf-countdown-inv');
                if (!els.length) return;

                function setSpinner() {
                    wraps.forEach(function(w) {
                        w.innerHTML = '<i class="icon-refresh icon-spin"></i> {l s='Comprobando con Veri*Factu...' mod='verifactu'}';
                    });
                }

                function doCheckAndReload() {
                    setSpinner();
                    if (typeof verifactu_ajax_url === 'undefined' || typeof jQuery === 'undefined') {
                        setTimeout(function() { location.reload(); }, 2000);
                        return;
                    }
                    jQuery.ajax({
                        url: verifactu_ajax_url,
                        type: 'POST',
                        dataType: 'json',
                        data: { ajax: true, action: 'checkPendingStatus', token: verifactu_token },
                        complete: function() { location.reload(); }
                    });
                }

                if (remaining <= 0) { doCheckAndReload(); return; }

                var timer = setInterval(function() {
                    remaining--;
                    if (remaining <= 0) {
                        clearInterval(timer);
                        doCheckAndReload();
                    } else {
                        els.forEach(function(el) { el.textContent = remaining + 's'; });
                    }
                }, 1000);
            })();
            </script>
            {/if}
        </div>
        
        <div class="panel-footer">
            <button class="btn btn-default" id="send_verifactu" {if $verifactu_invoice.estado == "pendiente" || $verifactu_invoice.verifactuEstadoRegistro == "Correcto"}disabled="true"{/if}>
                <i class="icon-refresh"></i> {l s='Reenviar registro' mod='verifactu'}
            </button>

            {if $verifactu_invoice.verifactuEstadoRegistro == "Incorrecto"}
            <button class="btn btn-default" id="check_dni" {if $verifactu_invoice.estado == "pendiente"}disabled="true"{/if}>
                <i class="icon-user"></i> {l s='Comprobar DNI' mod='verifactu'}
            </button>
            {/if}

            {if $show_anulacion_button}
            <button class="btn btn-default" id="send_anulacion_verifactu" {if $verifactu_invoice.estado == "pendiente" || $verifactu_invoice.anulacion == "1" || $verifactu_invoice.verifactuEstadoRegistro == "Incorrecto" || !$verifactu_invoice.verifactuEstadoRegistro}disabled="true"{/if}>
                <i class="icon-trash"></i> {l s='Enviar Anulación' mod='verifactu'}
            </button>
            {/if}
            
            {if $verifactu_invoice.estado == 'pendiente'}
            <button class="btn btn-info" id="check_api_status">
                <i class="icon-signal"></i> {l s='Estado AEAT' mod='verifactu'}
            </button>
            {/if}
        </div>
    </div>

{else}
    {* --- ESTADO VACÍO --- *}
    <div class="panel" id="formVerifactu">
        <div class="panel-heading">
            <i class="icon-receipt"></i> {l s='Veri*Factu' mod='verifactu'}
        </div>
        <div class="panel-body text-center text-muted">
             <i class="icon-file-text-alt" style="font-size: 2em;"></i><br><br>
            {l s='Este pedido no tiene factura todavía.' mod='verifactu'}
        </div>
    </div>
{/if}


{* --- BLOQUE PARA LOS ABONOS --- *}
{if $verifactu_slips}
    {foreach from=$verifactu_slips item=slip}
    <div class="panel" id="formVerifactuSlip_{$slip.id_order_slip}" style="margin-top: 15px;">
        <div class="panel-heading" style="color: #333;">
            <i class="icon-reply"></i> {l s='Veri*factu Abono' mod='verifactu'} ({$slip.formatted_number|escape:'htmlall':'UTF-8'})
        </div>

        <div class="panel-body">
            <div class="row">
                {* --- COLUMNA IZQUIERDA: ESTADO Y TIMELINE --- *}
                <div class="col-md-8">
                    <div class="form-group">
                        Estado actual: <span class="badge {if $slip.estado == 'pendiente'}badge-info{elseif $slip.verifactuEstadoRegistro == "Correcto"}badge-success{elseif $slip.verifactuEstadoRegistro == "AceptadoConErrores"}badge-warning{elseif $slip.estado == "api_error" || $slip.estado == "stalled"}badge-api-error{else}badge-danger{/if}" style="font-size: 1.1em; padding: 5px 10px;">
                            {if $slip.anulacion == "1"}
                                {l s='Registro Anulado' mod='verifactu'}
                            {else}
                                {if $slip.estado == "pendiente"}
                                    {l s='Enviado correctamente. En espera de respuesta de Veri*Factu' mod='verifactu'}
                                {elseif $slip.estado == "api_error" || $slip.estado == "stalled"}
                                    {l s='Error de conexión con la API — Se reintentará automáticamente' mod='verifactu'}
                                {else}
                                    {if $slip.verifactuEstadoRegistro == ""}
                                        {l s='No enviado' mod='verifactu'}
                                    {elseif $slip.verifactuEstadoRegistro == "Correcto"}
                                        {l s='Correcto' mod='verifactu'}
                                    {else}
                                        {$slip.verifactuEstadoRegistro|escape:'htmlall':'UTF-8'}
                                    {/if}
                                {/if}
                            {/if}
                        </span>
                        
                        {if $slip.anulacion == "1"}
                             <span class="badge badge-dark">{l s='Anulado' mod='verifactu'}</span>
                        {/if}

                        {if $slip.verifactuEstadoRegistro == "Correcto" || $slip.verifactuEstadoRegistro == "AceptadoConErrores"}
                             - Tipo factura: {if $slip.TipoFactura == "R5"}
                                <span class="badge badge-light border">{l s='Simplificada (R5)' mod='verifactu'}</span>
                            {else}
                                <span class="badge badge-light border">{l s='Completa' mod='verifactu'}</span>
                            {/if}
                        {/if}
                    </div>
                    
                    {* --- TIMELINE ABONO --- *}
                    {if isset($slip.timeline) && $slip.timeline|@count > 0}
                        <h4 class="text-muted text-uppercase" style="font-size: 11px; letter-spacing: 0.5px; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 15px; margin-top: 20px;">
                            <i class="icon-time"></i> {l s='Historial de registros enviados' mod='verifactu'}
                        </h4>
                        
                        <div class="verifactu-timeline">
                            {foreach from=$slip.timeline item=event}
                                <div class="verifactu-timeline-item">
                                    <div class="verifactu-timeline-marker" style="border-color: {$event.color}; background-color: {$event.color};"></div>
                                    <div class="verifactu-timeline-content">
                                        <div class="verifactu-timeline-title" style="color: {$event.color}">
                                            {if isset($event.icon)}<i class="{$event.icon}"></i>{/if} {$event.title}
                                        </div>
                                        {if isset($event.date) && $event.date}
                                            <span class="verifactu-timeline-date">
                                                {$event.date|date_format:"%d/%m/%Y %H:%M:%S"}
                                            </span>
                                        {/if}
                                        {if isset($event.detail) && $event.detail}
                                            <span class="verifactu-timeline-detail">
                                                {$event.detail}
                                            </span>
                                        {/if}
                                    </div>
                                </div>
                            {/foreach}
                        </div>
                        <hr>
                    {/if}
                    {* --- FIN TIMELINE --- *}
                </div>

                {* --- COLUMNA DERECHA: QR --- *}
                <div class="col-md-4">
                    {if $slip.imgQR != ''}
                        <div class="form-group text-center" style="background: #f9f9f9; padding: 10px; border: 1px solid #eee;">
                            <a href="{$slip.urlQR|escape:'htmlall':'UTF-8'}" target="_blank"><img src="{$slip.imgQR|escape:'htmlall':'UTF-8'}" width="100"></a>
                            <div style="margin-top:5px; font-size: 0.9em; color: #777;">{l s='Clic para verificar en AEAT' mod='verifactu'}</div>
                        </div>
                    {/if}
                </div>
            </div>
            
            {* --- BOTONES DE ACCIÓN PARA ABONOS --- *}
            <div class="panel-footer" style="padding: 10px; background: #f5f5f5; text-align: right;">
                <button class="btn btn-default btn-sm button-resend-verifactu" data-id_order="{$id_order}" data-type="abono" data-id_slip="{$slip.id_order_slip}" {if $slip.estado == "pendiente" || $slip.verifactuEstadoRegistro == "Correcto"}disabled{/if}>
                    <i class="icon-refresh"></i> {l s='Reenviar abono' mod='verifactu'}
                </button>
            </div>

            <div id="estado_envio_verifactu_slip_{$slip.id_order_slip}" style="display:none;" class="alert alert-success">
                <div class="alert-text"></div>
            </div>

        </div>
    </div>
    {/foreach}
{/if}
