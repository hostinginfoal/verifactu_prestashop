{*
* Fichero de plantilla para la vista de pedido en versiones "legacy" de PrestaShop (< 1.7.7)
* Utiliza la estructura de <div class="panel"> en lugar de <div class="card">
*}

<script>
    var id_order = '{$id_order}';
</script>

{if $verifactu_invoice}
    {* --- BLOQUE PARA LA FACTURA PRINCIPAL --- *}
    <div class="panel" id="formVerifactu">
        <div class="panel-heading" style="color: #585a69; background-color:{if $verifactu_invoice.estado == 'pendiente'}#e4e3f7{elseif $verifactu_invoice.verifactuEstadoRegistro == "Correcto"}#eaf7ee{elseif $verifactu_invoice.verifactuEstadoRegistro == "AceptadoConErrores"}#fff3cd{else}#f7dcde{/if};">
            <i class="icon-receipt"></i> {l s='Verifactu' mod='verifactu'} (Factura {$verifactu_invoice.formatted_number|escape:'htmlall':'UTF-8'})
        </div>

        <div class="panel-body">
            <div class="form-group">
                <strong>{l s='Registro de facturación:' mod='verifactu'}</strong>
                <span id="estado-verifactu" style="font-weight:bold;margin-left:10px;">
                    {if $verifactu_invoice.anulacion == "1"}
                        {l s='Registro Anulado' mod='verifactu'}
                    {else}
                        {if $verifactu_invoice.estado == "pendiente"}
                            {l s='Enviado correctamente. En espera de respuesta de Veri*Factu' mod='verifactu'}
                        {elseif $verifactu_invoice.estado == "api_error"}
                            <span style="color: #a94442;">{$verifactu_invoice.verifactuDescripcionErrorRegistro|escape:'htmlall':'UTF-8'}</span>
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
            <div class="form-group">
                <strong>{l s='Tipo de factura:' mod='verifactu'}</strong> 
                <span id="tipo-factura-verifactu" style="font-weight:bold;margin-left:10px;">
                    {if $verifactu_invoice.TipoFactura == "F2"}
                        {l s='Factura Simplificada' mod='verifactu'}
                    {else}
                        {l s='Factura Completa' mod='verifactu'}
                    {/if}
                </span>
            </div>
            {/if}

            {if $verifactu_invoice.imgQR != ''}
                <div class="form-group text-center">
                    <a href="{$verifactu_invoice.urlQR|escape:'htmlall':'UTF-8'}" target="_blank"><img src="{$verifactu_invoice.imgQR|escape:'htmlall':'UTF-8'}" width="100"></a>
                </div>
            {/if}
            
            <div id="estado_envio_verifactu" style="display:none;" class="alert alert-success">
                <div class="alert-text"></div>
            </div>
        </div>
        
        <div class="panel-footer">
            <button class="btn btn-default" id="send_verifactu" {if $verifactu_invoice.estado == "pendiente" || $verifactu_invoice.verifactuEstadoRegistro == "Correcto"}disabled="true"{/if}>
                <i class="icon-refresh"></i> {l s='Reenviar registro de facturación' mod='verifactu'}
            </button>

            {if $verifactu_invoice.verifactuEstadoRegistro == "Incorrecto"}
            <button class="btn btn-default" id="check_dni" {if $verifactu_invoice.estado == "pendiente"}disabled="true"{/if}>
                <i class="icon-user"></i> {l s='Comprobar DNI' mod='verifactu'}
            </button>
            {/if}

            <button class="btn btn-default" id="send_anulacion_verifactu" {if $verifactu_invoice.estado == "pendiente" || $verifactu_invoice.anulacion == "1" || $verifactu_invoice.verifactuEstadoRegistro == "Incorrecto" || !$verifactu_invoice.verifactuEstadoRegistro}disabled="true"{/if}>
                <i class="icon-trash"></i> {l s='Enviar registro Anulación' mod='verifactu'}
            </button>

            {if $verifactu_invoice.estado == 'pendiente'}
            <button class="btn btn-info" id="check_api_status">
                <i class="icon-signal"></i> {l s='Comprobar estado AEAT' mod='verifactu'}
            </button>
            {/if}
        </div>
    </div>

{else}
    {* Se muestra si NO hay factura principal *}
    <div class="panel" id="formVerifactu">
        <div class="panel-heading">
            <i class="icon-receipt"></i> {l s='Verifactu' mod='verifactu'}
        </div>
        <div class="panel-body">
            {l s='Este pedido no tiene factura todavía.' mod='verifactu'}
        </div>
    </div>
{/if}


{* --- BLOQUE PARA LOS ABONOS (NUEVO) --- *}
{if $verifactu_slips}
    {foreach from=$verifactu_slips item=slip}
    <div class="panel" id="formVerifactuSlip_{$slip.id_order_slip}">
        <div class="panel-heading" style="color: #585a69; background-color:{if $slip.estado == 'pendiente'}#e4e3f7{elseif $slip.verifactuEstadoRegistro == "Correcto"}#eaf7ee{elseif $slip.verifactuEstadoRegistro == "AceptadoConErrores"}#fff3cd{else}#f7dcde{/if};">
            <i class="icon-receipt"></i> {l s='Verifactu Abono' mod='verifactu'} ({$slip.formatted_number|escape:'htmlall':'UTF-8'})
        </div>

        <div class="panel-body">
            <div class="form-group">
                <strong>{l s='Registro de abono:' mod='verifactu'}</strong>
                <span id="estado-verifactu-slip-{$slip.id_order_slip}" style="font-weight:bold;margin-left:10px;">
                    {if $slip.anulacion == "1"}
                        {l s='Registro Anulado' mod='verifactu'}
                    {else}
                        {if $slip.estado == "pendiente"}
                            {l s='Enviado correctamente. En espera de respuesta de Veri*Factu' mod='verifactu'}
                        {elseif $slip.estado == "api_error"}
                            <span style="color: #a94442;">{$slip.verifactuDescripcionErrorRegistro|escape:'htmlall':'UTF-8'}</span>
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
            <div class="form-group">
                <strong>{l s='Tipo de factura:' mod='verifactu'}</strong> 
                <span style="font-weight:bold;margin-left:10px;">
                    {if $slip.TipoFactura == "R5"}
                        {l s='Factura Simplificada' mod='verifactu'} (Rectificativa)
                    {else}
                        {l s='Factura Completa' mod='verifactu'} (Rectificativa)
                    {/if}
                </span>
            </div>
            {/if}

            {if $slip.imgQR != ''}
                <div class="form-group text-center">
                    <a href="{$slip.urlQR|escape:'htmlall':'UTF-8'}" target="_blank"><img src="{$slip.imgQR|escape:'htmlall':'UTF-8'}" width="100"></a>
                </div>
            {/if}
        </div>
        {* El panel-footer con botones se omite para los abonos *}
    </div>
    {/foreach}
{/if}