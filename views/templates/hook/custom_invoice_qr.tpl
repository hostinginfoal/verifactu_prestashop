{if isset($verifactu_qr_code_path) && $verifactu_qr_code_path}
{*
    Este .tpl se usa para el hook personalizado 'displayVerifactuQR'.
    Muestra SOLO el QR (con el tamaño configurado en el módulo).
    El texto no se incluye para dar flexibilidad en la plantilla.
*}  <div style="font-size: 6pt; width: {$verifactu_qr_width|intval}px; text-align:center; ">
        <img src="{$verifactu_qr_code_path|escape:'htmlall':'UTF-8'}" style="width: {$verifactu_qr_width|intval}px; height: {$verifactu_qr_width|intval}px;">
        <br>
        {if isset($verifactu_qr_text) && $verifactu_qr_text}
            {$verifactu_qr_text|escape:'htmlall':'UTF-8'}
        {/if}
    </div>
{/if}  
