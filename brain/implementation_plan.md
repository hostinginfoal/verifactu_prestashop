# Plan: Dashboard con estadísticas por año

## Cambios:

### 1. verifactu.php — renderDashboard()
- Obtener años disponibles en la BD (para navegación)
- Obtener el año seleccionado (GET param `vf_year`, default año actual)  
- Nueva query: stats por mes del año seleccionado (correctos, incorrectos, aceptados_con_errores, total)
- Pasar `monthly_stats`, `selected_year`, `available_years` a Smarty

### 2. dashboard.tpl
- Reemplazar FILA 2 (contadores por estado) con:
  - Header "Estadísticas de Envíos" + navegación ← AÑO →
  - 4 tarjetas: Total, Correctos, Incorrectos, Acept. con errores (del año)
  - Gráfico de barras apiladas por mes (Chart.js CDN)
  - Tabla mensual con 12 filas
