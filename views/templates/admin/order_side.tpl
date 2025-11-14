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
        <div class="card-header" style="color:white; background-color:{if $verifactu_invoice.estado == 'pendiente'}#e4e3f7{elseif $verifactu_invoice.verifactuEstadoRegistro == "Correcto"}#eaf7ee{elseif $verifactu_invoice.verifactuEstadoRegistro == "AceptadoConErrores"}#fff3cd{else}#f7dcde{/if};">
            <h3 class="card-header-title">
                {l s='Verifactu' mod='verifactu'} (Factura {$verifactu_invoice.formatted_number|escape:'htmlall':'UTF-8'})
            </h3>
        </div>
        <div class="card-body">
            <div class="input-group">
                <div style="width:100%;">
                    {l s='Registro de facturación:' mod='verifactu'}
                    <span id="estado-verifactu" style="font-weight:bold;margin-left:20px;">
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
                                    {l s='Enviado correctamente a Veri*Factu' mod='verifactu'}
                                {else}
                                    {$verifactu_invoice.verifactuEstadoRegistro|escape:'htmlall':'UTF-8'} - {$verifactu_invoice.verifactuDescripcionErrorRegistro|escape:'htmlall':'UTF-8'} ({$verifactu_invoice.verifactuCodigoErrorRegistro|escape:'htmlall':'UTF-8'})
                                {/if}
                            {/if}
                        {/if}
                    </span>
                </div>

                {if $verifactu_invoice.verifactuEstadoRegistro == "Correcto" || $verifactu_invoice.verifactuEstadoRegistro == "AceptadoConErrores"}
                <div style="width:100%;">
                    {l s='Tipo de factura:' mod='verifactu'} <span id="estado-verifactu" style="font-weight:bold;margin-left:20px;">
                        {if $verifactu_invoice.TipoFactura == "F2"}
                            {l s='Factura Simplificada' mod='verifactu'}
                        {else}
                            {l s='Factura Completa' mod='verifactu'}
                        {/if}
                        </span>
                </div>
                {/if}
            </div>
            
            {if $verifactu_invoice.imgQR != ''}
                <div class="" style="width:100%; text-align:center">
                    <a href="{$verifactu_invoice.urlQR|escape:'htmlall':'UTF-8'}" target="_new"><img src="{$verifactu_invoice.imgQR|escape:'htmlall':'UTF-8'}" width="100"></a>
                </div>
            {/if}
            <div class="input-group">
                <button class="btn btn-action ml-2" style="width:100%; margin-top:20px;" id="send_verifactu" {if $verifactu_invoice.estado == "pendiente" || $verifactu_invoice.verifactuEstadoRegistro == "Correcto" }disabled="true"{/if}>
                    {l s='Reenviar registro de facturación' mod='verifactu'}
                </button>
                {if $verifactu_invoice.verifactuEstadoRegistro == "Incorrecto"}
                <button class="btn btn-action ml-2" style="width:100%; margin-top:20px;" id="check_dni" {if $verifactu_invoice.estado == "pendiente" }disabled="true"{/if}>
                    {l s='Comprobar DNI' mod='verifactu'}
                </button>
                {/if}
                {if $show_anulacion_button}
                <button class="btn btn-action ml-2" style="width:100%; margin-top:20px;" id="send_anulacion_verifactu" {if $verifactu_invoice.estado == "pendiente" || $verifactu_invoice.anulacion == "1" || $verifactu_invoice.verifactuEstadoRegistro == "Incorrecto" || !$verifactu_invoice.verifactuEstadoRegistro}disabled="true"{/if}>
                    {l s='Enviar registro Anulación' mod='verifactu'}
                </button>
                {/if}
                {if $verifactu_invoice.estado == 'pendiente'} {* El botón de comprobar estado solo aparece para la factura principal si está pendiente *}
                    <button class="btn btn-info ml-2" style="width:100%; margin-top:20px;" id="check_api_status">
                        <i class="icon-signal"></i> {l s='Comprobar estado AEAT' mod='verifactu'}
                    </button>
                {/if}
                {* (El formulario de Factura-e se omite por brevedad) *}
            </div>
            
            <div id="estado_envio_verifactu" style="display:none;" class="alert alert-success d-print-none">
                <div class="alert-text"></div>
            </div>
        </div>
    </div>

{else}
    {* Se muestra si NO hay factura principal *}
    <div class="card" id="formVerifactu">
        <div class="card-header">
            <h3 class="card-header-title">
                {l s='Verifactu' mod='verifactu'}
            </h3>
        </div>
        <div class="card-body">
            <div class="input-group">
                {l s='Este pedido no tiene factura todavía.' mod='verifactu'}
            </div>
        </div>
    </div>
{/if}


{* --- BLOQUE PARA LOS ABONOS (NUEVO) --- *}
{if $verifactu_slips}
    {foreach from=$verifactu_slips item=slip}
    <div class="card" id="formVerifactuSlip_{$slip.id_order_slip}">
        <div class="card-header" style="color:white; background-color:{if $slip.estado == 'pendiente'}#e4e3f7{elseif $slip.verifactuEstadoRegistro == "Correcto"}#eaf7ee{elseif $slip.verifactuEstadoRegistro == "AceptadoConErrores"}#fff3cd{else}#f7dcde{/if};">
            <h3 class="card-header-title">
                {l s='Verifactu Abono' mod='verifactu'} ({$slip.formatted_number|escape:'htmlall':'UTF-8'})
            </h3>
        </div>
        <div class="card-body">
            <div class="input-group">
                <div style="width:100%;">
                    {l s='Registro de abono:' mod='verifactu'}
                    <span id="estado-verifactu-slip-{$slip.id_order_slip}" style="font-weight:bold;margin-left:20px;">
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
                                    {l s='Enviado correctamente a Veri*Factu' mod='verifactu'}
                                {else}
                                    {$slip.verifactuEstadoRegistro|escape:'htmlall':'UTF-8'} - {$slip.verifactuDescripcionErrorRegistro|escape:'htmlall':'UTF-8'} ({$slip.verifactuCodigoErrorRegistro|escape:'htmlall':'UTF-8'})
                                {/if}
                            {/if}
                        {/if}
                    </span>
                </div>

                {if $slip.verifactuEstadoRegistro == "Correcto" || $slip.verifactuEstadoRegistro == "AceptadoConErrores"}
                <div style="width:100%;">
                    {l s='Tipo de factura:' mod='verifactu'} <span style="font-weight:bold;margin-left:20px;">
                        {if $slip.TipoFactura == "R5"}
                            {l s='Factura Simplificada' mod='verifactu'} (Rectificativa)
                        {else}
                            {l s='Factura Completa' mod='verifactu'} (Rectificativa)
                        {/if}
                        </span>
                </div>
                {/if}
            </div>
            
            {if $slip.imgQR != ''}
                <div class="" style="width:100%; text-align:center">
                    <a href="{$slip.urlQR|escape:'htmlall':'UTF-8'}" target="_new"><img src="{$slip.imgQR|escape:'htmlall':'UTF-8'}" width="100"></a>
                </div>
            {/if}

            {* Nota: Los botones de acción se omiten aquí para evitar conflictos de ID y JS. *}
            {* Si se necesitan acciones específicas para abonos, el JS debe ser adaptado. *}

        </div>
    </div>
    {/foreach}
{/if}