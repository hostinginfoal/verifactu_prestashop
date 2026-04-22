{* VeriFactu - Dashboard template *}
{* TODO-06: Panel de estadísticas Veri*Factu *}

<div class="panel">
    <h3><i class="icon icon-credit-card"></i> {l s='VeriFactu by InFoAL' mod='verifactu'}</h3>
    <p>
        <strong>{l s='Envía los registros de facturación de forma automática al sistema VeriFactu' mod='verifactu'}</strong><br />
    </p>
    <br />
    <p>
        {l s='Si no dispones de una clave de API, solicítala en ' mod='verifactu'}<a href="https://verifactu.infoal.com" target="_blank">https://verifactu.infoal.com</a>
    </p>
</div>

<div class="panel">
	<h3><i class="icon icon-tags"></i> {l s='Documentación' mod='verifactu'}</h3>
	<p>
		&raquo;
        {l s='Puedes ver la guía de usuario del módulo en el siguiente enlace' mod='verifactu'} : <a href="https://verifactu.infoal.com/prestashop/guia-de-usuario-modulo-verifactu-para-prestashop" target="_blank">https://verifactu.infoal.com/prestashop/guia-de-usuario-modulo-verifactu-para-prestashop</a>
	</p>
	<p>
		&raquo;
        {l s='Puedes descargar la última versión del módulo desde aquí: ' mod='verifactu'} : <a href="https://github.com/hostinginfoal/verifactu_prestashop/releases/latest/download/verifactu.zip">https://github.com/hostinginfoal/verifactu_prestashop/releases/latest/download/verifactu.zip</a>
	</p>
	<p>
		&raquo;
        {l s='Si necesitas soporte puedes ponerte en contacto con nosotros, abriendo un ticket de soporte en el siguiente enlace: ' mod='verifactu'} : <a href="https://hosting.infoal.com/submitticket.php?step=2&deptid=1" target="_blank">https://hosting.infoal.com/submitticket.php?step=2&deptid=1</a>
	</p>
</div>

