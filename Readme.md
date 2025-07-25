# Módulo VeriFactu para PrestaShop

Nuestro módulo conecta tu tienda directamente al sistema Veri*Factu, enviando los registros de facturación de forma automática y segura.

***NOTA IMPORTANTE: Nuestra API de momento solo envía datos al entorno de PRE-PRODUCCIÓN de VERI*FACTU puesto que la AEAT no ha publicado un entorno real todavía***

- Envío automático de registros de facturación.
- Respuesta de verifactu con código QR y enlace.
- Sin necesidad de certificado digital.
- Compatible con PrestaShop 8.x y versiones anteriores.
- Panel de gestión y visualización de logs.
- Cumplimiento con la normativa VeriFactu.

Nuestra solución se integra de forma nativa en el corazón de PrestaShop. Al generarse una factura, el módulo envía el registro de facturación directamente al sistema verifactu a través de nuestra API:

- Recopila los Datos: Captura toda la información necesaria de la factura.
- Genera el Registro: Construye el fichero XML con el formato exigido por la AEAT.
- Envía de Forma Segura: Transmite el registro al sistema VeriFactu a través de una conexión segura (Infoal Verifactu API).
- Recibe Confirmación: Almacena la respuesta y el código de validación, garantizando la trazabilidad.

***Solicita tu Token de acceso gratuïto a la API Infoal Verifactu en https://verifactu.infoal.com.***

PRIMER RELEASE
- Configuración del token de API InFoAL Veri*Factu
- Envío manual de los registros de Alta desde la ficha del pedido.
- Envío manual de los registros de Anulación desde la ficha del pedido.
- Registro de logs

PROXIMAMENTE
- Envío automático de los registros de facturación cuando se generen las facturas.
- Envío automático de los registros de facturación de las facturas rectificativas al generar las facturas de abono.
- Listado de los registros de facturación enviado en el formulario de configuración del módulo
- Listado de los logs de los registros enviados en el formulario de configuración del módulo
- Código QR en la factura generada de prestashop.
