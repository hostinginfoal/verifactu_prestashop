{* Archivo: /views/templates/admin/list_content.tpl *}
{foreach $list as $tr}
	{* Fila Principal - La hacemos clicable con una clase y un data-attribute *}
	<tr class="verifactu-row-clickable" data-row-id="{$tr.id_reg_fact|escape:'html':'UTF-8'}" style="cursor: pointer;">
		{foreach $fields_display as $key => $params}
			<td>
                {* Lógica para mostrar los datos de cada celda, igual que el HelperList por defecto *}
				{if $key == 'actions'}
					{if method_exists($current_module, $params.callback)}
						{$current_module->$params.callback($tr.$identifier, $tr)|escape:'html':'UTF-8'}
					{/if}
				{elseif isset($params.callback)}
					{$current_module->$params.callback($tr.$key, $tr)|escape:'html':'UTF-8'}
                {elseif isset($tr.$key)}
                    {if isset($params.prefix)}{$params.prefix|escape:'html':'UTF-8'}{/if}
                    {$tr.$key|escape:'html':'UTF-8'}
                    {if isset($params.suffix)}{$params.suffix|escape:'html':'UTF-8'}{/if}
				{/if}
			</td>
		{/foreach}
	</tr>

	{* Fila de Detalles - Oculta por defecto *}
	<tr class="verifactu-details-row" id="details-{$tr.id_reg_fact|escape:'html':'UTF-8'}" style="display: none;">
		<td colspan="{count($fields_display)}">
			<div style="padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd;">
				<h4>{l s='Detalles del Registro de Facturación' mod='verifactu'} (ID: {$tr.id_reg_fact|escape:'html':'UTF-8'})</h4>
				<table class="table">
					<tbody>
						<tr><th>{l s='Hash' mod='verifactu'}</th><td>{$tr.hash|escape:'html':'UTF-8'}</td></tr>
						<tr><th>{l s='Hash Anterior' mod='verifactu'}</th><td>{$tr.AnteriorHash|escape:'html':'UTF-8'}</td></tr>
						<tr><th>{l s='Fecha de Emisión' mod='verifactu'}</th><td>{$tr.IssueDate|escape:'html':'UTF-8'}</td></tr>
						<tr><th>{l s='Fecha de Registro' mod='verifactu'}</th><td>{$tr.fechaHoraRegistro|escape:'html':'UTF-8'}</td></tr>
						<tr><th>{l s='SIF Nombre' mod='verifactu'}</th><td>{$tr.SIFNombreSIF|escape:'html':'UTF-8'}</td></tr>
						<tr><th>{l s='SIF Versión' mod='verifactu'}</th><td>{$tr.SIFVersion|escape:'html':'UTF-8'}</td></tr>
						{* Puedes añadir aquí todos los demás campos que quieras mostrar *}
					</tbody>
				</table>
			</div>
		</td>
	</tr>
{/foreach}