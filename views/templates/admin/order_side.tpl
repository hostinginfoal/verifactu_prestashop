{*
* Plantilla para la barra lateral de pedidos (PS >= 1.7.7)
*}

<script>
    {* Definimos id_order para los botones de la factura principal *}
    var id_order = '{$id_order}';
</script>


            

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
    <div class="card" id="formVerifactu">
        <div class="card-header" style="color:white; background-color:{if $verifactu_invoice.estado == 'pendiente'}#e4e3f7{elseif $verifactu_invoice.verifactuEstadoRegistro == "Correcto"}#eaf7ee{elseif $verifactu_invoice.verifactuEstadoRegistro == "AceptadoConErrores"}#fff3cd{else}#f7dcde{/if}; color: #333;">
            <h3 class="card-header-title">
                <i class="icon-receipt"></i> {l s='Veri*Factu' mod='verifactu'}
                <span class="text-muted">({$verifactu_invoice.formatted_number|escape:'htmlall':'UTF-8'})</span>
                 
            </h3>
        </div>
        <div class="card-body">
            Estado actual: <span class="badge estado_verifactu {if $verifactu_invoice.estado == 'pendiente'}badge-info{elseif $verifactu_invoice.verifactuEstadoRegistro == "Correcto"}badge-success{elseif $verifactu_invoice.verifactuEstadoRegistro == "AceptadoConErrores"}badge-warning{else}badge-danger{/if}">
                    {if $verifactu_invoice.anulacion == "1"}
                            {l s='Registro Anulado' mod='verifactu'}
                    {else}
                        {if $verifactu_invoice.estado == "pendiente"}
                            {l s='Enviado correctamente. En espera de respuesta de Veri*Factu' mod='verifactu'}
                        {elseif $verifactu_invoice.estado == "api_error"}
                            <span class="text-danger">{$verifactu_invoice.verifactuDescripcionErrorRegistro|escape:'htmlall':'UTF-8'}</span>
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
                     <span class="badge badge-dark">{l s='Anulado' mod='verifactu'}</span>
                {/if}

                {if $verifactu_invoice.verifactuEstadoRegistro == "Correcto" || $verifactu_invoice.verifactuEstadoRegistro == "AceptadoConErrores"}
                    {if $verifactu_invoice.TipoFactura == "F2"}
                        <span class="badge badge-light border tipo_factura">{l s='Factura Simplificada' mod='verifactu'}</span>
                    {else}
                        <span class="badge badge-light border tipo_factura">{l s='Factura Completa' mod='verifactu'}</span>
                    {/if}
                {/if}
                <br><br>
            {* --- TIMELINE --- *}
            {if isset($verifactu_invoice.timeline) && $verifactu_invoice.timeline|@count > 0}
                <h6 class="text-muted text-uppercase mb-3" style="font-size: 11px; letter-spacing: 0.5px;">
                    <i class="icon-time"></i> {l s='Historial de registros enviados' mod='verifactu'}
                </h6>
                
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


            {* --- QR --- *}
            {if $verifactu_invoice.imgQR != ''}
                <div class="text-center mb-3 p-2 border rounded bg-light">
                    <a href="{$verifactu_invoice.urlQR|escape:'htmlall':'UTF-8'}" target="_new">
                        <img src="{$verifactu_invoice.imgQR|escape:'htmlall':'UTF-8'}" style="max-width: 100px; height: auto;">
                    </a>
                    <div class="mt-1"><small class="text-muted">{l s='Clic para verificar en AEAT' mod='verifactu'}</small></div>
                </div>
            {/if}

            {* --- BOTONES DE ACCIÓN --- *}
            <div class="btn-group-vertical w-100">
                <button class="btn btn-outline-primary btn-sm mb-1" id="send_verifactu" {if $verifactu_invoice.estado == "pendiente" || $verifactu_invoice.verifactuEstadoRegistro == "Correcto" }disabled{/if}>
                    <i class="icon-refresh"></i> {l s='Reenviar registro' mod='verifactu'}
                </button>

                {if $verifactu_invoice.verifactuEstadoRegistro == "Incorrecto"}
                    <button class="btn btn-outline-secondary btn-sm mb-1" id="check_dni" {if $verifactu_invoice.estado == "pendiente" }disabled{/if}>
                        <i class="icon-user"></i> {l s='Comprobar DNI' mod='verifactu'}
                    </button>
                {/if}

                {if $show_anulacion_button}
                    <button class="btn btn-outline-danger btn-sm mb-1" id="send_anulacion_verifactu" {if $verifactu_invoice.estado == "pendiente" || $verifactu_invoice.anulacion == "1" || $verifactu_invoice.verifactuEstadoRegistro == "Incorrecto" || !$verifactu_invoice.verifactuEstadoRegistro}disabled{/if}>
                        <i class="icon-ban"></i> {l s='Anular en Veri*Factu' mod='verifactu'}
                    </button>
                {/if}

                {if $verifactu_invoice.estado == 'pendiente'}
                    <button class="btn btn-info btn-sm mb-1" id="check_api_status">
                        <i class="icon-signal"></i> {l s='Comprobar estado AEAT' mod='verifactu'}
                    </button>
                {/if}
            </div>
            
            <div id="estado_envio_verifactu" style="display:none;" class="alert alert-success mt-2 d-print-none">
                <div class="alert-text"></div>
            </div>
        </div>
    </div>

