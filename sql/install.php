<?php
/**
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
*/
$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'verifactu_reg_fact` (
    `id_reg_fact` int NOT NULL,
    `id_order_invoice` int NOT NULL,
    `invoice_number` varchar(100) DEFAULT NULL,
    `tipo` varchar(20) DEFAULT NULL,
    `EstadoEnvio` varchar(100) DEFAULT NULL,
    `EstadoRegistro` varchar(100) DEFAULT NULL,
    `CodigoErrorRegistro` varchar(100) DEFAULT NULL,
    `DescripcionErrorRegistro` text,
    `urlQR` varchar(255) DEFAULT NULL,
    `id_queue` int NOT NULL,
    `estado_queue` varchar(20) DEFAULT NULL,
    `InvoiceNumber` varchar(50) DEFAULT NULL,
    `IssueDate` date DEFAULT NULL,
    `TipoOperacion` varchar(45) DEFAULT NULL,
    `EmpresaNombreRazon` varchar(45) DEFAULT NULL,
    `EmpresaNIF` varchar(20) DEFAULT NULL,
    `hash` varchar(255) DEFAULT NULL,
    `cadena` text,
    `AnteriorHash` varchar(255) DEFAULT NULL,
    `TipoFactura` varchar(45) DEFAULT NULL,
    `FacturaSimplificadaArt7273` varchar(45) DEFAULT NULL,
    `FacturaSinIdentifDestinatarioArt61d` varchar(45) DEFAULT NULL,
    `CalificacionOperacion` varchar(45) DEFAULT NULL,
    `Macrodato` varchar(45) DEFAULT NULL,
    `Cupon` varchar(45) DEFAULT NULL,
    `TotalTaxOutputs` decimal(15,2) DEFAULT NULL,
    `InvoiceTotal` decimal(15,2) DEFAULT NULL,
    `BuyerName` varchar(255) DEFAULT NULL,
    `BuyerCorporateName` varchar(255) DEFAULT NULL,
    `BuyerTaxIdentificationNumber` varchar(45) DEFAULT NULL,
    `BuyerCountryCode` varchar(10) DEFAULT NULL,
    `IDOtroIDType` varchar(45) DEFAULT NULL,
    `IDOtroID` varchar(45) DEFAULT NULL,
    `TipoRectificativa` varchar(10) DEFAULT NULL,
    `CorrectiveInvoiceNumber` varchar(50) DEFAULT NULL,
    `CorrectiveInvoiceSeriesCode` varchar(10) DEFAULT NULL,
    `CorrectiveIssueDate` date DEFAULT NULL,
    `CorrectiveBaseAmount` decimal(15,2) DEFAULT NULL,
    `CorrectiveTaxAmount` decimal(15,2) DEFAULT NULL,
    `FechaHoraHusoGenRegistro` varchar(45) DEFAULT NULL,
    `fechaHoraRegistro` datetime DEFAULT NULL,
    `SIFNombreRazon` varchar(255) DEFAULT NULL,
    `SIFNIF` varchar(45) DEFAULT NULL,
    `SIFNombreSIF` varchar(45) DEFAULT NULL,
    `SIFIdSIF` varchar(45) DEFAULT NULL,
    `SIFVersion` varchar(45) DEFAULT NULL,
    `SIFNumeroInstalacion` varchar(45) DEFAULT NULL,
    `SIFTipoUsoPosibleSoloVerifactu` varchar(45) DEFAULT NULL,
    `SIFTipoUsoPosibleMultiOT` varchar(45) DEFAULT NULL,
    `SIFIndicadorMultiplesOT` varchar(45) DEFAULT NULL,
    `id_shop` int NOT NULL,
    PRIMARY KEY  (`id_reg_fact`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';


$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'verifactu_order_invoice` (
    `id_order_invoice` int(11) NOT NULL,
    `estado` VARCHAR(40),
    `id_reg_fact` int(11) NOT NULL,
    `verifactuEstadoEnvio` VARCHAR(100),
    `verifactuEstadoRegistro` VARCHAR(100),
    `verifactuCodigoErrorRegistro` VARCHAR(100),
    `verifactuDescripcionErrorRegistro` TEXT,
    `urlQR` VARCHAR(255),
    `anulacion` int(11) NOT NULL,
    `TipoFactura` VARCHAR(100),
    PRIMARY KEY  (`id_order_invoice`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'verifactu_order_slip` (
    `id_order_slip` int(11) NOT NULL,
    `estado` VARCHAR(40),
    `id_reg_fact` int(11) NOT NULL,
    `verifactuEstadoEnvio` VARCHAR(100),
    `verifactuEstadoRegistro` VARCHAR(100),
    `verifactuCodigoErrorRegistro` VARCHAR(100),
    `verifactuDescripcionErrorRegistro` TEXT,
    `anulacion` int(11) NOT NULL,
    `TipoFactura` VARCHAR(100),
    PRIMARY KEY  (`id_order_slip`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
