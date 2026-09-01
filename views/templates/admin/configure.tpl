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



{if $update_available}
    <div class="alert alert-info" role="alert">
        <p class="alert-text">
            
            {l s='¡Hay una nueva versión disponible!' mod='verifactu'}
            <strong>{l s='Versión' mod='verifactu'} {$latest_version|escape:'html':'UTF-8'}</strong>.
            <a href="{$github_releases_url|escape:'html':'UTF-8'}" target="_blank" class="btn btn-info" style="margin-left: 15px;">
                <i class="icon-download"></i> {l s='Descargar ahora' mod='verifactu'}
            </a>
        </p>
    </div>
{/if}



<ul class="nav nav-tabs">
    <li class="{if $active_tab == 'dashboard'}active{/if}">
        <a href="{$current}&tab_module_verifactu=dashboard">
            <i class="icon-dashboard"></i> {l s='Dashboard' mod='verifactu'}
        </a>
    </li>
    <li class="{if $active_tab == 'configure'}active{/if}">
        <a href="{$current}&tab_module_verifactu=configure">
            <i class="icon-cogs"></i> {l s='Configuración' mod='verifactu'}
        </a>
    </li>
    <li class="{if $active_tab == 'sales_invoices'}active{/if}">
        <a href="{$current}&tab_module_verifactu=sales_invoices">
            <i class="icon-list-ul"></i> {l s='Facturas' mod='verifactu'}
        </a>
    </li>
    <li class="{if $active_tab == 'credit_slips'}active{/if}">
        <a href="{$current}&tab_module_verifactu=credit_slips">
            <i class="icon-list-alt"></i> {l s='Facturas por abono' mod='verifactu'}
        </a>
    </li>
    <li class="{if $active_tab == 'reg_facts'}active{/if}">
        <a href="{$current}&tab_module_verifactu=reg_facts">
            <i class="icon-list"></i> {l s='Registros de Facturación' mod='verifactu'}
        </a>
    </li>
    <li class="{if $active_tab == 'facturae'}active{/if}">
        <a href="{$current}&tab_module_verifactu=facturae">
            <i class="icon-file-text-o"></i> {l s='Facturas Electrónicas' mod='verifactu'}
        </a>
    </li>
    <li class="{if $active_tab == 'help'}active{/if}">
        <a href="{$current}&tab_module_verifactu=help">
            <i class="icon-question-circle"></i> {l s='Ayuda' mod='verifactu'}
        </a>
    </li>
</ul>

