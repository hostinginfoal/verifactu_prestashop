{*
* InFoAL S.L.
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to hosting@infoal.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    InFoAL S.L. <hosting@infoal.com>
*  @copyright InFoAL S.L.
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of InFoAL S.L.
*}

{if $id_order_invoice != ""}

<script>
    var urladmin = '{$urladmin}';
    var id_order = '{$id_order}';
</script>

<!--<div class="col-md-4 left-column">-->
    <div class="card" id="formVerifactu">
        <div class="card-header" style="color:white; background-color:{if $verifactuEstadoRegistro == "Correcto"}#cbf2d4;{elseif $verifactuEstadoRegistro == "AceptadoConErrores"}#fab000{else}#fbc6c3;{/if}">
            <h3 class="card-header-title">
              Verifactu
            </h3>
        </div>
        <div class="card-body">
            <div class="input-group">
                
                Registro de facturación: 
                <span id="estado-verifactu" style="font-weight:bold;margin-left:20px;">
                {if $verifactuEstadoRegistro == ""}
                  No enviado
                {else}
                  {if $verifactuEstadoRegistro == "Correcto"}
                    {$verifactuEstadoRegistro}
                  {else}
                    {$verifactuEstadoRegistro} - {$verifactuDescripcionErrorRegistro} ({$verifactuCodigoErrorRegistro})
                  {/if}
                {/if}</span>

                
            </div>
            
            {if $imgQR != ''}
                <div class="" style="width:100%; text-align:center">
                    <a href="{$urlQR}" target="_new"><img src="{$imgQR}" width="100"></a>
                </div>
            {/if}
            <div class="input-group">
                <button class="btn btn-action ml-2" style="width:100%; margin-top:20px;" id="send_verifactu" {if $verifactuEstadoRegistro == "Correcto" || $verifactuEstadoRegistro == "AceptadoConErrores"}disabled="true"{/if}>
                  {l s='Enviar registro de Alta' mod='lupiverifactu'}
                </button>
                <button  style="display:none;" class="btn btn-action ml-2" style="width:100%; margin-top:20px;" id="check_dni">
                  {l s='Comprobar DNI' mod='lupiverifactu'}
                </button>
                {if $verifactuEstadoRegistro == "Correcto" || $verifactuEstadoRegistro == "AceptadoConErrores"}
                <button style="display:none;" class="btn btn-action ml-2" style="width:100%; margin-top:10px;" id="send_anulacion_verifactu">
                  {l s='Enviar registro Anulación' mod='lupiverifactu'}
                </button>
                
                {/if}
            </div>
            
            <div id="estado_envio_verifactu" style="display:none;" class="alert alert-success d-print-none">
                <div class="alert-text">
                    
                </div>
            </div>
        </div>
    </div>
<!--</div>-->

{else}
  <div class="card" id="formVerifactu">
        <div class="card-header">
            <h3 class="card-header-title">
              Verifactu
            </h3>
        </div>
        <div class="card-body">
            <div class="input-group">
                Este pedido no tiene factura todavía.
            </div>
            
        </div>
    </div>
{/if}