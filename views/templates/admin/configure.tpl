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

<div class="panel">
    <h3><i class="icon icon-credit-card"></i> {l s='VeriFactu by InFoAL' mod='verifactu'}</h3>
    <p>
        <strong>{l s='Envía los registros de facturación de forma automática al sistema VeriFactu' mod='verifactu'}</strong><br />
    </p>
    <br />
    <p>
        {l s='Si no dispones de una clave de API, solicita una gratuita en ' mod='verifactu'}<a href="https://verifactu.infoal.com" target="_blank">https://verifactu.infoal.com</a>
    </p>
</div>

<div class="panel">
	<h3><i class="icon icon-tags"></i> {l s='Documentación' mod='verifactu'}</h3>
	<p>
		&raquo;
        {l s='Puedes ver la documentación del módulo en el siguiente enlace' mod='verifactu'} : <a href="https://github.com/hostinginfoal/verifactu_prestashop" target="_blank">https://github.com/hostinginfoal/verifactu_prestashop</a>
	</p>
</div>

<ul class="nav nav-tabs">
    <li class="{if $active_tab == 'configure'}active{/if}">
        <a href="{$current}&tab_module_verifactu=configure">
            <i class="icon-cogs"></i> {l s='Configuración' mod='verifactu'}
        </a>
    </li>
    <li class="{if $active_tab == 'invoices'}active{/if}">
        <a href="{$current}&tab_module_verifactu=invoices">
            <i class="icon-list"></i> {l s='Estado facturas' mod='verifactu'}
        </a>
    </li>
    <li class="{if $active_tab == 'reg_facts'}active{/if}">
        <a href="{$current}&tab_module_verifactu=reg_facts">
            <i class="icon-list"></i> {l s='Registros de Facturación' mod='verifactu'}
        </a>
    </li>
    {if 1==2}
    <li class="{if $active_tab == 'logs'}active{/if}">
        <a href="{$current}&tab_module_verifactu=logs">
            <i class="icon-send"></i> {l s='Logs' mod='verifactu'}
        </a>
    </li>
    {/if}
</ul>

