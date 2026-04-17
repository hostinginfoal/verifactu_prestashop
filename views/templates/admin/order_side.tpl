{*
 * Plantilla para la barra lateral de pedidos (PS >= 1.7.7)
 * TODO-25: Widget mejorado — todos los datos siempre visibles
 *}

<script>
    var id_order = '{$id_order}';
</script>

{* TODO-17: Aviso NIF vacío *}
{if $nif_warning}
<div class="vf-nif-warning">
    <i class="icon-warning-sign"></i>
    <div>
        <strong>{l s='Sin NIF/DNI en la dirección de facturación' mod='verifactu'}</strong><br>
        <span>{l s='Veri*Factu enviará esta factura como Simplificada (sin identificación del destinatario). Añada el NIF del cliente antes de facturar si necesita factura completa.' mod='verifactu'}</span>
    </div>
</div>
{/if}

{* ================================================================
   BLOQUE FACTURA PRINCIPAL
   ================================================================ *}
{if $verifactu_invoice}

    {* Aviso legal inalterabilidad *}
    {if $verifactu_invoice.verifactuEstadoRegistro == 'Correcto'}
    <div class="alert alert-success d-print-none">
        <div class="alert-text">
            <strong>{l s='AVISO LEGAL OBLIGATORIO - INALTERABILIDAD DE FACTURA' mod='verifactu'}</strong><br>
            {l s='La normativa española prohíbe modificar una factura cuyo registro de facturación ha sido aceptado correctamente en Veri*Factu. Para corregirla debe emitirse una Factura por Abono (Reembolso).' mod='verifactu'}
        </div>
    </div>
    {/if}

    {* ─── Color de cabecera según estado ─── *}
    {if $verifactu_invoice.anulacion == "1"}
        {assign var="hbg" value="#6c757d"}{assign var="htxt" value="#fff"}
    {elseif $verifactu_invoice.estado == "pendiente"}
        {assign var="hbg" value="#e4e3f7"}{assign var="htxt" value="#333"}
    {elseif $verifactu_invoice.verifactuEstadoRegistro == "Correcto"}
        {assign var="hbg" value="#eaf7ee"}{assign var="htxt" value="#333"}
    {elseif $verifactu_invoice.verifactuEstadoRegistro == "AceptadoConErrores"}
        {assign var="hbg" value="#fff3cd"}{assign var="htxt" value="#333"}
    {elseif $verifactu_invoice.estado == "api_error" || $verifactu_invoice.estado == "stalled"}
        {assign var="hbg" value="#fff4e0"}{assign var="htxt" value="#7a4f00"}
    {else}
        {assign var="hbg" value="#f7dcde"}{assign var="htxt" value="#333"}
    {/if}

    <div class="card" id="formVerifactu">
        <div class="card-header" style="background-color:{$hbg}; color:{$htxt};">
            <h3 class="card-header-title">
                <i class="icon-receipt"></i> {l s='Veri*Factu' mod='verifactu'}
                <span style="font-size:13px; font-weight:normal; margin-left:6px; opacity:.8;">
                    {$verifactu_invoice.formatted_number|escape:'htmlall':'UTF-8'}
                </span>
            </h3>
        </div>
        <div class="card-body" style="padding: 12px 14px;">

            {* ─── INDICADOR DE ESTADO ─── *}
            {if $verifactu_invoice.anulacion == "1"}
                <div class="vf-status-block vf-status-anulado">
                    <i class="icon-ban vf-status-icon"></i>
                    <div>
                        <div class="vf-status-label">{l s='Anulado' mod='verifactu'}</div>
                        <div class="vf-status-sub">{l s='Registro anulado en Veri*Factu' mod='verifactu'}</div>
                    </div>
                </div>
            {elseif $verifactu_invoice.estado == "pendiente"}
                <div class="vf-status-block vf-status-pendiente">
                    <i class="icon-clock-o vf-status-icon"></i>
                    <div>
                        <div class="vf-status-label">{l s='Pendiente' mod='verifactu'}</div>
                        <div class="vf-status-sub">{l s='En espera de confirmación de Veri*Factu' mod='verifactu'}</div>
                    </div>
                </div>
            {elseif $verifactu_invoice.verifactuEstadoRegistro == "Correcto"}
                <div class="vf-status-block vf-status-correcto">
                    <i class="icon-check-circle vf-status-icon"></i>
                    <div>
                        <div class="vf-status-label">{l s='Aceptado por la AEAT' mod='verifactu'}</div>
                        <div class="vf-status-sub">
                            {if $verifactu_invoice.TipoFactura == "F2"}{l s='Factura Simplificada (F2)' mod='verifactu'}
                            {elseif $verifactu_invoice.TipoFactura == "F1"}{l s='Factura Completa (F1)' mod='verifactu'}
                            {else}{$verifactu_invoice.TipoFactura|escape:'htmlall':'UTF-8'}{/if}
                            {if $verifactu_invoice.apiMode} &middot; <em>{$verifactu_invoice.apiMode|escape:'htmlall':'UTF-8'}</em>{/if}
                        </div>
                    </div>
                </div>
            {elseif $verifactu_invoice.verifactuEstadoRegistro == "AceptadoConErrores"}
                <div class="vf-status-block vf-status-warning">
                    <i class="icon-exclamation-triangle vf-status-icon"></i>
                    <div>
                        <div class="vf-status-label">{l s='Aceptado con avisos' mod='verifactu'}</div>
                        <div class="vf-status-sub">{l s='La AEAT aceptó el registro con advertencias no bloqueantes' mod='verifactu'}</div>
                    </div>
                </div>
            {elseif $verifactu_invoice.estado == "failed"}
                <div class="vf-status-block vf-status-error">
                    <i class="icon-remove vf-status-icon"></i>
                    <div>
                        <div class="vf-status-label">{l s='Fallido' mod='verifactu'}</div>
                        <div class="vf-status-sub">{l s='Máximo de reintentos alcanzado' mod='verifactu'} ({$verifactu_invoice.retry_count} {l s='intentos' mod='verifactu'})</div>
                    </div>
                </div>
            {elseif $verifactu_invoice.estado == "api_error" || $verifactu_invoice.estado == "stalled"}
                <div class="vf-status-block vf-status-api-error">
                    <i class="icon-refresh vf-status-icon"></i>
                    <div>
                        <div class="vf-status-label">
                            {l s='Error de conexión con la API' mod='verifactu'}
                            {if $verifactu_invoice.verifactuCodigoErrorRegistro}
                            &nbsp;<span class="vf-code-badge">{$verifactu_invoice.verifactuCodigoErrorRegistro|escape:'htmlall':'UTF-8'}</span>
                            {/if}
                        </div>
                        <div class="vf-status-sub">{l s='Se reintentará el envío automáticamente' mod='verifactu'}{if $verifactu_invoice.retry_count} &middot; {$verifactu_invoice.retry_count} {l s='intentos' mod='verifactu'}{/if}</div>
                    </div>
                </div>
            {elseif $verifactu_invoice.verifactuEstadoRegistro == "Incorrecto"}
                <div class="vf-status-block vf-status-error">
                    <i class="icon-times-circle vf-status-icon"></i>
                    <div>
                        <div class="vf-status-label">
                            {l s='Rechazado por la AEAT' mod='verifactu'}
                            {if $verifactu_invoice.verifactuCodigoErrorRegistro}
                            &nbsp;<span class="vf-code-badge">{$verifactu_invoice.verifactuCodigoErrorRegistro|escape:'htmlall':'UTF-8'}</span>
                            {/if}
                        </div>
                        {if $verifactu_invoice.verifactuDescripcionErrorRegistro}
                        <div class="vf-status-sub">{$verifactu_invoice.verifactuDescripcionErrorRegistro|escape:'htmlall':'UTF-8'|truncate:120:'...'}</div>
                        {/if}
                    </div>
                </div>
            {else}
                <div class="vf-status-block vf-status-pendiente">
                    <i class="icon-minus-circle vf-status-icon"></i>
                    <div>
                        <div class="vf-status-label">{l s='No enviado todavía' mod='verifactu'}</div>
                        <div class="vf-status-sub">{l s='Pendiente de envío a Veri*Factu' mod='verifactu'}</div>
                    </div>
                </div>
            {/if}

            {* ─── DATOS DEL REGISTRO ─── *}
            <div class="vf-section-title"><i class="icon-file-text-o"></i> {l s='Datos del registro AEAT' mod='verifactu'}</div>
            <table class="vf-detail-table">
                {if $verifactu_invoice.date_add}
                <tr><td>{l s='Fecha factura' mod='verifactu'}</td><td>{$verifactu_invoice.date_add|date_format:'%d/%m/%Y'}</td></tr>
                {/if}
                {if $verifactu_invoice.InvoiceNumber}
                <tr><td>{l s='Nº Factura AEAT' mod='verifactu'}</td><td><strong>{$verifactu_invoice.InvoiceNumber|escape:'htmlall':'UTF-8'}</strong></td></tr>
                {/if}
                {if $verifactu_invoice.IssueDate}
                <tr><td>{l s='Fecha emisión' mod='verifactu'}</td><td>{$verifactu_invoice.IssueDate|escape:'htmlall':'UTF-8'}</td></tr>
                {/if}
                {if $verifactu_invoice.InvoiceTotal}
                <tr><td>{l s='Total factura' mod='verifactu'}</td><td><strong>{$verifactu_invoice.InvoiceTotal|string_format:'%.2f'} &euro;</strong></td></tr>
                {/if}
                {if $verifactu_invoice.TipoFactura}
                <tr><td>{l s='Tipo factura' mod='verifactu'}</td><td>{$verifactu_invoice.TipoFactura|escape:'htmlall':'UTF-8'}</td></tr>
                {/if}
                {if $verifactu_invoice.TipoOperacion}
                <tr><td>{l s='Tipo operación' mod='verifactu'}</td><td>{$verifactu_invoice.TipoOperacion|escape:'htmlall':'UTF-8'}</td></tr>
                {/if}
                {if $verifactu_invoice.BuyerName}
                <tr><td>{l s='Destinatario' mod='verifactu'}</td><td>{$verifactu_invoice.BuyerName|escape:'htmlall':'UTF-8'}</td></tr>
                {/if}
                {if $verifactu_invoice.BuyerTaxIdentificationNumber}
                <tr><td>{l s='NIF destinatario' mod='verifactu'}</td><td>{$verifactu_invoice.BuyerTaxIdentificationNumber|escape:'htmlall':'UTF-8'}</td></tr>
                {/if}
                {if $verifactu_invoice.apiMode}
                <tr><td>{l s='Modo API' mod='verifactu'}</td><td><span class="vf-badge-mode">{$verifactu_invoice.apiMode|escape:'htmlall':'UTF-8'}</span></td></tr>
                {/if}
                {if $verifactu_invoice.retry_count}
                <tr><td>{l s='Reintentos' mod='verifactu'}</td><td>{$verifactu_invoice.retry_count}</td></tr>
                {/if}
                {if $verifactu_invoice.verifactuCodigoErrorRegistro}
                <tr><td>{l s='Código error' mod='verifactu'}</td><td class="vf-error-cell"><strong>{$verifactu_invoice.verifactuCodigoErrorRegistro|escape:'htmlall':'UTF-8'}</strong></td></tr>
                {/if}
                {if $verifactu_invoice.verifactuDescripcionErrorRegistro}
                <tr><td>{l s='Error AEAT' mod='verifactu'}</td><td class="vf-error-cell">{$verifactu_invoice.verifactuDescripcionErrorRegistro|escape:'htmlall':'UTF-8'}</td></tr>
                {/if}
            </table>

            {* ─── TIMELINE ─── *}
            {if isset($verifactu_invoice.timeline) && $verifactu_invoice.timeline|@count > 0}
            <div class="vf-section-title"><i class="icon-time"></i> {l s='Historial de envíos' mod='verifactu'}</div>
            <div class="verifactu-timeline">
                {foreach from=$verifactu_invoice.timeline item=event}
                <div class="verifactu-timeline-item">
                    <div class="verifactu-timeline-marker" style="border-color:{$event.color};background-color:{$event.color};"></div>
                    <div class="verifactu-timeline-content">
                        <div class="verifactu-timeline-title" style="color:{$event.color}">
                            {if isset($event.icon)}<i class="{$event.icon}"></i>{/if} {$event.title}
                        </div>
                        {if isset($event.date) && $event.date}
                        <span class="verifactu-timeline-date">{$event.date|date_format:"%d/%m/%Y %H:%M"}</span>
                        {/if}
                        {if isset($event.detail) && $event.detail}
                        <span class="verifactu-timeline-detail">{$event.detail}</span>
                        {/if}
                    </div>
                </div>
                {/foreach}
            </div>
            {/if}

            {* ─── HASH ─── *}
            {if $verifactu_invoice.hash}
            <div class="vf-section-title"><i class="icon-key"></i> {l s='Huella del registro (hash)' mod='verifactu'}</div>
            <div class="vf-hash-block">{$verifactu_invoice.hash|escape:'htmlall':'UTF-8'}</div>
            {/if}

            {* ─── QR ─── *}
            {if $verifactu_invoice.imgQR != ''}
            <div class="vf-section-title"><i class="icon-qrcode"></i> {l s='Código QR de verificación' mod='verifactu'}</div>
            <div class="vf-qr-block">
                <a href="{$verifactu_invoice.urlQR|escape:'htmlall':'UTF-8'}" target="_blank" title="{l s='Verificar en sede AEAT' mod='verifactu'}">
                    <img src="{$verifactu_invoice.imgQR|escape:'htmlall':'UTF-8'}" alt="QR Veri*Factu" class="vf-qr-img">
                </a>
                <div class="vf-qr-caption"><i class="icon-external-link"></i> {l s='Clic para verificar en sede AEAT' mod='verifactu'}</div>
            </div>
            {/if}

            {* ─── BOTONES DE ACCIÓN ─── *}
            <div class="vf-section-title"><i class="icon-bolt"></i> {l s='Acciones' mod='verifactu'}</div>
            <div class="vf-actions">
                <button class="vf-btn vf-btn-primary" id="send_verifactu"
                    {if $verifactu_invoice.estado == "pendiente" || $verifactu_invoice.verifactuEstadoRegistro == "Correcto"}disabled{/if}
                    title="{l s='Reenviar el registro a Veri*Factu' mod='verifactu'}">
                    <i class="icon-refresh"></i> {l s='Reenviar a Veri*Factu' mod='verifactu'}
                </button>

                {if $verifactu_invoice.verifactuEstadoRegistro == "Incorrecto"}
                <button class="vf-btn vf-btn-secondary" id="check_dni"
                    {if $verifactu_invoice.estado == "pendiente"}disabled{/if}
                    title="{l s='Comprobar si el DNI del cliente es válido en la AEAT' mod='verifactu'}">
                    <i class="icon-user"></i> {l s='Comprobar DNI en AEAT' mod='verifactu'}
                </button>
                {/if}

                {if $show_anulacion_button}
                <button class="vf-btn vf-btn-danger" id="send_anulacion_verifactu"
                    {if $verifactu_invoice.estado == "pendiente" || $verifactu_invoice.anulacion == "1" || $verifactu_invoice.verifactuEstadoRegistro == "Incorrecto" || !$verifactu_invoice.verifactuEstadoRegistro}disabled{/if}
                    title="{l s='Enviar anulación de este registro a Veri*Factu' mod='verifactu'}">
                    <i class="icon-ban"></i> {l s='Anular registro en Veri*Factu' mod='verifactu'}
                </button>
                {/if}

                {if $verifactu_invoice.estado == 'pendiente'}
                <button class="vf-btn vf-btn-info" id="check_api_status"
                    title="{l s='Comprobar si los servidores de la AEAT están operativos' mod='verifactu'}">
                    <i class="icon-signal"></i> {l s='Estado de la AEAT' mod='verifactu'}
                </button>
                {/if}
            </div>

            <div id="estado_envio_verifactu" style="display:none;" class="alert mt-2 d-print-none">
                <div class="alert-text"></div>
            </div>

        </div>{* /card-body *}
    </div>{* /card *}

