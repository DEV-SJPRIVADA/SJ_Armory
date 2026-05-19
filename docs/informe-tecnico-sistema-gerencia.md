# Informe técnico del sistema de control de armamento

**Destinatario:** Gerencia  
**Empresa:** SJ SEGURIDAD PRIVADA LTDA  
**Sistema:** aplicación web interna de gestión de armamento (proyecto **SJ Armory**)  
**Elaboración:** documento generado a partir del análisis del sistema y la documentación del repositorio (`README.md`).  
**Fecha del informe:** [Completar: mes / año]

---

## 1. Resumen ejecutivo

La empresa contaba con el registro del armamento en **hojas de cálculo (Excel)**, con edición manual y limitaciones para trazabilidad, reportes y trabajo simultáneo. Se implementó un **sistema web propio** que centraliza inventario, asignaciones, documentación, alertas, mapa operativo, transferencias, novedades operativas y auditoría, con acceso por **roles** (administrador, responsable, auditor).

**Puesta en producción prevista / realizada:** [30 de abril de 2026 — ajustar si aplica].  
**Infraestructura:** hosting **Hostinger** (contratación anual).  
**Desarrollo:** **Wilder Rivera** (Supervisor), sin contratación de proveedor de desarrollo ni herramientas de desarrollo adicionales a cargo de la empresa.  
**Capacitación:** convocatoria nacional por correo; sesión remota (p. ej. Microsoft Teams) con evidencia de asistencia.

> **Figura 1 — [Insertar imagen]**  
> *Logo de la empresa o pantalla institucional (opcional portada del informe).*

---

## 2. Objetivo del sistema

- Sustituir el control manual disperso por una **fuente única de verdad** en base de datos.
- Dar **trazabilidad** a asignaciones de armas a clientes, puestos y trabajadores.
- Facilitar el **seguimiento documental** (vencimientos, alertas, exportaciones).
- Apoyar la **operación diaria** (dashboard, mapa, notificaciones en tiempo real cuando está habilitado el servicio de *websockets*).
- Registrar acciones relevantes en **auditoría** para consulta posterior.

> **Figura 2 — [Insertar imagen]**  
> *Diagrama simple “antes / después” (Excel vs sistema web). Puede ser un esquema en PowerPoint exportado a PNG.*

---

## 3. Alcance funcional (qué cubre el sistema)

| Área | Descripción breve |
|------|-------------------|
| **Armas** | Alta, consulta, edición de datos; fotos técnicas y de permiso; documentos; exportación; sin borrado físico de armas. |
| **Asignaciones** | Operativa (arma–cliente–responsable) e interna (arma–puesto y/o trabajador). |
| **Transferencias** | Envío, aceptación, cancelación; historial; campos opcionales de munición/proveedores según reglas del sistema. |
| **Clientes, puestos, trabajadores** | Gestión con archivo/historial donde aplica; políticas por rol. |
| **Usuarios** | Roles ADMIN / RESPONSABLE / AUDITOR; cartera de clientes por responsable; credenciales y flujos de acceso. |
| **Cargas masivas** | Importación de armas por lotes (admin); plantillas de reverso autenticado de permiso (porte/tenencia) para PDF y ficha. |
| **Reportes** | Armas por cliente, sin destino, historial por arma, auditoría, **novedades operativas** (hurtos, pérdidas, etc.). |
| **Alertas** | Documentos vencidos, por vencer y armas sin alertas; filtro por **uno o varios meses** (panel con checkboxes y años distintos); modales con búsqueda, selección y exportación `.docx` / vista previa PDF (`Revalidacion_{mes}_{año}`). |
| **Mapa** | Visualización de ubicación operativa según reglas de prioridad (puesto / cliente / trabajador). |
| **Dashboard** | Indicadores y gráficos en tiempo casi real (actualización por eventos cuando broadcasting está activo). |
| **Notificaciones** | Campana con no leídas; historial en menú de usuario. |
| **Permisos (documento)** | Descarga de permiso como PDF (frente + reverso según plantillas), cuando aplica la configuración. |

> **Figura 3 — [Insertar imagen]**  
> *Captura del **menú principal** o barra de navegación autenticada mostrando las secciones visibles para un usuario **ADMIN**.*

---

## 4. Roles y gobierno de acceso

- **ADMIN:** acceso amplio a configuración operativa, reportes, importaciones y usuarios.  
- **RESPONSABLE:** ve y opera según **cartera** de clientes asignada y armas bajo su responsabilidad; niveles 1 (gestión) y 2 (lectura) según configuración.  
- **AUDITOR:** consulta inventario, reportes y alertas; sin administración operativa completa.

Esto reduce el riesgo de que un usuario modifique datos fuera de su ámbito.

> **Figura 4 — [Insertar imagen]**  
> *Captura de la pantalla de **usuarios** o de **edición de usuario** mostrando el **rol** y, si aplica, el **nivel de responsabilidad**.*

---

## 5. Arquitectura técnica (resumen para gerencia)

| Componente | Tecnología principal |
|------------|----------------------|
| Aplicación servidor | **PHP**, framework **Laravel 10** |
| Base de datos | **MySQL** (en producción típica) |
| Interfaz | **HTML**, **Tailwind CSS**, **Vite**, **JavaScript** |
| Tiempo real (opcional) | **Laravel Reverb** + **Echo** (*WebSockets*) |
| Archivos | Almacenamiento en servidor según discos configurados (`storage`) |

El detalle de variables de entorno, compilación de *frontend* y despliegue está documentado en el `README.md` del repositorio para el equipo técnico.

> **Figura 5 — [Insertar imagen]**  
> *Opcional: esquema de arquitectura (navegador → hosting → base de datos). Puede ser una figura de una sola página.*

