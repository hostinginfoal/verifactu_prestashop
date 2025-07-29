{*
* Plantilla para el hook displayPDFInvoice del módulo VeriFactu.
* Muestra el código QR y la información de verificación.
*}
<br><br>
<div>
    <table style="width: 100%;" nobr="true">
        <tr>
            <td style="width: 88%; text-align:right;">
                <p style="font-size: 8pt; color: #444;">
                    Factura registrada en el sistema Veri*Factu:
                </p>
            </td>
            <td style="width: 12%; text-align: right;">
                
                {if isset($verifactu_qr_code_path) && $verifactu_qr_code_path}
                    <img src="{$verifactu_qr_code_path}" />
                {/if}
                
            </td>
        </tr>
    </table>
</div>