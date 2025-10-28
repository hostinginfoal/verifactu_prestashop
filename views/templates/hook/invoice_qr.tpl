{*
* Plantilla para el hook displayPDFInvoice del módulo VeriFactu.
* Muestra el código QR y la información de verificación.
*}
<br><br>
<div>
    <table style="width: 100%;" nobr="true">
        <tr>
            <td style="width: 88%; text-align:right;">
                <br><br>
                <p style="font-size: 6pt; color: #444; text-align: right; ">
                    {l s='Factura verificable en la sede electrónica de la AEAT' mod='verifactu'}
                </p>
            </td>
            <td style="width: 12%; text-align: right;">
                
                {if isset($verifactu_qr_code_path) && $verifactu_qr_code_path}
                    <img src="{$verifactu_qr_code_path}" style="margin: 0px !important; padding: 0px !important;" />
                {/if}
                
            </td>
        </tr>
    </table>
</div>