---

## 6. Seguridad y continuidad (puntos a conocer gerencia)

- Acceso por **sesión web** autenticada; rutas sensibles bajo middleware de autenticación.  
- **Auditoría** de acciones relevantes en tabla de logs.  
- Se recomienda política de **copias de seguridad** periódicas de la base de datos y de `storage` según criterio de TI.  
- El sistema **no elimina físicamente** armas; se privilegia trazabilidad.

> **Figura 6 — [Insertar imagen]**  
> *Captura de un registro en **auditoría** (reporte de auditoría) mostrando usuario, fecha y acción.*

---

## 7. Pantallas representativas (guía de capturas)

Use las figuras siguientes para ilustrar el informe. Sustituya cada bloque por la imagen correspondiente (PNG o JPG, buena legibilidad).

### 7.1 Acceso

> **Figura 7 — [Insertar imagen]**  
> *Pantalla de **inicio de sesión** (`/login`).*

### 7.2 Tablero principal

> **Figura 8 — [Insertar imagen]**  
> *Vista del **dashboard** con KPIs y al menos un gráfico visible.*

### 7.3 Inventario de armas

> **Figura 9 — [Insertar imagen]**  
> *Listado de **armas** (vista índice) con filtros o columnas principales visibles.*

> **Figura 10 — [Insertar imagen]**  
> *Ficha de un **arma** (detalle): datos generales o pestaña visible.*

### 7.4 Operación en campo

> **Figura 11 — [Insertar imagen]**  
> *Vista del **mapa** con marcadores o cluster.*

### 7.5 Documentación y alertas

Módulo **Alertas documentales**: tres tarjetas resumen (vencidos, por vencer en ventana de 120 días, sin alertas). El filtro **Meses** permite marcar varios períodos en un panel con checkboxes (cambio de año con flechas); al pulsar **Filtrar** se acota la consulta. Desde cada tarjeta se abre un modal con búsqueda, exclusión de armas con novedad bloqueante, selección de armas y **Descargar relación** (Word) o vista previa (PDF). El archivo descargado se nombra automáticamente, por ejemplo `Revalidacion_mayo_2025.docx`.

> **Figura 12 — [Insertar imagen]**  
> *Pantalla de **alertas** (tarjetas de vencidos / por vencer / sin alertas) con el panel **Meses** abierto mostrando checkboxes por mes.*

> **Figura 13 — [Insertar imagen]**  
> *Detalle de **documentos** en la ficha de un arma (o lista de documentos), si aplica.*

### 7.6 Transferencias

> **Figura 14 — [Insertar imagen]**  
> *Listado de **transferencias** (unificada) mostrando estados o columnas clave.*

### 7.7 Novedades operativas

> **Figura 15 — [Insertar imagen]**  
> *Reporte de **novedades operativas** (dashboard de tipos / modalidades).*

> **Figura 16 — [Insertar imagen]**  
> *Mismo módulo con el modal **Lista** abierto y el **buscador** visible (opcional pero muy ilustrativo).*

### 7.8 Cargas masivas

> **Figura 17 — [Insertar imagen]**  
> *Centro de **cargas masivas** / **Subir armas** (vista principal o modal de carga).*

### 7.9 Notificaciones

> **Figura 18 — [Insertar imagen]**  
> ***Campana** de notificaciones desplegada o menú de **historial de notificaciones**.*

---

## 8. Implementación y costos (resumen)

| Concepto | Situación |
|----------|-----------|
| **Hosting** | Contratación **Hostinger** por **un año**; anexar factura al expediente. |
| **Desarrollo** | Realizado por **Wilder Rivera**; **sin recargos adicionales** a la empresa ni contratación de proveedores ni herramientas de desarrollo. |
| **Despliegue** | Con acompañamiento de **Carlos Andrés Gutiérrez**, Analista TIC (según registro interno). |
| **Capacitación** | Convocatoria nacional por **correo**; evidencia de sesión (p. ej. **Teams**). |

> **Figura 19 — [Insertar imagen]**  
> *Captura del **panel de Hostinger** o pantalla de dominio/hosting **sin mostrar contraseñas** (solo zona pública segura).*

---

## 9. Riesgos y dependencias

- **Disponibilidad del hosting:** indisponibilidad afecta el acceso; mitigar con proveedor estable y monitoreo.  
- **Copias de seguridad:** riesgo de pérdida si no hay respaldo verificado; definir responsable y frecuencia.  
- **Tiempo real:** si Reverb no está en ejecución, la app funciona pero sin actualizaciones instantáneas en pantalla (según configuración).  
- **Geocodificación:** uso de servicios externos (Nominatim); en evolución conviene límites de uso y políticas de seguridad (detalle técnico en README).

---

## 10. Conclusiones y recomendaciones

1. El sistema cumple el rol de **plataforma única** para el control de armamento frente al Excel manual.  
2. Se recomienda **manual de usuario** por rol (ADMIN, RESPONSABLE, AUDITOR) y **plan de respaldos** aprobado por TI.  
3. Mantener **registro de cambios** (como el FO-CE-12) para mejoras posteriores.  
4. Programar **revisiones periódicas** con Dirección de Operaciones para priorizar mejoras.

---

## 11. Anexos sugeridos

- Factura / contrato **Hostinger**.  
- Evidencia de **capacitación** (captura Teams, lista de asistentes).  
- Constancia de **go-live** (correo o acta con fecha).  
- Extracto del **`README.md`** del repositorio (documentación técnica).

---

## 12. Control del documento

| Versión | Fecha | Autor | Cambios |
|---------|--------|--------|---------|
| 0.1 | [fecha] | [nombre] | Borrador inicial |

---

*Fin del informe.*
