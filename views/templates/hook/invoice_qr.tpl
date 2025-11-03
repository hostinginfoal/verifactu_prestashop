{*
* Plantilla para el hook displayPDFInvoice del módulo VeriFactu.
* Muestra el código QR y la información de verificación.
*}
<div>
    <table style="width: 100%;" nobr="true">
        <tr>
            <td style="width: 100%; text-align:right;">
                
                {if isset($verifactu_qr_code_path) && $verifactu_qr_code_path}
                    <img src="{$verifactu_qr_code_path}" style="margin: 0px !important; padding: 0px !important;width: {$verifactu_qr_width|intval}px; height: {$verifactu_qr_width|intval}px;" />
                {/if}
                <p style="font-size: 6pt; color: #444; text-align: right; width: {$verifactu_qr_width|intval}px; ">
                    {if isset($verifactu_qr_text) && $verifactu_qr_text}
                        {$verifactu_qr_text|escape:'htmlall':'UTF-8'}
                    {/if}
                </p>
            </td>
            
        </tr>
    </table>
</div>