{else}
    <div class="card" id="formVerifactu">
        <div class="card-header">
            <h3 class="card-header-title"><i class="icon-receipt"></i> {l s='Veri*Factu' mod='verifactu'}</h3>
        </div>
        <div class="card-body text-center text-muted" style="padding: 24px 14px;">
            <i class="icon-file-text-o" style="font-size: 2em; display:block; margin-bottom: 8px;"></i>
            {l s='Este pedido aún no tiene factura.' mod='verifactu'}
        </div>
    </div>
{/if}


{* ================================================================
   BLOQUES DE ABONOS
   ================================================================ *}
{if $verifactu_slips}
    {foreach from=$verifactu_slips item=slip}

    {if $slip.anulacion == "1"}
        {assign var="sbg" value="#6c757d"}{assign var="stxt" value="#fff"}
    {elseif $slip.estado == "pendiente"}
        {assign var="sbg" value="#e4e3f7"}{assign var="stxt" value="#333"}
    {elseif $slip.verifactuEstadoRegistro == "Correcto"}
        {assign var="sbg" value="#eaf7ee"}{assign var="stxt" value="#333"}
    {elseif $slip.verifactuEstadoRegistro == "AceptadoConErrores"}
        {assign var="sbg" value="#fff3cd"}{assign var="stxt" value="#333"}
    {elseif $slip.estado == "api_error" || $slip.estado == "stalled"}
        {assign var="sbg" value="#fff4e0"}{assign var="stxt" value="#7a4f00"}
    {else}
        {assign var="sbg" value="#f7dcde"}{assign var="stxt" value="#333"}
    {/if}

    <div class="card mt-3" id="formVerifactuSlip_{$slip.id_order_slip}">
        <div class="card-header" style="background-color:{$sbg}; color:{$stxt};">
            <h3 class="card-header-title">
                <i class="icon-reply"></i> {l s='Veri*Factu — Abono' mod='verifactu'}
                <span style="font-size:13px; font-weight:normal; margin-left:6px; opacity:.8;">
                    {$slip.formatted_number|escape:'htmlall':'UTF-8'}
                </span>
            </h3>
        </div>
        <div class="card-body" style="padding: 12px 14px;">

            {* Estado del abono *}
            {if $slip.anulacion == "1"}
                <div class="vf-status-block vf-status-anulado">
                    <i class="icon-ban vf-status-icon"></i>
                    <div><div class="vf-status-label">{l s='Anulado' mod='verifactu'}</div></div>
                </div>
            {elseif $slip.estado == "pendiente"}
                <div class="vf-status-block vf-status-pendiente">
                    <i class="icon-clock-o vf-status-icon"></i>
                    <div>
                        <div class="vf-status-label">{l s='Pendiente' mod='verifactu'}</div>
                        <div class="vf-status-sub">{l s='En espera de confirmación' mod='verifactu'}</div>
                    </div>
                </div>
            {elseif $slip.verifactuEstadoRegistro == "Correcto"}
                <div class="vf-status-block vf-status-correcto">
                    <i class="icon-check-circle vf-status-icon"></i>
                    <div>
                        <div class="vf-status-label">{l s='Aceptado por la AEAT' mod='verifactu'}</div>
                        <div class="vf-status-sub">
                            {if $slip.TipoFactura == "R5"}{l s='Abono Simplificado (R5)' mod='verifactu'}
                            {elseif $slip.TipoFactura}{$slip.TipoFactura|escape:'htmlall':'UTF-8'}
                            {else}{l s='Abono' mod='verifactu'}{/if}
                            {if $slip.apiMode} &middot; <em>{$slip.apiMode|escape:'htmlall':'UTF-8'}</em>{/if}
                        </div>
                    </div>
                </div>
            {elseif $slip.verifactuEstadoRegistro == "AceptadoConErrores"}
                <div class="vf-status-block vf-status-warning">
                    <i class="icon-exclamation-triangle vf-status-icon"></i>
                    <div><div class="vf-status-label">{l s='Aceptado con avisos' mod='verifactu'}</div></div>
                </div>
            {elseif $slip.estado == "api_error" || $slip.estado == "stalled"}
                <div class="vf-status-block vf-status-api-error">
                    <i class="icon-refresh vf-status-icon"></i>
                    <div>
                        <div class="vf-status-label">
                            {l s='Error de conexión con la API' mod='verifactu'}
                            {if $slip.verifactuCodigoErrorRegistro}
                            &nbsp;<span class="vf-code-badge">{$slip.verifactuCodigoErrorRegistro|escape:'htmlall':'UTF-8'}</span>
                            {/if}
                        </div>
                        <div class="vf-status-sub">{l s='Se reintentará el envío automáticamente' mod='verifactu'}{if $slip.retry_count} &middot; {$slip.retry_count} {l s='intentos' mod='verifactu'}{/if}</div>
                    </div>
                </div>
            {else}
                <div class="vf-status-block vf-status-error">
                    <i class="icon-times-circle vf-status-icon"></i>
                    <div>
                        <div class="vf-status-label">
                            {l s='Rechazado por la AEAT' mod='verifactu'}
                            {if $slip.verifactuCodigoErrorRegistro}
                            &nbsp;<span class="vf-code-badge">{$slip.verifactuCodigoErrorRegistro|escape:'htmlall':'UTF-8'}</span>
                            {/if}
                        </div>
                        {if $slip.verifactuDescripcionErrorRegistro}
                        <div class="vf-status-sub">{$slip.verifactuDescripcionErrorRegistro|escape:'htmlall':'UTF-8'|truncate:120:'...'}</div>
                        {/if}
                    </div>
                </div>
            {/if}

            {* Datos del abono *}
            <div class="vf-section-title"><i class="icon-file-text-o"></i> {l s='Datos del registro AEAT' mod='verifactu'}</div>
            <table class="vf-detail-table">
                {if $slip.date_add}
                <tr><td>{l s='Fecha abono' mod='verifactu'}</td><td>{$slip.date_add|date_format:'%d/%m/%Y'}</td></tr>
                {/if}
                {if $slip.InvoiceNumber}
                <tr><td>{l s='Nº Abono AEAT' mod='verifactu'}</td><td><strong>{$slip.InvoiceNumber|escape:'htmlall':'UTF-8'}</strong></td></tr>
                {/if}
                {if $slip.IssueDate}
                <tr><td>{l s='Fecha emisión' mod='verifactu'}</td><td>{$slip.IssueDate|escape:'htmlall':'UTF-8'}</td></tr>
                {/if}
                {if $slip.InvoiceTotal}
                <tr><td>{l s='Total abono' mod='verifactu'}</td><td><strong>{$slip.InvoiceTotal|string_format:'%.2f'} &euro;</strong></td></tr>
                {/if}
                {if $slip.TipoFactura}
                <tr><td>{l s='Tipo' mod='verifactu'}</td><td>{$slip.TipoFactura|escape:'htmlall':'UTF-8'}</td></tr>
                {/if}
                {if $slip.BuyerName}
                <tr><td>{l s='Destinatario' mod='verifactu'}</td><td>{$slip.BuyerName|escape:'htmlall':'UTF-8'}</td></tr>
                {/if}
                {if $slip.BuyerTaxIdentificationNumber}
                <tr><td>{l s='NIF' mod='verifactu'}</td><td>{$slip.BuyerTaxIdentificationNumber|escape:'htmlall':'UTF-8'}</td></tr>
                {/if}
                {if $slip.apiMode}
                <tr><td>{l s='Modo API' mod='verifactu'}</td><td><span class="vf-badge-mode">{$slip.apiMode|escape:'htmlall':'UTF-8'}</span></td></tr>
                {/if}
                {if $slip.retry_count}
                <tr><td>{l s='Reintentos' mod='verifactu'}</td><td>{$slip.retry_count}</td></tr>
                {/if}
                {if $slip.verifactuCodigoErrorRegistro}
                <tr><td>{l s='Código error' mod='verifactu'}</td><td class="vf-error-cell"><strong>{$slip.verifactuCodigoErrorRegistro|escape:'htmlall':'UTF-8'}</strong></td></tr>
                {/if}
                {if $slip.verifactuDescripcionErrorRegistro}
                <tr><td>{l s='Error AEAT' mod='verifactu'}</td><td class="vf-error-cell">{$slip.verifactuDescripcionErrorRegistro|escape:'htmlall':'UTF-8'}</td></tr>
                {/if}
            </table>

            {* Timeline abono *}
            {if isset($slip.timeline) && $slip.timeline|@count > 0}
            <div class="vf-section-title"><i class="icon-time"></i> {l s='Historial de envíos' mod='verifactu'}</div>
            <div class="verifactu-timeline">
                {foreach from=$slip.timeline item=event}
                <div class="verifactu-timeline-item">
                    <div class="verifactu-timeline-marker" style="border-color:{$event.color};background-color:{$event.color};"></div>
                    <div class="verifactu-timeline-content">
                        <div class="verifactu-timeline-title" style="color:{$event.color}">
                            {if isset($event.icon)}<i class="{$event.icon}"></i>{/if} {$event.title}
                        </div>
                        {if isset($event.date) && $event.date}
                        <span class="verifactu-timeline-date">{$event.date|date_format:"%d/%m/%Y %H:%M"}</span>
                        {/if}
                        {if isset($event.detail) && $event.detail}
                        <span class="verifactu-timeline-detail">{$event.detail}</span>
                        {/if}
                    </div>
                </div>
                {/foreach}
            </div>
            {/if}

            {* Hash abono *}
            {if $slip.hash}
            <div class="vf-section-title"><i class="icon-key"></i> {l s='Huella del registro (hash)' mod='verifactu'}</div>
            <div class="vf-hash-block">{$slip.hash|escape:'htmlall':'UTF-8'}</div>
            {/if}

            {* QR abono *}
            {if $slip.imgQR != ''}
            <div class="vf-section-title"><i class="icon-qrcode"></i> {l s='Código QR de verificación' mod='verifactu'}</div>
            <div class="vf-qr-block">
                <a href="{$slip.urlQR|escape:'htmlall':'UTF-8'}" target="_blank">
                    <img src="{$slip.imgQR|escape:'htmlall':'UTF-8'}" alt="QR Abono" class="vf-qr-img">
                </a>
                <div class="vf-qr-caption"><i class="icon-external-link"></i> {l s='Verificar en AEAT' mod='verifactu'}</div>
            </div>
            {/if}

            {* Botones abono *}
            <div class="vf-section-title"><i class="icon-bolt"></i> {l s='Acciones' mod='verifactu'}</div>
            <div class="vf-actions">
                <button class="vf-btn vf-btn-primary button-resend-verifactu"
                    data-id_order="{$id_order}" data-type="abono" data-id_slip="{$slip.id_order_slip}"
                    {if $slip.estado == "pendiente" || $slip.verifactuEstadoRegistro == "Correcto"}disabled{/if}
                    title="{l s='Reenviar este abono a Veri*Factu' mod='verifactu'}">
                    <i class="icon-refresh"></i> {l s='Reenviar abono' mod='verifactu'}
                </button>
            </div>

            <div id="estado_envio_verifactu_slip_{$slip.id_order_slip}" style="display:none;" class="alert mt-2 d-print-none">
                <div class="alert-text"></div>
            </div>

        </div>
    </div>
    {/foreach}
{/if}