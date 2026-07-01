{* VeriFactu - Dashboard template *}

<div class="panel">
    <h3>{l s='Infoal VeriFactu para PrestaShop' mod='verifactu'} - Dashboard</h3>
    <p>
        <strong>{l s='Envía los registros de facturación de forma automática al sistema VeriFactu de la AEAT' mod='verifactu'}</strong><br />
    </p>
    <br />
    <p>
        {l s='Si no dispones de una clave de API, solicítala en ' mod='verifactu'}<a href="https://verifactu.infoal.com/registro-de-usuarios" target="_blank">https://verifactu.infoal.com/registro-de-usuarios</a>
    </p>
</div>

<style>
/* ---- Base ---- */
.vf-dashboard { margin-top: 15px; }
.vf-dashboard .row { display: flex; flex-wrap: wrap; }
.vf-dashboard .row > [class*="col-"] { display: flex; }
.vf-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px 15px;
    text-align: center;
    margin-bottom: 20px;
    width: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    box-sizing: border-box;
}
.vf-stat-card .vf-stat-number { font-size: 32px; font-weight: bold; color: #2c3e50; line-height: 1; margin-bottom: 5px; }
.vf-stat-card .vf-stat-label  { font-size: 12px; color: #888; text-transform: uppercase; }
.vf-stat-card.vf-green  .vf-stat-number { color: #27ae60; }
.vf-stat-card.vf-red    .vf-stat-number { color: #e74c3c; }
.vf-stat-card.vf-orange .vf-stat-number { color: #e67e22; }
.vf-stat-card.vf-blue   .vf-stat-number { color: #2980b9; }
.vf-semaforo { display: inline-block; width: 14px; height: 14px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }
.vf-semaforo.green { background: #27ae60; box-shadow: 0 0 6px #27ae60; }
.vf-semaforo.red   { background: #e74c3c; box-shadow: 0 0 6px #e74c3c; }
.vf-semaforo.grey  { background: #bbb; }
.vf-logo-card { background: #fff; border: 1px solid #e0e0e0; }
.vf-logo-card img { display: block; margin: auto; }

/* ---- Estadísticas anuales ---- */
.vf-annual-section { margin-bottom: 20px; }
.vf-annual-section .panel-heading { display: flex; align-items: center; justify-content: space-between; }
.vf-annual-section .panel-body { padding: 20px; }
.vf-year-nav { display: flex; align-items: center; gap: 10px; }
.vf-year-nav a, .vf-year-nav span {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 50%;
    border: 1px solid #ccc; background: #fff;
    color: #555; font-size: 13px; text-decoration: none; transition: background .2s;
}
.vf-year-nav a:hover { background: #f0f0f0; }
.vf-year-nav a.disabled { opacity: .35; pointer-events: none; }
.vf-year-nav strong { font-size: 15px; font-weight: 700; color: #555; min-width: 44px; text-align: center; }

/* ---- Tarjetas del año ---- */
.vf-year-cards { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
.vf-ycard {
    flex: 1; min-width: 140px;
    border: 1px solid #e8e8e8; border-radius: 8px;
    padding: 16px 20px; background: #fff;
}
.vf-ycard .vf-ycard-label { font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: #aaa; margin-bottom: 6px; }
.vf-ycard .vf-ycard-value { font-size: 36px; font-weight: 800; color: #2c3e50; }
.vf-ycard.vf-ycard-green  { border-left: 4px solid #27ae60; }
.vf-ycard.vf-ycard-green  .vf-ycard-value { color: #27ae60; }
.vf-ycard.vf-ycard-red    { border-left: 4px solid #e74c3c; }
.vf-ycard.vf-ycard-red    .vf-ycard-value { color: #e74c3c; }
.vf-ycard.vf-ycard-orange { border-left: 4px solid #e67e22; }
.vf-ycard.vf-ycard-orange .vf-ycard-value { color: #e67e22; }

/* ---- Gráfico ---- */
.vf-chart-wrap {
    background: #fafafa; border: 1px solid #eee; border-radius: 6px;
    padding: 16px; margin-bottom: 24px;
}
.vf-chart-legend { display: flex; gap: 20px; margin-bottom: 10px; font-size: 13px; }
.vf-chart-legend span { display: flex; align-items: center; gap: 6px; }
.vf-chart-legend span::before { content: ''; display: inline-block; width: 12px; height: 12px; border-radius: 2px; }
.vf-legend-green::before  { background: #27ae60; }
.vf-legend-red::before    { background: #e74c3c; }
.vf-legend-orange::before { background: #e67e22; }

/* ---- Tabla mensual ---- */
.vf-monthly-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.vf-monthly-table th {
    text-align: left; padding: 10px 14px;
    border-bottom: 2px solid #eee; color: #666;
    font-size: 12px; font-weight: 700; text-transform: uppercase;
}
.vf-monthly-table th.text-right, .vf-monthly-table td.text-right { text-align: right; }
.vf-monthly-table td { padding: 10px 14px; border-bottom: 1px solid #f0f0f0; }
.vf-monthly-table tr.vf-row-empty td { color: #bbb; }
.vf-monthly-table tr:last-child td { border-bottom: none; }
.vf-monthly-table .col-correct  { color: #27ae60; font-weight: 600; }
.vf-monthly-table .col-wrong    { color: #e74c3c; font-weight: 600; }
.vf-monthly-table .col-accepted { color: #e67e22; font-weight: 600; }
.vf-monthly-table .col-total   { font-weight: 700; color: #2c3e50; }

/* ---- Layout ancho (≥1600px): gráfico + tabla en columnas ---- */
@media (min-width: 1600px) {
    .vf-chart-table-wrap { display: flex; gap: 24px; align-items: flex-start; }
    .vf-chart-table-wrap .vf-chart-wrap  { flex: 0 0 50%; max-width: 50%; margin-bottom: 0; }
    .vf-chart-table-wrap .vf-table-wrap  { flex: 0 0 50%; max-width: 50%; overflow-y: auto; max-height: 420px; }
}

/* ---- Errores ---- */
.vf-recent-errors table { margin-bottom: 0; }
.vf-recent-errors table td, .vf-recent-errors table th { font-size: 12px; }
</style>

<div class="vf-dashboard">

    {* --- FILA 1: Logo + Totales globales + Estado AEAT --- *}
    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="vf-stat-card vf-logo-card">
                <a href="https://verifactu.infoal.com/acceso-clientes" target="_blank" title="Acceso clientes VeriFactu">
                    <img src="{$module_dir}infoalverifactu.png" alt="Infoal VeriFactu" style="max-width:80%; max-height:45px; object-fit:contain;" />
                </a>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="vf-stat-card">
                <div class="vf-stat-number">{$stats.total_enviadas|intval}</div>
                <div class="vf-stat-label">{l s='Registros de facturación enviados (total)' mod='verifactu'}</div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="vf-stat-card">
                <div class="vf-stat-number">{$stats.total_mes|intval}</div>
                <div class="vf-stat-label">{l s='Enviados este mes' mod='verifactu'}</div>
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

    {* --- SECCIÓN ANUAL --- *}
    <div class="panel vf-annual-section">
        <div class="panel-heading">
            <span><i class="icon-bar-chart"></i> {l s='Estadísticas de Envíos' mod='verifactu'}</span>
            <div class="vf-year-nav">
                {assign var="prev_year" value=$selected_year-1}
                {assign var="next_year" value=$selected_year+1}
                <a href="{$base_url_year|escape:'html'}&vf_year={$prev_year}" {if !in_array($prev_year, $available_years)}class="disabled"{/if} title="{$prev_year}">&#8592;</a>
                <strong>{$selected_year}</strong>
                <a href="{$base_url_year|escape:'html'}&vf_year={$next_year}" {if !in_array($next_year, $available_years)}class="disabled"{/if} title="{$next_year}">&#8594;</a>
            </div>
        </div>
        <div class="panel-body">

        {* Tarjetas del año *}
        <div class="vf-year-cards">
            <div class="vf-ycard">
                <div class="vf-ycard-label">{l s='Total enviados' mod='verifactu'}</div>
                <div class="vf-ycard-value">{$year_total|number_format:0:',':'.'}</div>
            </div>
            <div class="vf-ycard vf-ycard-green">
                <div class="vf-ycard-label">{l s='Correctos' mod='verifactu'}</div>
                <div class="vf-ycard-value">{$year_correct|number_format:0:',':'.'}</div>
            </div>
            <div class="vf-ycard vf-ycard-red">
                <div class="vf-ycard-label">{l s='Incorrectos' mod='verifactu'}</div>
                <div class="vf-ycard-value">{$year_wrong|number_format:0:',':'.'}</div>
            </div>
            <div class="vf-ycard vf-ycard-orange">
                <div class="vf-ycard-label">{l s='Acept. con errores' mod='verifactu'}</div>
                <div class="vf-ycard-value">{$year_accepted|number_format:0:',':'.'}</div>
            </div>
        </div>

        {* Gráfico + Tabla: en pantallas anchas aparecen lado a lado *}
        <div class="vf-chart-table-wrap">

        {* Gráfico de barras *}
        <div class="vf-chart-wrap">
            <div class="vf-chart-legend">
                <span class="vf-legend-green">{l s='Correctos' mod='verifactu'}</span>
                <span class="vf-legend-red">{l s='Incorrectos' mod='verifactu'}</span>
                <span class="vf-legend-orange">{l s='Acept. con errores' mod='verifactu'}</span>
            </div>
            <canvas id="vf-monthly-chart" height="90"></canvas>
        </div>

        {* Tabla mensual *}
        <div class="vf-table-wrap">
        <table class="vf-monthly-table">
            <thead>
                <tr>
                    <th>{l s='Mes' mod='verifactu'}</th>
                    <th class="text-right" style="color:#27ae60;">{l s='Correctos' mod='verifactu'}</th>
                    <th class="text-right" style="color:#e74c3c;">{l s='Incorrectos' mod='verifactu'}</th>
                    <th class="text-right" style="color:#e67e22;">{l s='Acept. con errores' mod='verifactu'}</th>
                    <th class="text-right">{l s='Total' mod='verifactu'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$monthly_stats item=m}
                <tr{if !$m.has_data} class="vf-row-empty"{/if}>
                    <td>{$m.mes_name}</td>
                    <td class="text-right col-correct">{$m.correctos|number_format:0:',':'.'}</td>
                    <td class="text-right col-wrong">{$m.incorrectos|number_format:0:',':'.'}</td>
                    <td class="text-right col-accepted">{$m.aceptados_con_errores|number_format:0:',':'.'}</td>
                    <td class="text-right col-total">{$m.total|number_format:0:',':'.'}</td>
                </tr>
                {/foreach}
            </tbody>
        </table>
        </div>{* /vf-table-wrap *}

        </div>{* /vf-chart-table-wrap *}

        </div>{* /panel-body *}
    </div>{* /vf-annual-section panel *}

    {* --- ÚLTIMOS ERRORES --- *}
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

{* Chart.js CDN *}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script type="text/javascript">
(function() {
    // ---- Datos mensuales desde PHP ----
    var labels   = [{foreach from=$monthly_stats item=m}'{$m.mes_short}',{/foreach}];
    var correct  = [{foreach from=$monthly_stats item=m}{$m.correctos},{/foreach}];
    var wrong    = [{foreach from=$monthly_stats item=m}{$m.incorrectos},{/foreach}];
    var accepted = [{foreach from=$monthly_stats item=m}{$m.aceptados_con_errores},{/foreach}];

    // ---- Gráfico barras apiladas ----
    var ctx = document.getElementById('vf-monthly-chart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '{l s='Correctos' mod='verifactu' js=1}',
                        data: correct,
                        backgroundColor: '#27ae60',
                        borderRadius: 2
                    },
                    {
                        label: '{l s='Incorrectos' mod='verifactu' js=1}',
                        data: wrong,
                        backgroundColor: '#e74c3c',
                        borderRadius: 2
                    },
                    {
                        label: '{l s='Acept. con errores' mod='verifactu' js=1}',
                        data: accepted,
                        backgroundColor: '#e67e22',
                        borderRadius: 2
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, grid: { color: '#f0f0f0' } }
                }
            }
        });
    }

    // ---- Estado AEAT (async) ----
    var ajaxUrl = '{$ajax_url|escape:'javascript':'UTF-8'}';
    var token   = '{$ajax_token|escape:'javascript':'UTF-8'}';

    $.ajax({
        url: ajaxUrl, type: 'POST', dataType: 'json',
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

    // ---- Reenvío individual desde el dashboard ----
    $(document).on('click', '.vf-resend-single', function() {
        var btn      = $(this);
        var id_order = btn.data('id-order');
        var type     = btn.data('type');
        btn.prop('disabled', true).find('i').addClass('icon-spin');
        $.ajax({
            url: ajaxUrl, type: 'POST', dataType: 'json',
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
