{*
* Fichero de plantilla para la vista de pedido en versiones "legacy" de PrestaShop (< 1.7.7)
* Utiliza la estructura de <div class="panel"> en lugar de <div class="card">
*}

{if $id_order_invoice != ""}

<script>
    var id_order = '{$id_order}';
</script>

{* --- INICIO DE LA MODIFICACIÓN DE ESTILOS --- *}
<div class="panel" id="formVerifactu">
    <div class="panel-heading" style="color: #585a69; background-color:{if $estado == 'pendiente'}#e4e3f7{elseif $verifactuEstadoRegistro == "Correcto"}#eaf7ee{elseif $verifactuEstadoRegistro == "AceptadoConErrores"}#fff3cd{else}#f7dcde{/if};">
        <i class="icon-receipt"></i> {l s='Verifactu' mod='verifactu'}
    </div>
{* --- FIN DE LA MODIFICACIÓN DE ESTILOS --- *}

    <div class="panel-body">
        <div class="form-group">
            <strong>{l s='Registro de facturación:' mod='verifactu'}</strong>
            <span id="estado-verifactu" style="font-weight:bold;margin-left:10px;">
                {if $anulacion == "1"}
                    {l s='Registro Anulado' mod='verifactu'}
                {else}
                    {if $estado == "pendiente"}
                        {l s='Enviado correctamente. En espera de respuesta de Veri*Factu' mod='verifactu'}
                    {else}
                        {if $verifactuEstadoRegistro == ""}
                            {l s='No enviado' mod='verifactu'}
                        {else}
                            {if $verifactuEstadoRegistro == "Correcto"}
                                {l s='Enviado correctamente a Veri*Factu' mod='verifactu'}
                            {else}
                                {$verifactuEstadoRegistro} - {$verifactuDescripcionErrorRegistro} ({$verifactuCodigoErrorRegistro})
                            {/if}
                        {/if}
                    {/if}
                {/if}
            </span>
        </div>

        {if $verifactuEstadoRegistro == "Correcto" || $verifactuEstadoRegistro == "AceptadoConErrores"}
        <div class="form-group">
            <strong>{l s='Tipo de factura:' mod='verifactu'}</strong> 
            <span id="tipo-factura-verifactu" style="font-weight:bold;margin-left:10px;">
                {if $TipoFactura == "F2"}
                    {l s='Factura Simplificada' mod='verifactu'}
                {else}
                    {l s='Factura Completa' mod='verifactu'}
                {/if}
            </span>
        </div>
        {/if}

        {if $imgQR != ''}
            <div class="form-group text-center">
                <a href="{$urlQR}" target="_blank"><img src="{$imgQR}" width="100"></a>
            </div>
        {/if}
        
        <div id="estado_envio_verifactu" style="display:none;" class="alert alert-success">
			<div class="alert-text">
                    
            </div>
		</div>
    </div>
    
    <div class="panel-footer">
        <button class="btn btn-default" id="send_verifactu" {if $estado == "pendiente" || $verifactuEstadoRegistro == "Correcto"}disabled="true"{/if}>
            <i class="icon-refresh"></i> {l s='Reenviar registro de facturación' mod='verifactu'}
        </button>

        {if $verifactuEstadoRegistro == "Incorrecto"}
        <button class="btn btn-default" id="check_dni" {if $estado == "pendiente"}disabled="true"{/if}>
            <i class="icon-user"></i> {l s='Comprobar DNI' mod='verifactu'}
        </button>
        {/if}

        <button class="btn btn-default" id="send_anulacion_verifactu" {if $estado == "pendiente" || $anulacion == "1" || $verifactuEstadoRegistro == "Incorrecto" || !$verifactuEstadoRegistro}disabled="true"{/if}>
            <i class="icon-trash"></i> {l s='Enviar registro Anulación' mod='verifactu'}
        </button>

        {if $show_status_check_button}
        <button class="btn btn-info" id="check_api_status">
            <i class="icon-signal"></i> {l s='Comprobar estado AEAT' mod='verifactu'}
        </button>
        {/if}
    </div>
</div>

{else}
<div class="panel" id="formVerifactu">
    <div class="panel-heading">
        <i class="icon-receipt"></i> {l s='Verifactu' mod='verifactu'}
    </div>
    <div class="panel-body">
        {l s='Este pedido no tiene factura todavía.' mod='verifactu'}
    </div>
</div>
{/if}