{else}
    {* --- ESTADO VACÍO --- *}
    <div class="card" id="formVerifactu">
        <div class="card-header">
            <h3 class="card-header-title">
                {l s='Veri*Factu' mod='verifactu'}
            </h3>
        </div>
        <div class="card-body text-center text-muted">
            <i class="icon-file-text-o" style="font-size: 2em;"></i><br>
            {l s='Este pedido no tiene factura todavía.' mod='verifactu'}
        </div>
    </div>
{/if}


{* --- BLOQUE PARA LOS ABONOS --- *}
{if $verifactu_slips}
    {foreach from=$verifactu_slips item=slip}
    <div class="card mt-3" id="formVerifactuSlip_{$slip.id_order_slip}">
        <div class="card-header" style="background-color: {if $slip.estado == 'pendiente'}#e4e3f7{elseif $slip.verifactuEstadoRegistro == 'Correcto'}#eaf7ee{elseif $slip.verifactuEstadoRegistro == 'AceptadoConErrores'}#fff3cd{else}#f7dcde{/if}; color: #333;">
            <h3 class="card-header-title">
                <i class="icon-reply"></i> {l s='Veri*factu Abono' mod='verifactu'}
                <span class="text-muted">({$slip.formatted_number|escape:'htmlall':'UTF-8'})</span>
                 
            </h3>
        </div>
        <div class="card-body">
            Estado: <span class="badge estado_verifactu {if $slip.estado == 'pendiente'}badge-info{elseif $slip.verifactuEstadoRegistro == "Correcto"}badge-success{elseif $slip.verifactuEstadoRegistro == "AceptadoConErrores"}badge-warning{else}badge-danger{/if}">
                {if $slip.anulacion == "1"}
                    {l s='Registro Anulado' mod='verifactu'}
                {else}
                    {if $slip.estado == "pendiente"}
                        {l s='Enviado correctamente. En espera de respuesta de Veri*Factu' mod='verifactu'}
                    {elseif $slip.estado == "api_error"}
                        <span class="text-danger">{$slip.verifactuDescripcionErrorRegistro|escape:'htmlall':'UTF-8'}</span>
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
                {if $slip.TipoFactura == "R5"}
                    <span class="badge badge-light border tipo_factura">{l s='Factura Simplificada' mod='verifactu'}</span>
                {else}
                     <span class="badge badge-light border tipo_factura">{l s='Factura Completa' mod='verifactu'}</span>
                {/if}
            {/if}
            <br><br>
            {* --- TIMELINE ABONO --- *}
            {if isset($slip.timeline) && $slip.timeline|@count > 0}
                <h6 class="text-muted text-uppercase mb-3" style="font-size: 11px; letter-spacing: 0.5px;">
                    <i class="icon-time"></i> {l s='Historial de registros enviados' mod='verifactu'}
                </h6>
                
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
                
            {/if}
            {* --- FIN TIMELINE --- *}


            {* --- QR --- *}
            {if $slip.imgQR != ''}
                <div class="text-center mb-3 p-2 border rounded bg-light">
                    <a href="{$slip.urlQR|escape:'htmlall':'UTF-8'}" target="_new">
                        <img src="{$slip.imgQR|escape:'htmlall':'UTF-8'}" style="max-width: 100px; height: auto;">
                    </a>
                    <div class="mt-1"><small class="text-muted">{l s='Clic para verificar en AEAT' mod='verifactu'}</small></div>
                </div>
            {/if}

             {* --- BOTONES DE ACCIÓN (Si los necesitas para abonos) --- *}
             {* Nota: Si necesitas acciones de reenvío para abonos, asegúrate de que tu JS maneja el data-type="abono" *}
            <div class="btn-group-vertical w-100">
                 <button class="btn btn-outline-primary btn-sm mb-1 button-resend-verifactu" data-id_order="{$id_order}" data-type="abono" data-id_slip="{$slip.id_order_slip}" {if $slip.estado == "pendiente" || $slip.verifactuEstadoRegistro == "Correcto" }disabled{/if}>
                    <i class="icon-refresh"></i> {l s='Reenviar abono' mod='verifactu'}
                </button>
            </div>

            <div id="estado_envio_verifactu_slip_{$slip.id_order_slip}" style="display:none;" class="alert alert-success mt-2 d-print-none">
                <div class="alert-text"></div>
            </div>

        </div>
    </div>
    {/foreach}
{/if}