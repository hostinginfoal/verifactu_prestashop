<div align="center">

# 🧾 VeriFactu — Módulo para PrestaShop

**Envío automático de registros de facturación al sistema Veri\*Factu de la AEAT**

[![PrestaShop](https://img.shields.io/badge/PrestaShop-1.6%20%7C%201.7%20%7C%208%20%7C%209-blue?logo=prestashop)](https://www.prestashop.com)
[![PHP](https://img.shields.io/badge/PHP-5.6%20%7C%207.x%20%7C%208.x-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![License](https://img.shields.io/badge/License-AFL--3.0-green)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.5.4-orange)](https://github.com/hostinginfoal/verifactu_prestashop/releases)
[![InFoAL](https://img.shields.io/badge/by-InFoAL%20S.L.-informational)](https://verifactu.infoal.com)

</div>

---

## 📋 ¿Qué es Veri\*Factu?

**Veri\*Factu** es el sistema de registro de facturación de la **AEAT (Agencia Tributaria española)**. A partir del 1 de julio de 2025, determinados contribuyentes estarán obligados a utilizar sistemas informáticos de facturación homologados que envíen datos a la AEAT en tiempo real. Este módulo automatiza todo ese proceso directamente desde PrestaShop, **sin necesidad de certificado digital**.

> 💡 El módulo actúa a través de la [**API InFoAL VeriFactu**](https://verifactu.infoal.com), que gestiona la comunicación con los servidores de la AEAT de forma segura y certificada.

---

## ✨ Características principales

| Funcionalidad | Descripción |
|---|---|
| 🚀 **Envío automático** | Al generarse una factura, el registro se envía a la AEAT sin intervención manual |
| 🔁 **Reintentos automáticos** | Backoff exponencial en caso de fallo de conexión (hasta 5 reintentos) |
| 📄 **Facturas rectificativas** | Envío automático de abonos/devoluciones como facturas R4/R5 |
| ✅ **Sin certificado digital** | La firma y comunicación con la AEAT la gestiona la API InFoAL |
| 📱 **Código QR** | QR de verificación incrustado automáticamente en la factura PDF |
| 🔍 **Comprobación de DNI/NIF** | Validación del NIF del cliente con la API en tiempo real |
| 📊 **Dashboard de estado** | Panel con estadísticas de envíos, errores y estado AEAT en tiempo real |
| 🗂️ **Trazabilidad completa** | Histórico de todos los registros enviados con su estado AEAT |
| 🛠️ **Herramientas de mantenimiento** | Verificación y reparación de base de datos desde el backoffice |
| 🌍 **Regímenes especiales** | Soporte para OSS, Canarias (IGIC), Ceuta/Melilla (IPSI) y Recargo de Equivalencia |
| 🏪 **Multishop** | Compatible con instalaciones multi-tienda de PrestaShop |
| 🐛 **Diagnóstico de soporte** | Generación de informe técnico para soporte, con envío directo o descarga ZIP |

---

## 🔄 ¿Cómo funciona?

```
PrestaShop genera factura
        │
        ▼
Módulo VeriFactu captura el evento
        │
        ▼
Construye el registro (XML/JSON AEAT)
        │
        ▼
Envía a la API InFoAL (conexión segura)
        │
        ▼
InFoAL firma y transmite a la AEAT
        │
        ▼
AEAT devuelve: Correcto / Incorrecto / AceptadoConErrores
        │
        ▼
Módulo almacena el resultado + QR en PrestaShop
```

---

## 🧩 Compatibilidad

| PrestaShop | PHP | Estado |
|---|---|---|
| 1.6.x | 5.6 — 7.4 | ✅ Compatible |
| 1.7.x | 7.1 — 8.1 | ✅ Compatible |
| 8.x | 7.4 — 8.3 | ✅ Compatible |
| 9.x | 8.1 — 8.3 | ✅ Compatible |

---

## 🚀 Instalación

### Requisitos previos

- Token de API InFoAL VeriFactu ([solicítalo gratuitamente aquí](https://verifactu.infoal.com))
- NIF del emisor de facturas (tu empresa o autónomo)
- Extensión PHP `curl` habilitada en el servidor

### Pasos

1. **Descarga** el archivo `verifactu.zip` de la [última versión](https://github.com/hostinginfoal/verifactu_prestashop/releases/latest/download/verifactu.zip)
2. En el **backoffice** de PrestaShop → Módulos → Subir un módulo → selecciona el ZIP
3. **Instala** el módulo
4. Ve a **Configuración del módulo** e introduce:
   - Tu **API Token** de InFoAL
   - El **NIF del emisor**
   - La **razón social** y domicilio fiscal (para el QR)
5. ✅ ¡Listo! A partir de ahora las facturas se registrarán automáticamente

---

## 📂 Estructura de los listados

El módulo añade tres secciones en el backoffice:

### 📑 Facturas de Venta
Todas las facturas ordinarias generadas desde PrestaShop. Para cada una puedes ver su **estado en Veri\*Factu** (Correcto, Incorrecto, Pendiente…) y actuar: reenviar, verificar NIF o anular el registro.

### 📑 Facturas por Abono
Los reembolsos y notas de crédito. Se registran en la AEAT como **facturas rectificativas** (tipo R4/R5). Seguimiento independiente al de las facturas originales.

### 📊 Registros de Facturación
Histórico técnico completo de cada envío a la AEAT. Cada vez que una factura se envía (o reenvía), se genera un nuevo registro con:
- **id_reg_fact** — identificador único asignado por la AEAT
- **Fecha de envío** — `date_sent` real del envío a la API
- **Estado en cola** — estado de procesamiento
- **Código QR** — enlace de verificación AEAT

> ⚠️ Una misma factura puede tener **varios registros** (uno por intento de envío).

---

## 🌍 Enlace de verificación AEAT

Los clientes pueden verificar sus facturas directamente en la sede electrónica de la AEAT:

🔗 [https://sede.agenciatributaria.gob.es/Sede/iva/sistemas-informaticos-facturacion-verifactu/gestiones.html](https://sede.agenciatributaria.gob.es/Sede/iva/sistemas-informaticos-facturacion-verifactu/gestiones.html)

---

## ⚙️ Estados de un registro

| Estado | Significado |
|---|---|
| `nuevo` | Pendiente de enviar (en cola) |
| `pendiente` | Enviado a la AEAT, esperando confirmación |
| `sincronizado` | Procesado con respuesta de la AEAT |
| `api_error` | Error de comunicación, se reintentará automáticamente |
| `failed` | Agotados los reintentos — requiere acción manual |
| `stalled` | Más de 7 días sin confirmación desde el envío |

**Respuesta AEAT:**

| Valor | Significado |
|---|---|
| `Correcto` | ✅ Registro aceptado por la AEAT |
| `AceptadoConErrores` | ⚠️ Aceptado con avisos no bloqueantes |
| `Incorrecto` | ❌ Rechazado — revisar el detalle del error |

---

## 🔧 Configuración avanzada

| Opción | Descripción |
|---|---|
| **Modo Pruebas** | Envía a los servidores de sandbox de la AEAT |
| **OSS / Ventanilla Única** | Para ventas B2C intracomunitarias |
| **Canarias / Ceuta / Melilla** | Activación de IGIC/IPSI |
| **Recargo de Equivalencia** | Detección y desglose automático |
| **Anulaciones** | Permite anular en Veri\*Factu facturas ya registradas |
| **Modo Debug** | Logs detallados en `/modules/verifactu/logs/verifactu.log` |
| **Cronjob** | Automatización de reintentos y comprobación de pendientes |

### URL del Cronjob

```
https://tu-tienda.com/modules/verifactu/cron.php?token=TU_TOKEN_CRON
```

Recomendado: cada **5 minutos**.

---

## 🆘 Soporte

| Canal | Enlace |
|---|---|
| 🌐 Web | [verifactu.infoal.com](https://verifactu.infoal.com) |
| 📧 Email | [hosting@infoal.com](mailto:hosting@infoal.com) |
| 📖 Documentación | [Guía completa del módulo](https://verifactu.infoal.com/prestashop/guia-de-usuario-modulo-verifactu-para-prestashop) |
| 🐛 Issues | [GitHub Issues](https://github.com/hostinginfoal/verifactu_prestashop/issues) |

---

## 📄 Licencia

Distribuido bajo licencia **AFL-3.0**. Consulta el archivo [LICENSE](LICENSE) para más detalles.

---

<div align="center">

Desarrollado con ❤️ por [**InFoAL S.L.**](https://www.infoal.com) — Especialistas en soluciones de facturación electrónica para PrestaShop.

</div>
