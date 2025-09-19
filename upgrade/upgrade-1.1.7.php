<?php
/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * This function updates your module from previous versions to the version 1.1,
 * usefull when you modify your database, or register a new hook ...
 * Don't forget to create one file per version.
 */
function upgrade_module_1_1_7($module)
{
    $sql = array();
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'verifactu_reg_fact` 
ADD COLUMN `id_queue` int(11) NOT NULL AFTER `urlQR`,
ADD COLUMN `estado_queue` VARCHAR(20) NOT NULL AFTER `id_queue`,
ADD COLUMN `InvoiceNumber` VARCHAR(50) NULL AFTER `estado_queue`,
ADD COLUMN `IssueDate` DATE NULL AFTER `InvoiceNumber`,
ADD COLUMN `TipoOperacion` VARCHAR(45) NULL AFTER `IssueDate`,
ADD COLUMN `EmpresaNombreRazon` VARCHAR(45) NULL AFTER `TipoOperacion`,
ADD COLUMN `EmpresaNIF` VARCHAR(20) NULL AFTER `EmpresaNombreRazon`,
ADD COLUMN `hash` VARCHAR(255) NULL AFTER `EmpresaNIF`,
ADD COLUMN `cadena` TEXT NULL AFTER `hash`,
ADD COLUMN `AnteriorHash` VARCHAR(255) NULL AFTER `cadena`,
ADD COLUMN `TipoFactura` VARCHAR(45) NULL AFTER `AnteriorHash`,
ADD COLUMN `FacturaSimplificadaArt7273` VARCHAR(45) NULL AFTER `TipoFactura`,
ADD COLUMN `FacturaSinIdentifDestinatarioArt61d` VARCHAR(45) NULL AFTER `FacturaSimplificadaArt7273`,
ADD COLUMN `CalificacionOperacion` VARCHAR(45) NULL AFTER `FacturaSinIdentifDestinatarioArt61d`,
ADD COLUMN `Macrodato` VARCHAR(45) NULL AFTER `CalificacionOperacion`,
ADD COLUMN `Cupon` VARCHAR(45) NULL AFTER `Macrodato`,
ADD COLUMN `TotalTaxOutputs` DECIMAL(15,2) NULL AFTER `Cupon`,
ADD COLUMN `InvoiceTotal` DECIMAL(15,2) NULL AFTER `TotalTaxOutputs`,
ADD COLUMN `BuyerName` VARCHAR(255) NULL AFTER `InvoiceTotal`,
ADD COLUMN `BuyerCorporateName` VARCHAR(255) NULL AFTER `BuyerName`,
ADD COLUMN `BuyerTaxIdentificationNumber` VARCHAR(45) NULL AFTER `BuyerCorporateName`,
ADD COLUMN `BuyerCountryCode` VARCHAR(10) NULL AFTER `BuyerTaxIdentificationNumber`,
ADD COLUMN `IDOtroIDType` VARCHAR(45) NULL AFTER `BuyerCountryCode`,
ADD COLUMN `IDOtroID` VARCHAR(45) NULL AFTER `IDOtroIDType`,
ADD COLUMN `TipoRectificativa` VARCHAR(10) NULL AFTER `IDOtroID`,
ADD COLUMN `CorrectiveInvoiceNumber` VARCHAR(50) NULL AFTER `TipoRectificativa`,
ADD COLUMN `CorrectiveInvoiceSeriesCode` VARCHAR(10) NULL AFTER `CorrectiveInvoiceNumber`,
ADD COLUMN `CorrectiveIssueDate` DATE NULL AFTER `CorrectiveInvoiceSeriesCode`,
ADD COLUMN `CorrectiveBaseAmount` DECIMAL(15,2) NULL AFTER `CorrectiveIssueDate`,
ADD COLUMN `CorrectiveTaxAmount` DECIMAL(15,2) NULL AFTER `CorrectiveBaseAmount`,
ADD COLUMN `FechaHoraHusoGenRegistro` VARCHAR(45) NULL AFTER `CorrectiveTaxAmount`,
ADD COLUMN `fechaHoraRegistro` DATETIME NULL AFTER `FechaHoraHusoGenRegistro`,
ADD COLUMN `SIFNombreRazon` VARCHAR(255) NULL AFTER `fechaHoraRegistro`,
ADD COLUMN `SIFNIF` VARCHAR(45) NULL AFTER `SIFNombreRazon`,
ADD COLUMN `SIFNombreSIF` VARCHAR(45) NULL AFTER `SIFNIF`,
ADD COLUMN `SIFIdSIF` VARCHAR(45) NULL AFTER `SIFNombreSIF`,
ADD COLUMN `SIFVersion` VARCHAR(45) NULL AFTER `SIFIdSIF`,
ADD COLUMN `SIFNumeroInstalacion` VARCHAR(45) NULL AFTER `SIFVersion`,
ADD COLUMN `SIFTipoUsoPosibleSoloVerifactu` VARCHAR(45) NULL AFTER `SIFNumeroInstalacion`,
ADD COLUMN `SIFTipoUsoPosibleMultiOT` VARCHAR(45) NULL AFTER `SIFTipoUsoPosibleSoloVerifactu`,
ADD COLUMN `SIFIndicadorMultiplesOT` VARCHAR(45) NULL AFTER `SIFTipoUsoPosibleMultiOT`,
ADD COLUMN `id_shop` int(11) NOT NULL AFTER `SIFIndicadorMultiplesOT`';

$sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'verifactu_reg_fact` CHANGE COLUMN `verifactuEstadoEnvio` `EstadoEnvio` VARCHAR(100) NULL ,
CHANGE COLUMN `verifactuEstadoRegistro` `EstadoRegistro` VARCHAR(100) NULL ,
CHANGE COLUMN `verifactuCodigoErrorRegistro` `CodigoErrorRegistro` VARCHAR(100) NULL ,
CHANGE COLUMN `verifactuDescripcionErrorRegistro` `DescripcionErrorRegistro` TEXT NULL';

$sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'verifactu_order_invoice` ADD COLUMN `TipoFactura` VARCHAR(100) NULL AFTER `anulacion`';
$sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'verifactu_order_slip` ADD COLUMN `TipoFactura` VARCHAR(100) NULL AFTER `anulacion`';

    foreach ($sql as $query) {
        if (Db::getInstance()->execute($query) == false) {
            return false;
        }
    }

    return true;
}
