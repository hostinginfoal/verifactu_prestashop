<?php
/**
 * InFoAL S.L.
 *
 * NOTICE OF LICENSE
 * Proprietary - All Rights Reserved.
 * @author    InFoAL S.L. <hosting@infoal.com>
 * @copyright 2025 InFoAL S.L.
 *
 * TODO-14: VerifactuQRService
 * Responsabilidad: generación, caché en memoria y limpieza de imágenes QR temporales.
 * Extraído de verifactu.php para reducir el tamaño del fichero principal.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Servicio de generación de imágenes QR para VeriFactu.
 *
 * Uso desde verifactu.php:
 *   require_once _PS_MODULE_DIR_ . 'verifactu/services/VerifactuQRService.php';
 *   $qrService = new VerifactuQRService();
 *   $imgSrc = $qrService->generateQrImage($url, 'inv_123');
 */
class VerifactuQRService
{
    /** @var array Caché de rutas de ficheros QR generados en esta request. */
    private $temp_qr_files = [];

    /** @var string Directorio donde se guardan los QR temporales del módulo. */
    private $qr_lib_path;

    public function __construct()
    {
        $this->qr_lib_path = _PS_MODULE_DIR_ . 'verifactu/lib/phpqrcode/qrlib.php';
    }

    /**
     * Genera (o recupera de caché) una imagen QR en base64 data URI.
     *
     * @param string $url   URL del QR.
     * @param string $key   Clave única para cachear el resultado en esta request.
     * @param int    $size  Tamaño del QR en píxeles (por defecto 3 → ~87px).
     * @return string       Data URI base64 o cadena vacía si no hay URL.
     */
    public function generateQrImage($url, $key = 'default', $size = 3)
    {
        if (empty($url)) {
            return '';
        }

        if (isset($this->temp_qr_files[$key])) {
            return $this->temp_qr_files[$key];
        }

        if (!file_exists($this->qr_lib_path)) {
            return '';
        }

        require_once $this->qr_lib_path;

        $tmp_file = tempnam(sys_get_temp_dir(), 'vfqr_') . '.png';

        try {
            QRcode::png($url, $tmp_file, QR_ECLEVEL_L, $size, 1);
            if (file_exists($tmp_file)) {
                $image_data = file_get_contents($tmp_file);
                $base64     = base64_encode($image_data);
                $result     = 'data:image/png;base64,' . $base64;
                $this->temp_qr_files[$key] = $result;
                @unlink($tmp_file);
                return $result;
            }
        } catch (Exception $e) {
            // Silenciar: no bloqueamos la carga del pedido por un QR roto
        }

        return '';
    }

    /**
     * Limpia todos los ficheros temporales de QR en disco (si quedaran huérfanos).
     */
    public function cleanupTempFiles()
    {
        $tmp_dir = sys_get_temp_dir();
        $files   = glob($tmp_dir . '/vfqr_*.png');
        if (is_array($files)) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
}
