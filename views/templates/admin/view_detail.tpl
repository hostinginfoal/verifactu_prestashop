{* VeriFactu - Vista Detalle mejorada del Registro de Facturación *}
{* TODO-10: Enhanced detail view *}

<style>
.vf-detail-panel { margin-bottom: 15px; }
.vf-detail-panel .panel-heading { font-size: 14px; font-weight: bold; }
.vf-detail-table th { width: 35%; background: #f8f8f8; font-weight: 600; font-size: 12px; padding: 7px 10px; }
.vf-detail-table td { font-size: 12px; padding: 7px 10px; word-break: break-all; }
.vf-detail-table td:empty::after, .vf-detail-table td.vf-empty::after { content: '—'; color: #bbb; }
.vf-hash-box { font-family: monospace; font-size: 11px; background: #f4f4f4; padding: 8px; border-radius: 3px; word-break: break-all; border: 1px solid #ddd; }
.vf-json-box { font-family: monospace; font-size: 11px; background: #2d2d2d; color: #f8f8f2; padding: 12px; border-radius: 4px; overflow-x: auto; max-height: 350px; overflow-y: auto; }
.vf-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
.vf-badge-success { background: #d4edda; color: #155724; }
.vf-badge-danger  { background: #f8d7da; color: #721c24; }
.vf-badge-warning { background: #fff3cd; color: #856404; }
.vf-badge-info    { background: #d1ecf1; color: #0c5460; }
</style>

<div class="row">

    {* ---- COLUMNA IZQUIERDA ---- *}
    <div class="col-xs-12 col-md-6">

        {* Datos de la Factura *}
        <div class="panel vf-detail-panel">
            <div class="panel-heading">
                <i class="icon-file-text-o"></i>
                {l s='Datos de la Factura' mod='verifactu'}
                <span class="pull-right">
                    {if $registro.tipo == 'alta'}
                        <span class="vf-badge vf-badge-success">{l s='Alta' mod='verifactu'}</span>
                    {else}
                        <span class="vf-badge vf-badge-warning">{l s='Abono' mod='verifactu'}</span>
                    {/if}
                </span>
            </div>
            <table class="table vf-detail-table">
                <tbody>
                {foreach from=$info_factura key=label item=val}
                    <tr>
                        <th>{$label|escape:'html':'UTF-8'}</th>
                        <td class="{if !$val}vf-empty{/if}">{$val|escape:'html':'UTF-8'}</td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

        {* Empresa Emisora *}
        <div class="panel vf-detail-panel">
            <div class="panel-heading"><i class="icon-building"></i> {l s='Empresa Emisora' mod='verifactu'}</div>
            <table class="table vf-detail-table">
                <tbody>
                {foreach from=$info_empresa key=label item=val}
                    <tr>
                        <th>{$label|escape:'html':'UTF-8'}</th>
                        <td class="{if !$val}vf-empty{/if}">{$val|escape:'html':'UTF-8'}</td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

        {* Destinatario *}
        <div class="panel vf-detail-panel">
            <div class="panel-heading"><i class="icon-user"></i> {l s='Destinatario' mod='verifactu'}</div>
            <table class="table vf-detail-table">
                <tbody>
                {foreach from=$info_destinatario key=label item=val}
                    <tr>
                        <th>{$label|escape:'html':'UTF-8'}</th>
                        <td class="{if !$val}vf-empty{/if}">{$val|escape:'html':'UTF-8'}</td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

        {* Factura Rectificativa (solo si existe) *}
        {if $info_rectificativa}
        <div class="panel vf-detail-panel" style="border-left: 4px solid #e67e22;">
            <div class="panel-heading"><i class="icon-exchange"></i> {l s='Factura Rectificativa' mod='verifactu'}</div>
            <table class="table vf-detail-table">
                <tbody>
                {foreach from=$info_rectificativa key=label item=val}
                    <tr>
                        <th>{$label|escape:'html':'UTF-8'}</th>
                        <td class="{if !$val}vf-empty{/if}">{$val|escape:'html':'UTF-8'}</td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
        {/if}

    </div>{* /col-left *}

    {* ---- COLUMNA DERECHA ---- *}
    <div class="col-xs-12 col-md-6">

        {* Estado AEAT *}
        <div class="panel vf-detail-panel">
            <div class="panel-heading">
                <i class="icon-check-circle"></i> {l s='Estado AEAT' mod='verifactu'}
                <span class="pull-right">
                    {if $registro.EstadoRegistro == 'Correcto'}
                        <span class="vf-badge vf-badge-success">{$registro.EstadoRegistro|escape:'html':'UTF-8'}</span>
                    {elseif $registro.EstadoRegistro == 'Incorrecto'}
                        <span class="vf-badge vf-badge-danger">{$registro.EstadoRegistro|escape:'html':'UTF-8'}</span>
                    {elseif $registro.EstadoRegistro == 'AceptadoConErrores'}
                        <span class="vf-badge vf-badge-warning">{$registro.EstadoRegistro|escape:'html':'UTF-8'}</span>
                    {else}
                        <span class="vf-badge vf-badge-info">{$registro.EstadoRegistro|escape:'html':'UTF-8'}</span>
                    {/if}
                </span>
            </div>
            <table class="table vf-detail-table">
                <tbody>
                {foreach from=$info_estado key=label item=val}
                    <tr>
                        <th>{$label|escape:'html':'UTF-8'}</th>
                        <td class="{if !$val}vf-empty{/if}">{$val|escape:'html':'UTF-8'}</td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

        {* URL QR + botón copiar *}
        {if $registro.urlQR}
        <div class="panel vf-detail-panel">
            <div class="panel-heading"><i class="icon-qrcode"></i> {l s='URL QR Verificación' mod='verifactu'}</div>
            <div style="padding:12px;">
                <div class="input-group">
                    <input type="text" id="vf-qr-url-input" class="form-control" value="{$registro.urlQR|escape:'html':'UTF-8'}" readonly style="font-size:11px;">
                    <span class="input-group-btn">
                        <button class="btn btn-default" id="vf-copy-qr-btn" title="{l s='Copiar URL QR al portapapeles' mod='verifactu'}">
                            <i class="icon-copy"></i> {l s='Copiar' mod='verifactu'}
                        </button>
                        <a href="{$registro.urlQR|escape:'html':'UTF-8'}" target="_blank" class="btn btn-info">
                            <i class="icon-external-link"></i> {l s='Abrir' mod='verifactu'}
                        </a>
                    </span>
                </div>
            </div>
        </div>
        {/if}

        {* Sistema Informático de Facturación (SIF) *}
        <div class="panel vf-detail-panel">
            <div class="panel-heading"><i class="icon-desktop"></i> {l s='Sistema Informático de Facturación (SIF)' mod='verifactu'}</div>
            <table class="table vf-detail-table">
                <tbody>
                {foreach from=$info_sif key=label item=val}
                    <tr>
                        <th>{$label|escape:'html':'UTF-8'}</th>
                        <td class="{if !$val}vf-empty{/if}">{$val|escape:'html':'UTF-8'}</td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

        {* Hash y cadena de encadenamiento *}
        <div class="panel vf-detail-panel">
            <div class="panel-heading"><i class="icon-lock"></i> {l s='Hash y Cadena de Encadenamiento' mod='verifactu'}</div>
            <div style="padding:10px 12px;">
                <p style="margin-bottom:5px; font-weight:600; font-size:12px;">{l s='Hash actual:' mod='verifactu'}</p>
                <div class="vf-hash-box" style="margin-bottom:10px;">{$registro.hash|escape:'html':'UTF-8'|default:'—'}</div>

                <p style="margin-bottom:5px; font-weight:600; font-size:12px;">{l s='Hash anterior:' mod='verifactu'}</p>
                <div class="vf-hash-box" style="margin-bottom:10px;">{$registro.AnteriorHash|escape:'html':'UTF-8'|default:'—'}</div>

                <p style="margin-bottom:5px; font-weight:600; font-size:12px;">{l s='Cadena firmada:' mod='verifactu'}</p>
                <div class="vf-hash-box">{$registro.cadena|escape:'html':'UTF-8'|default:'—'}</div>
            </div>
        </div>

        {* JSON completo (solo en modo debug) *}
        {if $debug_mode}
        <div class="panel vf-detail-panel">
            <div class="panel-heading">
                <i class="icon-code"></i> {l s='Datos completos del registro (Modo Debug)' mod='verifactu'}
                <span class="label label-warning pull-right">{l s='Solo visible en modo debug' mod='verifactu'}</span>
            </div>
            <div style="padding:10px 12px;">
                <pre class="vf-json-box">{$registro|@json_encode:JSON_PRETTY_PRINT|escape:'html':'UTF-8'}</pre>
            </div>
        </div>
        {/if}

    </div>{* /col-right *}

</div>{* /row *}

{* Botón Volver *}
<div class="panel">
    <div class="panel-footer">
        <a href="{$link->getAdminLink('AdminModules')|escape:'html':'UTF-8'}&configure=verifactu&tab_module_verifactu=reg_facts" class="btn btn-default">
            <i class="process-icon-back"></i> {l s='Volver al listado' mod='verifactu'}
        </a>
    </div>
</div>

{* TODO-10: Script para copiar URL QR *}
<script type="text/javascript">
$(document).ready(function() {
    $('#vf-copy-qr-btn').on('click', function() {
        var $input = $('#vf-qr-url-input');
        $input[0].select();
        try {
            document.execCommand('copy');
            $(this).html('<i class="icon-check"></i> {l s='¡Copiado!' mod='verifactu' js=1}');
            setTimeout(function() {
                $('#vf-copy-qr-btn').html('<i class="icon-copy"></i> {l s='Copiar' mod='verifactu' js=1}');
            }, 2000);
        } catch(e) {
            alert('{l s='No se pudo copiar automáticamente. Copia el texto manualmente.' mod='verifactu' js=1}');
        }
    });
});
</script>