<style>
.vf-dashboard { margin-top: 15px; }
.vf-stat-card { background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 20px 15px; text-align: center; margin-bottom: 20px; }
.vf-stat-card .vf-stat-number { font-size: 32px; font-weight: bold; color: #2c3e50; line-height: 1; margin-bottom: 5px; }
.vf-stat-card .vf-stat-label { font-size: 12px; color: #888; text-transform: uppercase; }
.vf-stat-card.vf-green .vf-stat-number { color: #27ae60; }
.vf-stat-card.vf-red .vf-stat-number { color: #e74c3c; }
.vf-stat-card.vf-orange .vf-stat-number { color: #e67e22; }
.vf-stat-card.vf-blue .vf-stat-number { color: #2980b9; }
.vf-semaforo { display: inline-block; width: 14px; height: 14px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }
.vf-semaforo.green { background: #27ae60; box-shadow: 0 0 6px #27ae60; }
.vf-semaforo.red { background: #e74c3c; box-shadow: 0 0 6px #e74c3c; }
.vf-semaforo.grey { background: #bbb; }
.vf-recent-errors table { margin-bottom: 0; }
.vf-recent-errors table td, .vf-recent-errors table th { font-size: 12px; }
</style>

<div class="vf-dashboard">

    {* --- FILA 1: Estado AEAT + Totales --- *}
    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="vf-stat-card">
                <div class="vf-stat-number">{$stats.total_enviadas|intval}</div>
                <div class="vf-stat-label">{l s='Facturas enviadas (total)' mod='verifactu'}</div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="vf-stat-card">
                <div class="vf-stat-number">{$stats.total_mes|intval}</div>
                <div class="vf-stat-label">{l s='Enviadas este mes' mod='verifactu'}</div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="vf-stat-card vf-blue">
                <div class="vf-stat-number">{$stats.total_importe|string_format:'%.2f'} €</div>
                <div class="vf-stat-label">{l s='Importe total facturado' mod='verifactu'}</div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="vf-stat-card" id="vf-aeat-status-card">
                <div class="vf-stat-number" style="font-size:20px;">
                    <span class="vf-semaforo grey" id="vf-aeat-dot"></span>
                    <span id="vf-aeat-text">{l s='Comprobando...' mod='verifactu'}</span>
                </div>
                <div class="vf-stat-label">{l s='Estado AEAT' mod='verifactu'}</div>
            </div>
        </div>
    </div>

    {* --- FILA 2: Contadores por estado --- *}
    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-2">
            <div class="vf-stat-card vf-green">
                <div class="vf-stat-number">{$stats.correctos|intval}</div>
                <div class="vf-stat-label">{l s='Correctas' mod='verifactu'}</div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-2">
            <div class="vf-stat-card vf-red">
                <div class="vf-stat-number">{$stats.incorrectos|intval}</div>
                <div class="vf-stat-label">{l s='Incorrectas' mod='verifactu'}</div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-2">
            <div class="vf-stat-card vf-orange">
                <div class="vf-stat-number">{$stats.pendientes|intval}</div>
                <div class="vf-stat-label">{l s='Pendientes' mod='verifactu'}</div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-2">
            <div class="vf-stat-card vf-orange">
                <div class="vf-stat-number">{$stats.api_errors|intval}</div>
                <div class="vf-stat-label">{l s='Errores API' mod='verifactu'}</div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-2">
            <div class="vf-stat-card vf-red">
                <div class="vf-stat-number">{$stats.failed|intval}</div>
                <div class="vf-stat-label">{l s='Fallidas (máx reintentos)' mod='verifactu'}</div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-2">
            <div class="vf-stat-card vf-blue">
                <div class="vf-stat-number">{$stats.aceptados_con_errores|intval}</div>
                <div class="vf-stat-label">{l s='Aceptadas con errores' mod='verifactu'}</div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 text-right" style="margin-top:-12px; margin-bottom:10px;">
            <small class="text-muted"><i class="icon-calendar"></i> {l s='Contadores de estado: últimos 12 meses' mod='verifactu'}</small>
        </div>
    </div>

    {* --- FILA 3: Últimos errores --- *}
    <div class="panel vf-recent-errors">
        <div class="panel-heading">
            <i class="icon-warning-sign"></i>
            {l s='Últimas facturas con error (últimos 30 días)' mod='verifactu'}
            <a href="{$current}&tab_module_verifactu=sales_invoices" class="btn btn-default btn-xs pull-right">
                {l s='Ver listado completo' mod='verifactu'}
            </a>
        </div>
        {if $recent_errors}
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{l s='ID Pedido' mod='verifactu'}</th>
                        <th>{l s='Nº Factura' mod='verifactu'}</th>
                        <th>{l s='Cliente' mod='verifactu'}</th>
                        <th>{l s='Estado' mod='verifactu'}</th>
                        <th>{l s='Error AEAT' mod='verifactu'}</th>
                        <th>{l s='Reintentos' mod='verifactu'}</th>
                        <th>{l s='Acción' mod='verifactu'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$recent_errors item=err}
                    <tr>
                        <td><a href="{$err.order_url|escape:'html':'UTF-8'}" target="_blank">#{$err.id_order|intval}</a></td>
                        <td>{$err.invoice_number|escape:'html':'UTF-8'}</td>
                        <td>{$err.customer|escape:'html':'UTF-8'}</td>
                        <td><span class="label label-danger">{$err.estado|escape:'html':'UTF-8'}</span></td>
                        <td style="max-width:280px; word-wrap:break-word;">{$err.verifactuDescripcionErrorRegistro|truncate:120:'...'|escape:'html':'UTF-8'}</td>
                        <td class="text-center">{$err.retry_count|intval}</td>
                        <td>
                            <button class="btn btn-xs btn-primary vf-resend-single"
                                data-id-order="{$err.id_order|intval}"
                                data-type="alta">
                                <i class="icon-refresh"></i> {l s='Reenviar' mod='verifactu'}
                            </button>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {else}
            <div class="alert alert-success" style="margin:15px;">
                <i class="icon-check"></i> {l s='¡Sin errores recientes! Todos los registros están correctos.' mod='verifactu'}
            </div>
        {/if}
    </div>

</div>

<script type="text/javascript">
// TODO-06: Comprobación asíncrona del estado AEAT al cargar el dashboard
(function() {
    var ajaxUrl = '{$ajax_url|escape:'javascript':'UTF-8'}';
    var token   = '{$ajax_token|escape:'javascript':'UTF-8'}';

    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: { action: 'CheckStatus', ajax: 1, token: token },
        success: function(res) {
            var dot  = document.getElementById('vf-aeat-dot');
            var text = document.getElementById('vf-aeat-text');
            if (res && res.success) {
                dot.className  = 'vf-semaforo green';
                text.innerHTML = '{l s='Operativo' mod='verifactu' js=1}';
            } else {
                dot.className  = 'vf-semaforo red';
                text.innerHTML = '{l s='Sin servicio' mod='verifactu' js=1}';
            }
        },
        error: function() {
            document.getElementById('vf-aeat-text').innerHTML = '{l s='No disponible' mod='verifactu' js=1}';
        }
    });

    // TODO-07: Reenvío individual desde el dashboard
    $(document).on('click', '.vf-resend-single', function() {
        var btn     = $(this);
        var id_order = btn.data('id-order');
        var type     = btn.data('type');
        btn.prop('disabled', true).find('i').addClass('icon-spin');
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: { action: 'EnviarVerifactu', ajax: 1, token: token, id_order: id_order, type: type },
            success: function(res) {
                if (res && res.response === 'OK') {
                    btn.closest('tr').addClass('success');
                    btn.html('<i class="icon-check"></i>');
                } else {
                    btn.prop('disabled', false).find('i').removeClass('icon-spin');
                    alert('{l s='Error al reenviar: ' mod='verifactu' js=1}' + (res.error || JSON.stringify(res)));
                }
            },
            error: function() {
                btn.prop('disabled', false).find('i').removeClass('icon-spin');
            }
        });
    });
}());
</script>
