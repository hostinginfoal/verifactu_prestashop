{* Archivo: /views/templates/admin/view_detail.tpl *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-search"></i> {l s='Detalle del Registro de Facturación' mod='verifactu'} (ID: {$registro.id_reg_fact|escape:'html':'UTF-8'})
    </div>
    <div class="table-responsive">
        <table class="table">
            <tbody>
                {foreach from=$registro key=key item=value}
                    <tr>
                        <th style="width: 25%;">{$key|escape:'html':'UTF-8'}</th>
                        <td>
                            {* Hacemos el hash y la cadena más legibles *}
                            {if $key == 'hash' || $key == 'AnteriorHash' || $key == 'cadena'}
                                <div style="word-wrap: break-word; max-width: 800px;">{$value|escape:'html':'UTF-8'}</div>
                            {else}
                                {$value|escape:'html':'UTF-8'}
                            {/if}
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
    <div class="panel-footer">
        <a href="{$link->getAdminLink('AdminModules')|escape:'html':'UTF-8'}&configure=verifactu&tab_module_verifactu=reg_facts" class="btn btn-default">
            <i class="process-icon-back"></i> {l s='Volver al listado' mod='verifactu'}
        </a>
    </div>
</div>