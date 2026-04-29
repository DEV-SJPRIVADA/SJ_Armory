# 🛡️ SJ Armory

Sistema web para **gestión de armamento**, **asignaciones operativas**, **transferencias**, **documentación**, **trazabilidad** y **auditoría**, con foco en operación diaria (dashboard, mapa, alertas) y control de acceso por rol/nivel.

> ✅ Este `README.md` está generado a partir del análisis del codebase (Laravel 10, Reverb, policies, controllers y `.env.example`).

---

## 📌 Alcance funcional

- ✅ **Armas**: alta/edición, fotos (técnicas y permiso), documentos, exportación, inventario.
- ✅ **Asignaciones**:
  - **Operativa** (arma ↔ cliente/responsable)
  - **Interna** (arma ↔ puesto / trabajador)
- ✅ **Transferencias**: solicitudes, aceptación / rechazo.
- ✅ **Clientes / Puestos / Trabajadores / Usuarios** (puestos y trabajadores: archivo, historial de cambios, políticas por rol)
- ✅ **Cargas masivas**: validación previa, preview, ejecución por chunks, trazabilidad por lote.
- ✅ **Dashboard**: KPIs, métricas, gráficos y estado “as of”.
- ✅ **Alertas**: vencimientos documentales.
- ✅ **Mapa**: geocodificación y visualización operativa.
- ✅ **Auditoría**: registro de cambios y acciones críticas.
- ✅ **Realtime (Broadcasting)**: Laravel Reverb + Echo (WebSockets) para sincronización en tiempo real.

---

## 🧱 Stack y requisitos técnicos

### ✅ Backend

| Componente | Versión / Uso |
|---|---|
| **PHP** | **8.2+** recomendado para entorno local (Laragon). En `composer.json`: `php: ^8.1`. |
| **Laravel** | `^10.10` |
| **Auth** | Laravel Breeze + sesiones web |
| **API tokens** | Laravel Sanctum |
| **Realtime** | Laravel Reverb `^1.10` (servidor WebSocket) |
| **Docs** | `phpoffice/phpword` para `.docx` + `dompdf/dompdf` |
| **HTTP** | `guzzlehttp/guzzle` |

### ✅ Frontend

| Componente | Versión / Uso |
|---|---|
| **Vite** | `^5` |
| **Tailwind** | `^3` + `@tailwindcss/forms` |
| **Alpine.js** | UI reactividad |
| **Axios** | HTTP desde frontend |
| **Leaflet** | Mapa + clustering |
| **Echo** | `laravel-echo` + `pusher-js` (cliente) apuntando a Reverb |

### 🧰 Entorno local recomendado

- 🪟 **Windows + Laragon** (Apache + MySQL + PHP 8.2.x)
- 🟦 **Node.js 18+** (en Laragon suele existir como `C:\laragon\bin\nodejs\node-v18\`)

---

## 🚀 Instalación y puesta en marcha (paso a paso)

> ⚠️ En ambientes con datos reales: **PROHIBIDO** `migrate:fresh` / `db:seed` u operaciones destructivas.

### 1) Clonar e instalar dependencias (PHP)

```bash
git clone <repo-url>
cd SJ_Armory
composer install
```

### 2) Configurar `.env`

1. Copia `.env.example` a `.env`.
2. Ajusta, como mínimo:
   - `APP_URL` (si se accede por IP en red: `http://<IP_DEL_SERVIDOR>`)
   - `DB_*`
   - `SANCTUM_STATEFUL_DOMAINS` (debe incluir el host/IP por el que acceden los navegadores)
   - Reverb: `REVERB_*` y `VITE_REVERB_*`

```bash
copy .env.example .env
php artisan key:generate
```

### 3) Migraciones (no destructivas)

```bash
php artisan migrate
```

### 4) Instalar y compilar frontend

```bash
npm install
npm run build
```

> 🧩 Si `npm` no está en PATH, usa la terminal integrada de Laragon o exporta la ruta a Node antes de compilar.

### 5) Levantar Reverb (WebSockets)

En una **terminal aparte** (el proceso debe seguir en ejecución mientras usas la app):

```bash
php artisan reverb:start
```

Atajos definidos en el proyecto:

```bash
npm run reverb
```

```bash
composer reverb
```

> En **Windows**, el puerto **8080** suele estar reservado (Hyper-V/WSL). El `.env.example` usa **6001** para `REVERB_PORT` / `REVERB_SERVER_PORT`. Abra ese puerto en el firewall si hay clientes en LAN.

---

## 🧭 Arquitectura de carpetas

| Ruta | Propósito |
|---|---|
| `app/Http/Controllers` | Casos de uso (CRUD, asignaciones, transferencias, reportes) |
| `app/Models` | Dominio / Eloquent (Weapon, Client, Assignments, Transfers, etc.) |
| `app/Policies` | Autorización por rol/alcance (`WeaponPolicy`, `ClientPolicy`, etc.) |
| `app/Services` | Lógica de negocio (métricas, importaciones, documentos, geocoding) |
| `app/Events` | Eventos broadcast (Realtime) |
| `resources/views` | UI (Blade) + componentes |
| `resources/js` | Bootstrap (Echo), dashboard, sincronización realtime |
| `routes/web.php` | Rutas web (auth + módulos) |
| `routes/channels.php` | Canales de broadcasting |
| `config/broadcasting.php` + `config/reverb.php` | Configuración broadcasting + Reverb |
| `database/migrations` | Esquema de datos |
| `storage/` | Archivos y cachés (con `.gitignore`) |

---

## 🧑‍⚖️ Roles y permisos (según lógica actual del código)

> Modelo: `app/Models/User.php`  
> Políticas: `app/Policies/*`

### 👥 Roles (`users.role`)

- 🟦 **ADMIN**
  - Acceso total.
  - Gestión de cartera (asignación cliente ↔ responsable).
  - Administración de entidades operativas, reportes y alertas.

- 🟩 **RESPONSABLE**
  - Alcance restringido a su **cartera** (`user_clients`) y a armas bajo su responsabilidad activa.
  - Capacidades condicionadas por nivel.

- 🟨 **AUDITOR**
  - Consulta (inventario/reportes/alertas), sin administración operativa.

### 🧩 Niveles de responsabilidad (`responsibility_levels.level`)

- **Nivel 1**: responsable operativo con gestión.
- **Nivel 2**: responsable solo lectura.

### 🔐 Reglas clave (extracto)

- `WeaponPolicy`
  - `viewAny`: ADMIN / RESPONSABLE / AUDITOR
  - `view`:
    - ADMIN: ✅
    - RESPONSABLE: ✅ solo si es responsable activo del arma
    - AUDITOR: ✅
  - `assignToClient`: ADMIN o RESPONSABLE Nivel 1 (condicionado al arma)
  - `delete`: **siempre false** (borrado físico deshabilitado)

- `ClientPolicy`
  - `view` para RESPONSABLE: solo si el cliente está en su cartera (`user_clients`)
  - `create/update/delete`: solo ADMIN

---

## 📡 Realtime (Reverb + Broadcasting)

### Componentes

- 🧠 Backend: `laravel/reverb` + broadcasting driver `reverb`
- 🖥️ Frontend: `resources/js/bootstrap.js` inicializa Echo con `VITE_REVERB_*`. Si `VITE_REVERB_HOST` es `127.0.0.1` o `localhost`, se usa `window.location.hostname` para que el WebSocket apunte al mismo host con el que se carga la app (útil en LAN con IP).
- 🔐 Auth de canales: endpoint `/broadcasting/auth` (rutas de broadcasting)
- 🎚️ Interruptor global: `broadcasting.enabled` / `BROADCAST_ENABLED` (en `DomainBroadcastEvent::broadcastWhen()`). Con `BROADCAST_ENABLED=false` no se intenta publicar por socket (evita `BroadcastException` si Reverb no está disponible). Con `BROADCAST_CONNECTION=log` el driver escribe en log en lugar de Reverb.

### Canales (definidos en `routes/channels.php`)

- `dashboard.updates`
- `weapons.updates`
- `assignments.updates`
- `clients.updates`
- `transfers.updates`
- `alerts.updates`
- `incidents.updates`
- `import-batches.updates`
- `users.updates`
- `workers.updates`
- `maps.updates`
- `posts.updates`

> Nota: la autorización de estos canales está configurada como `true` (permitir suscripción) y está lista para endurecerse por rol/alcance.

---

## 🧾 Variables de entorno (tabla basada en `.env.example`)

### 🌍 App

| Variable | Ejemplo | Descripción |
|---|---|---|
| `APP_NAME` | `Laravel` | Nombre de la app |
| `APP_ENV` | `local` | Entorno |
| `APP_KEY` | `base64:...` | Clave app |
| `APP_DEBUG` | `true` | Debug |
| `APP_URL` | `http://localhost` | URL base (si se accede por IP, usar la IP) |

### 🗄️ Base de datos

| Variable | Ejemplo | Descripción |
|---|---|---|
| `DB_CONNECTION` | `mysql` | Driver |
| `DB_HOST` | `127.0.0.1` | Host |
| `DB_PORT` | `3306` | Puerto |
| `DB_DATABASE` | `laravel` | BD |
| `DB_USERNAME` | `root` | Usuario |
| `DB_PASSWORD` | `` | Password |

### 🔊 Broadcasting / Reverb

| Variable | Ejemplo | Descripción |
|---|---|---|
| `BROADCAST_CONNECTION` | `reverb` | Conexión de broadcasting por defecto (`reverb`, `log`, `null`, …) |
| `BROADCAST_ENABLED` | `true` | Si es `false`, los eventos que extienden `DomainBroadcastEvent` no se publican (no llama a Reverb/Pusher) |
| `REVERB_APP_ID` | `armory` | App ID (Reverb) |
| `REVERB_APP_KEY` | `armory-key` | App Key |
| `REVERB_APP_SECRET` | `armory-secret` | App Secret |
| `REVERB_HOST` | `172.16.x.x` | Host/IP que el **navegador** usa para el WS (alinear con `APP_URL` si entras por IP) |
| `REVERB_PORT` | `6001` | Puerto cliente (WS); en Windows a veces `8080` no es usable |
| `REVERB_SCHEME` | `http` | `http` / `https` |
| `REVERB_SERVER_HOST` | `0.0.0.0` | Bind del proceso `reverb:start` |
| `REVERB_SERVER_PORT` | `6001` | Puerto donde escucha Reverb (igual que `REVERB_PORT` salvo proxies) |

### ⚡ Vite (variables expuestas al frontend)

| Variable | Ejemplo | Descripción |
|---|---|---|
| `VITE_APP_NAME` | `${APP_NAME}` | Nombre app en frontend |
| `VITE_REVERB_APP_KEY` | `${REVERB_APP_KEY}` | Key para Echo |
| `VITE_REVERB_HOST` | `${REVERB_HOST}` | Host WS para Echo |
| `VITE_REVERB_PORT` | `${REVERB_PORT}` | Puerto WS |
| `VITE_REVERB_SCHEME` | `${REVERB_SCHEME}` | Esquema WS |

### 🧠 Sesión / Sanctum

| Variable | Ejemplo | Descripción |
|---|---|---|
| `SESSION_DOMAIN` | *(vacío)* | Dominio de cookie si aplica |
| `SANCTUM_STATEFUL_DOMAINS` | *(vacío)* | Hosts/ips stateful (si se usa por IP/red, incluirlos) |

---

## 🧾 Módulos, paquetes y facturación (estado real del codebase)

Tras escanear `app/`, `database/` y `resources/views/` **no se encontró** implementación de:
- multiempresa (“company admin” / tenant),
- paquetes/planes,
- facturación/subscripciones/invoices.

Lo que **sí existe** hoy es control de acceso por:
- roles (`ADMIN`, `RESPONSABLE`, `AUDITOR`),
- nivel de responsabilidad,
- policies por módulo (weapons/clients/posts/workers/incidents),
- cartera de clientes (`user_clients`) para restringir alcance.

---

## 🧪 Tests y calidad

```bash
php artisan test
```

Formato de código:

```bash
./vendor/bin/pint
```

---

## 🧯 Troubleshooting rápido

- 🧩 **No conecta Reverb** / `BroadcastException` (p. ej. HTML en el mensaje):
  - que **`php artisan reverb:start`** (o `npm run reverb`) esté corriendo sin cortar la salida con pipes
  - `REVERB_HOST` / `APP_URL` / cómo abres el sitio en el navegador (misma IP o hostname)
  - puerto **`REVERB_SERVER_PORT`** abierto en firewall (p. ej. `6001`)
  - mientras depuras backend sin socket: `BROADCAST_ENABLED=false` o `BROADCAST_CONNECTION=log`
- 🧱 **Cambiaste `VITE_*` y no se refleja**: ejecuta `npm run build` o `npm run dev`.

Tipos de arma permitidos en validacion actual:

- `Escopeta`
- `Pistola`
- `Revolver`
- `Subametralladora`

Tipos de propiedad:

- `company_owned`
- `leased`
- `third_party`

### 5.1.1 Centro de cargas masivas

Controlador: `app/Http/Controllers/WeaponImportController.php`  
Servicios: `app/Services/WeaponImportService.php`, `app/Services/WeaponImportSpreadsheetReader.php`

El modulo se expone hoy como **Subir armas**, pero conceptualmente funciona como un centro de cargas masivas con dos vistas principales:

- indice de lotes ejecutados;
- detalle del lote con previsualizacion, ejecucion y cancelacion.

El flujo operativo implementado actualmente corresponde a armas. El esquema de datos ya reserva soporte para otros tipos de lote mediante `weapon_import_batches.type` y relaciona filas con `client_id` cuando aplique.

Flujo actual de armas:

- Modulo exclusivo para `ADMIN`.
- Permite cargar archivos `.xlsx`, `.csv` y `.txt`.
- El usuario sube el archivo desde modal:
  - arrastrar,
  - pegar desde portapapeles,
  - seleccionar desde el equipo.
- El sistema crea un lote en estado `draft` y muestra vista previa antes de aplicar cambios.
- Si el usuario cancela la carga antes de ejecutar:
  - el lote pendiente se elimina,
  - el archivo importado se elimina,
  - la pantalla vuelve a estado limpio para iniciar una nueva carga.
- Cada fila se clasifica en:
  - `create`
  - `update`
  - `no_change`
  - `error`
- Si existe al menos una fila con error, el lote no puede ejecutarse.
- Si no hay errores, el usuario puede ejecutar el lote y aplicar cambios sobre `weapons`.
- Durante la subida del archivo, la interfaz muestra progreso de carga.
- Durante la ejecucion del lote, la interfaz muestra:
  - porcentaje,
  - filas procesadas,
  - correctas,
  - fallidas,
  - estado del lote,
  - estimado de tiempo restante.
- El modulo conserva historial de lotes ejecutados y solo mantiene un borrador activo durante la revision.
- La vista principal del modulo se mantiene limpia mientras el lote siga pendiente:
  - la validacion detallada se revisa solo en el modal,
  - el resultado detallado solo aparece en el `index` despues de ejecutar.

Estados operativos del lote:

- `draft`
- `processing`
- `executed`
- `failed`

Reglas de negocio:

- La llave principal de comparacion es `serial_number`.
- Si la serie no existe:
  - crea el arma.
- Si la serie existe y hay cambios:
  - actualiza solo los campos importables.
- Si la serie existe y no hay diferencias:
  - marca la fila como `no_change`.
- Si faltan columnas o hay datos invalidos:
  - marca la fila como `error`.

Columnas soportadas:

- `TIPO DE ARMA`
- `MARCA ARMA`
- `No. SERIE`
- `CALIBRE`
- `CAPACIDAD`
- `TIPO PERMISO`
- `No. PERMISO`
- `FECHA VENCIMIENTO SALVOCONDUCTO`

Normalizaciones incluidas:

- Tipos de arma:
  - `ESCOPETA` -> `Escopeta`
  - `PISTOLA` -> `Pistola`
  - `Revolver` -> `Revolver`
  - `SUBAMETRALLADORA` -> `Subametralladora`
  - `UZI` -> `Subametralladora`
- Tipos de permiso:
  - `PORTE` -> `porte`
  - `TENENCIA` -> `tenencia`
- Fechas:
  - soporta fechas comunes de Excel (`d/m/Y`, `Y-m-d`, serial numerico Excel).

Campos que actualmente se crean o actualizan por importacion:

- `weapon_type`
- `brand`
- `serial_number`
- `caliber`
- `capacity`
- `permit_type`
- `permit_number`
- `permit_expires_at`

Campos excluidos por ahora:

- foto del permiso
- cantidad de municion
- cantidad de proveedor

Notas de ampliacion:

- `WeaponImportBatch::TYPE_WEAPON` y `WeaponImportBatch::TYPE_CLIENT` definen la tipologia del lote.
- `weapon_import_rows.client_id` queda disponible para asociar filas a clientes cuando el flujo correspondiente exista.
- La UI y las rutas vigentes siguen centradas en `weapon-imports.*`.

### 5.2 Asignacion a cliente (destino operativo)

Controlador: `app/Http/Controllers/WeaponClientAssignmentController.php`  
Servicio: `app/Services/WeaponAssignmentService.php`

Reglas:

- Solo una asignacion activa por arma.
- Si existe activa, se cierra (`end_at`, `is_active = null`) y se crea nueva.
- Si cambia cliente, limpia asignaciones internas activas (puesto/trabajador).
- Para RESPONSABLE no admin:
  - Solo clientes de su cartera.
- Para ADMIN:
  - Se selecciona responsable del cliente destino.

### 5.3 Asignacion interna (puesto o trabajador)

Controlador: `app/Http/Controllers/WeaponInternalAssignmentController.php`

Reglas:

- Requiere destino operativo activo (cliente asignado).
- Solo se puede asignar uno de dos:
  - `post_id` o `worker_id`.
- Solo una asignacion interna activa por arma:
  - puesto activo o trabajador activo.
- Si ya existe activa y no se marca reemplazo, muestra advertencia.
- Para RESPONSABLE:
  - Debe ser nivel 1 y responsable activo del arma.
  - Debe pertenecer a su cartera.
  - Si es trabajador, debe estar a su cargo.
- Permite retiro manual de asignacion interna activa.

### 5.4 Transferencias

Controlador: `app/Http/Controllers/WeaponTransferController.php`

Estados:

- `pending`
- `accepted`
- `rejected`
- `cancelled` (constante disponible; flujo actual usa pending/accepted/rejected)

Flujo:

- Solicitud masiva (`bulkStore`) de una o varias armas.
- Al solicitar:
  - Cierra asignaciones internas activas.
  - Retira asignacion de cliente activa.
  - Crea registro de transferencia pendiente.
- Aceptacion:
  - Valida cartera del destinatario.
  - Asigna nuevo cliente responsable.
  - Opcionalmente asigna puesto o trabajador.
- Rechazo:
  - Marca estado rechazado.

### 5.5 Clientes

Controlador: `app/Http/Controllers/ClientController.php`

- CRUD.
- RESPONSABLE ve solo clientes de su cartera.
- Geocodificacion automatica por direccion/ciudad/departamento.
- Opcion de coordenadas manuales desde mapa (`coords_source = map`).
- Si la ubicacion se selecciona en mapa:
  - completa latitud y longitud,
  - intenta completar direccion, barrio, municipio y departamento.
- Si luego el usuario corrige solo la direccion o barrio:
  - conserva la ubicacion tomada del mapa.
- Si el usuario diligencia manualmente direccion, barrio, municipio y departamento:
  - el sistema intenta calcular latitud y longitud antes de guardar.
- No permite borrar cliente con armas activas asignadas.

### 5.6 Puestos

Controlador: `app/Http/Controllers/PostController.php`

- CRUD operativo con **archivo** en lugar de borrado físico (`archived_at`), y **reactivación**.
- **Historial** (`post_histories`): entrada en el alta; en cada edición, **nota de cambio obligatoria** que se registra en el historial (además del campo notas del puesto).
- UI: listado con filtro de estado (activos/archivados); acción **Historial** (modal).
- `index` para ADMIN/RESPONSABLE/AUDITOR según política.
- Crear/editar/archivar: según `PostPolicy` (admin y responsable nivel 1 en ámbito de cartera donde aplique).
- Geocodificacion equivalente a clientes.
- Si la ubicacion se selecciona en mapa:
  - completa latitud y longitud,
  - conserva la ubicacion si luego solo se corrige la direccion.
- Si el usuario diligencia manualmente direccion, municipio y departamento:
  - el sistema intenta calcular latitud y longitud antes de guardar.
- Filtros por cliente y texto.

### 5.7 Trabajadores

Controlador: `app/Http/Controllers/WorkerController.php`

- CRUD con **archivo** / **reactivación** (no eliminación física); al archivar se cierran asignaciones internas activas arma–trabajador.
- **Historial** (`worker_histories`): misma regla que puestos (registro inicial + nota obligatoria en cada edición).
- **Responsable nivel 1 (no admin)**: puede gestionar trabajadores solo para **clientes de su cartera**; al crear/editar el **responsable queda fijo en su usuario**. El filtro **Responsable** en el listado se oculta para ese rol.
- Listado: filtros en una sola fila horizontal; evento `WorkerChanged` en canal `workers.updates` cuando broadcasting está activo.
- Roles de trabajador: ver `WorkerController::roleOptions()` (p. ej. escolta, supervisor).

### 5.8 Documentos de arma

Controlador: `app/Http/Controllers/WeaponDocumentController.php`

- Carga de archivo al disco `local`.
- Registro en `files` y `weapon_documents`.
- Descarga por ruta protegida.
- Si es documento de renovacion, se regenera al descargar.
- Eliminacion de documento + archivo fisico.

Observaciones permitidas en carga documental:

- `En Armerillo`
- `En Mantenimiento`
- `Para Mantenimiento`
- `Hurtada`
- `Perdida`
- `Dar de Baja`

Estados permitidos:

- `Sin novedad`
- `En proceso`

### 5.9 Fotos de arma

Controlador: `app/Http/Controllers/WeaponPhotoController.php`

- Carga y reemplazo por tipo de foto.
- Al actualizar o borrar, elimina archivo anterior.
- Sincroniza renovacion despues de cambios.

Descripciones tecnicas soportadas (`WeaponPhoto::DESCRIPTIONS`):

- `lado_derecho`
- `lado_izquierdo`
- `canon_disparador_marca`
- `serie`
- `aseo` (impronta)

### 5.10 Mapa operativo

Controlador: `app/Http/Controllers/MapController.php`  
Frontend: `resources/js/map.js`

- Vista `/mapa` para ADMIN/RESPONSABLE/AUDITOR.
- Endpoint JSON `/mapa/armas`.
- Coordenadas priorizadas por:
  1. Puesto activo.
  2. Cliente del trabajador activo.
  3. Cliente activo.
- Cluster con contador.
- Buscador por serie/cliente.
- Icono personalizado:
  - `public/images/map/Icono_Ubicacion.png`

### 5.11 Geocoding y reverse geocoding

Servicio: `app/Services/GeocodingService.php`  
Controller: `app/Http/Controllers/GeocodingController.php`

- Geocoding directo (Nominatim search).
- Reverse geocoding (Nominatim reverse).
- Timeouts cortos y fallback a `null` en error.
- Endpoint de geocoding directo para formularios:
  - `GET /geocode/search`
- En formularios de clientes y puestos:
  - muestra aviso corto si la direccion no es reconocida,
  - permite guardar sin coordenadas o elegir la ubicacion en el mapa.

### 5.12 Carteras de responsables

Controlador: `app/Http/Controllers/ResponsiblePortfolioController.php`

- ADMIN asigna clientes a responsables (`user_clients`).
- Impide quitar cliente si hay armas activas con ese responsable.
- Transferencia de cartera entre responsables.
- Al transferir cartera:
  - mueve pivote cartera.
  - actualiza `responsible_user_id` en asignaciones activas.

### 5.13 Reportes y alertas

Controlador: `app/Http/Controllers/ReportController.php`  
Controlador: `app/Http/Controllers/AlertsController.php`

Reportes:

- Armas por cliente.
- Armas sin destino.
- Historial por arma (asignaciones + documentos).
- Auditoria filtrable por rango (30/90 dias) y modulo.

Alertas:

- Vista general por tarjetas:
  - Documentos vencidos
  - Documentos por vencer
  - Armas sin alertas
- Filtro por mes calendario opcional:
  - si no se selecciona mes, muestra todo el sistema
  - si se selecciona un mes, muestra solo vencimientos de ese mes
- Cada tarjeta abre un modal con detalle seleccionable.
- El detalle permite:
  - buscar en todas las columnas,
  - seleccionar armas individuales,
  - seleccionar todo lo visible,
  - ver la relacion consolidada en PDF antes de descargar,
  - descargar la relacion filtrada en `.docx`.
- Cada modal (vencidos, por vencer y sin alertas) incluye:
  - un contador dinamico `N armas en la lista` que reacciona a la busqueda y a los filtros,
  - un toggle `Excluir armas con novedad` que oculta las armas con incidentes bloqueantes activos y las retira automaticamente de la seleccion, vista previa y descarga.
- La ventana de alerta preventiva opera sobre 120 dias.

### 5.14 Dashboard operativo

Controlador: `app/Http/Controllers/DashboardController.php`  
Servicio: `app/Services/DashboardMetricsService.php`

El dashboard principal ya no es una pantalla de accesos rapidos. Ahora muestra informacion real del sistema:

- KPIs de inventario:
  - total de armas
  - con destino activo
  - sin destino
  - documentos vencidos
  - por vencer
  - transferencias pendientes
- Metricas auxiliares:
  - clientes
  - puestos
  - trabajadores
- Graficos operativos:
  - armas por responsable
  - estado documental
  - renovaciones por mes
  - incidencias activas
  - estados del flujo de transferencias
  - distribucion interna

Comportamiento relevante:

- La cabecera muestra fecha y hora en tiempo real.
- El dashboard se refresca automaticamente sin recargar la pagina.
- Eventos de dominio (armas, asignaciones, transferencias, documentos, novedades) se emiten via Laravel Reverb y el frontend escucha con Laravel Echo para sincronizar vistas sin recargar.
- El grafico `Renovaciones por mes`:
  - muestra solo meses con datos,
  - filtra por anio,
  - por defecto usa el anio actual si existe en los documentos,
  - y solo ofrece en el selector los anios que realmente tienen documentos.
- El alcance de los datos respeta el rol del usuario:
  - `ADMIN` y `AUDITOR` ven alcance global.
  - `RESPONSABLE` ve solo su operacion.

## 6. Auditoria

Tabla: `audit_logs`

Se registran, entre otros:

- Login / logout.
- Password update / reset request / reset completed.
- Profile update / delete.
- CRUD de clientes, puestos, trabajadores, usuarios, armas.
- Archivo / reactivación de **puestos** y **trabajadores** (acciones de auditoría asociadas).
- Alta de usuario con contraseña temporal y marcas `must_change_password` (redirección a cambio obligatorio).
- Cambio de estado de usuario.
- Carga/actualizacion de fotos.
- Carga de documentos.
- Asignaciones cliente e internas.
- Cierres de asignaciones por transferencia/cambio cliente.
- Solicitud, aceptacion y rechazo de transferencias.
- Cambios de cartera.
- Cargas masivas de armas (`weapon_import_created`, `weapon_import_updated`).

## 7. Modelo de datos (tablas)

### Catalogos y seguridad

- `users` (incluye `must_change_password` y flujo de cambio forzado de contraseña)
- `positions`
- `responsibility_levels`
- `user_clients` (pivot cartera)
- `password_reset_tokens`
- `personal_access_tokens`
- `failed_jobs`

### Nucleo operativo

- `clients`
- `posts` (incluye `archived_at`)
- `post_histories`
- `workers` (incluye `archived_at`)
- `worker_histories`
- `weapons`
- `weapon_client_assignments`
- `weapon_post_assignments`
- `weapon_worker_assignments`
- `weapon_transfers`

### Archivos y trazabilidad

- `files`
- `weapon_photos`
- `weapon_documents`
- `weapon_import_batches`
- `weapon_import_rows`
- `audit_logs`

### Restricciones importantes

- Unicidad de `users.email`.
- Unicidad de `weapons.internal_code` y `weapons.serial_number`.
- Unicidad de `user_clients (user_id, client_id)`.
- Unicidad de foto por tipo en arma: `weapon_photos (weapon_id, description)`.
- `weapon_import_batches.type` clasifica el lote (`weapon`, `client`).
- `weapon_import_rows.client_id` referencia opcional a `clients`.
- Indexado por lote/accion y lote/fila en `weapon_import_rows`.
- Unicidad de activa por arma en asignaciones:
  - `weapon_client_assignments (weapon_id, is_active)`
  - `weapon_post_assignments (weapon_id, is_active)`
  - `weapon_worker_assignments (weapon_id, is_active)`

## 8. Rutas principales (web)

Las rutas estan en `routes/web.php` y `routes/auth.php`.

Grupos funcionales:

- Auth:
  - `login`, `forgot-password`, `reset-password`, `verify-email`, `logout`.
  - `register` solo si `AUTH_ALLOW_PUBLIC_REGISTRATION=true`.
- Perfil:
  - `profile.edit/update/destroy`.
- Administracion:
  - `users.*`, `users.status`.
- Operacion:
  - `weapons.*`
  - `weapon-imports.index`, `weapon-imports.preview`, `weapon-imports.start`, `weapon-imports.process`, `weapon-imports.status`, `weapon-imports.execute`, `weapon-imports.discard` (centro de cargas masivas de armas)
  - `weapons.client_assignments.store`
  - `weapons.internal_assignments.store/retire`
  - `weapons.photos.*`
  - `weapons.documents.*`
  - `weapons.permit`, `weapons.permit.update`
  - `weapons.imprints.toggle`
- Maestros:
  - `clients.*`, `posts.*`, `workers.*`.
- Transferencias:
  - `transfers.index`, `transfers.bulk`, `transfers.accept`, `transfers.reject`.
- Cartera:
  - `portfolios.index/edit/update/transfer`.
- Reportes y alertas:
  - `reports.*`, `alerts.documents`, `alerts.documents.preview`, `alerts.documents.download`.
- Dashboard:
  - `dashboard`, `dashboard.metrics`.
- Mapa:
  - `maps.index`, `maps.weapons`.
- Locale:
  - `locale.switch`.

## 9. Frontend y UX

Entradas Vite (`vite.config.js`):

- `resources/css/app.css`
- `resources/js/app.js`
- `resources/js/map.js`
- `resources/js/location-picker.js`

Caracteristicas:

- Navegacion responsive por rol en `resources/views/layouts/navigation.blade.php`.
- Idioma con cambio de session (`es`, `en`).
- Modales de seleccion de ubicacion con mapa y buscador textual.
- Cluster de mapa con icono personalizado y contador.
- Tailwind escanea vistas, JS y helpers PHP usados para clases dinamicas:
  - `./app/**/*.php`
  - `./resources/views/**/*.blade.php`
  - `./resources/js/**/*.js`
- Se usa `safelist` para clases dinamicas de estados documentales, de modo que los colores de alertas no se pierdan en el build.

## 10. Archivos y almacenamiento

Discos Laravel (`config/filesystems.php`):

- `local` (privado): `storage/app`
- `public` (publico): `storage/app/public` via `public/storage`

Rutas usadas por el dominio:

- Fotos arma: `storage/app/public/weapons/{weapon_id}/photos`
- Permiso arma: `storage/app/weapons/{weapon_id}/permits`
- Documentos arma: `storage/app/weapons/{weapon_id}/documents`
- Renovacion autogenerada: `storage/app/weapons/{weapon_id}/documents/renovacion_{internal_code}.docx`
- Archivos de importacion: `storage/app/weapon-imports`
- Plantilla de renovacion: `resources/templates/PLANTILLA_REVALIDACION.docx`
- Icono de mapa: `public/images/map/Icono_Ubicacion.png`

## 11. Internacionalizacion

- Locale por defecto: `es` (`config/app.php`).
- Fallback: `es`.
- Middleware de locale: `App\Http\Middleware\SetLocale`.
- Archivos:
  - `resources/lang/es/*`
  - `resources/lang/en.json`

## 12. Instalacion local

1. `composer install`
2. `npm install`
3. Copiar `.env.example` a `.env`
4. Configurar base de datos en `.env`
5. `php artisan key:generate`
6. Definir `SEED_ADMIN_PASSWORD` en `.env`
7. `php artisan migrate --seed`
8. Si vas a usar geocodificacion, definir `NOMINATIM_USER_AGENT`
9. `php artisan storage:link`
10. `npm run build` (o `npm run dev`)
11. `php artisan serve`
12. Levantar el servidor de broadcasting en paralelo (ver `12.3 Broadcasting en tiempo real`)

### 12.1 Acceso por red local con Laragon/Apache

Para acceder al sistema desde otros equipos de la misma red local:

- Apache debe escuchar en `*:80`.
- El `VirtualHost` debe apuntar a `public/` y aceptar el hostname o IP del equipo.
- El firewall de Windows debe permitir entrada TCP al puerto `80`.

Ejemplo de `VirtualHost`:

```apache
<VirtualHost *:80>
    ServerName NOMBRE-EQUIPO
    ServerAlias sj_armory.test
    ServerAlias *.sj_armory.test
    ServerAlias 172.16.23.36

    DocumentRoot "C:/laragon/www/SJ_Armory/public"

    <Directory "C:/laragon/www/SJ_Armory/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Notas:

- Si se accede por IP, por ejemplo `http://172.16.23.36`, no hace falta un dominio.
- Si se accede por un dominio local como `sj_armory.test`, cada equipo cliente debe resolver ese nombre via `hosts` o DNS interno.
- Para abrir el puerto `80` solo a la red local en Windows:

```cmd
netsh advfirewall firewall add rule name="Laragon Apache HTTP 80 (LocalSubnet)" dir=in action=allow protocol=TCP localport=80 program="C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\httpd.exe" remoteip=LocalSubnet profile=any
```

### 12.2 Configuracion detectada actualmente en este entorno

`.env` detectado:

- `APP_NAME="SJ_ARMORY"`
- `APP_ENV=local`
- `APP_URL=http://SJPCANAOPE1`
- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=sj_armory`
- `DB_USERNAME=root`
- `FILESYSTEM_DISK=local`
- `SESSION_DRIVER=file`
- `SESSION_DOMAIN=` vacio
- `SANCTUM_STATEFUL_DOMAINS=sj_armory.test,sj_armory.test:80,SJPCANAOPE1,SJPCANAOPE1:80,172.16.23.36,172.16.23.36:80`

VirtualHost detectado para este proyecto en Laragon:

```apache
<VirtualHost *:80>
    ServerName SJPCANAOPE1
    ServerAlias sj_armory.test
    ServerAlias *.sj_armory.test
    ServerAlias 172.16.23.36

    DocumentRoot "C:/laragon/www/SJ_Armory/public"

    <Directory "C:/laragon/www/SJ_Armory/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Observacion operativa:

- La configuracion detectada para `SJ_Armory` expone el proyecto por:
  - `http://SJPCANAOPE1`
  - `http://sj_armory.test`
  - `http://172.16.23.36`
- Si tambien quieres servirlo por otra IP o hostname, debes agregar ese valor tanto en el `VirtualHost` como en `SANCTUM_STATEFUL_DOMAINS`.

### 12.3 Broadcasting en tiempo real (Laravel Reverb)

El sistema usa Laravel Reverb como servidor WebSocket. Reverb debe correr **en paralelo** al servidor web. Si broadcasting está activo (`BROADCAST_ENABLED=true` y `BROADCAST_CONNECTION=reverb`) y Reverb no está levantado o es inalcanzable, las acciones que disparan eventos `ShouldBroadcast` pueden fallar con `BroadcastException`.

Arranque (mantener la terminal abierta):

```bash
php artisan reverb:start
```

Equivalente en este repo:

```bash
npm run reverb
```

```bash
composer reverb
```

Notas operativas:

- **`REVERB_HOST`** debe ser el host o IP con el que los navegadores alcanzan el servidor (p. ej. la misma IP que `APP_URL` si entras por LAN). El frontend resuelve bien el host cuando `VITE_REVERB_HOST` no fuerza `127.0.0.1` en clientes remotos (ver `resources/js/bootstrap.js`).
- **Puerto**: en Windows el **8080** a veces está reservado; el proyecto documenta **6001** en `.env.example`. Permite el puerto en el firewall para clientes en red.
- **Sin tiempo real temporalmente**: `BROADCAST_ENABLED=false` o `BROADCAST_CONNECTION=log`, luego `php artisan config:clear`.

Ejemplo explícito de bind (opcional):

```bash
php artisan reverb:start --host=0.0.0.0 --port=6001
```

## 13. Variables de entorno relevantes

Base:

- `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `FILESYSTEM_DISK`
- `SESSION_DRIVER`, `SESSION_LIFETIME`
- `SESSION_DOMAIN`
- `CACHE_DRIVER`
- `QUEUE_CONNECTION`
- `MAIL_*`
- `AWS_*` (si se usa S3)

Operacion:

- `AUTH_ALLOW_PUBLIC_REGISTRATION`
  - `false` por defecto
  - si esta en `false`, no se exponen rutas publicas de registro
- `SEED_ADMIN_PASSWORD`
  - obligatoria para ejecutar `AdminUserSeeder`
- `NOMINATIM_USER_AGENT`
  - identificacion usada por `GeocodingService` para Nominatim
- `APP_TIMEZONE`
  - zona horaria operativa del sistema
  - configurada actualmente para `America/Bogota`
- `SANCTUM_STATEFUL_DOMAINS`
  - lista de hosts/IP autorizados para cookies de sesion y autenticacion stateful
  - debe incluir hostname, alias local y/o IP real usada para acceder al sistema

Broadcasting (tiempo real, Laravel Reverb):

- `BROADCAST_CONNECTION=reverb` (u otro driver soportado)
- `BROADCAST_ENABLED` (`true`/`false`): si es `false`, los eventos basados en `DomainBroadcastEvent` no se publican por WebSocket
- `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`
  - credenciales del servidor Reverb usadas tanto por el cliente como por el servidor
- `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`
  - host y puerto visible para el cliente (el navegador se conecta aqui)
- `REVERB_SERVER_HOST`, `REVERB_SERVER_PORT`
  - bind real del proceso `reverb:start`
- `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME`
  - variables expuestas a Vite para configurar Laravel Echo en el frontend

Importante para entorno real:

- Ajustar `APP_URL` al dominio/IP real.
- Revisar `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE` si se publica por HTTPS.
- Si se necesita acceso flexible por IP, hostname o dominio, dejar `SESSION_DOMAIN` vacio para no fijar la cookie a un solo host.
- Si se usa Sanctum con SPA o frontends externos, agregar todos los hosts validos en `SANCTUM_STATEFUL_DOMAINS`.

## 14. Seeders y datos iniciales

`DatabaseSeeder` ejecuta:

- `PositionSeeder`
- `ResponsibilityLevelSeeder`
- `AdminUserSeeder`

`AdminUserSeeder` crea/actualiza dos usuarios ADMIN por email:

- `wilder.rivera@example.com`
- `andres.sanmiguel@example.com`

Requisito:

- exige `SEED_ADMIN_PASSWORD` antes de ejecutar `php artisan db:seed`

Se recomienda cambiar passwords y correos en produccion inmediatamente despues del primer despliegue.

## 15. Pruebas automatizadas

Suite actual en `tests/`:

- Unit basica.
- Feature de autenticacion y perfil (Breeze).
- Feature de `Subir armas`, incluyendo preview y progreso/ejecucion de lote.

Comando:

- `php artisan test`

Configuracion de testing:

- `phpunit.xml` fuerza `DB_CONNECTION=sqlite`
- `DB_DATABASE=:memory:`
- `.env.testing` define un entorno aislado para pruebas

Con esto, `php artisan test` no debe tocar la base real del proyecto.

Nota: actualmente no hay suite dedicada para reglas de negocio de armas/asignaciones/transferencias, aunque la logica ya esta implementada en controladores/servicios.

## 16. Operacion y despliegue (resumen)

Checklist minimo de produccion:

1. Configurar `.env` de produccion.
2. `composer install --no-dev --optimize-autoloader`
3. `npm ci && npm run build`
4. `php artisan migrate --force`
5. `php artisan storage:link`
6. `php artisan config:cache`
7. `php artisan route:cache`
8. `php artisan view:cache`
9. Configurar backups y rotacion de logs.

## 17. Estructura del proyecto (resumen)

- `app/Http/Controllers`: casos de uso web.
- `app/Services`: logica de negocio reusable.
- `app/Policies`: autorizacion por entidad.
- `app/Models`: modelo de dominio y relaciones.
- `database/migrations`: esquema.
- `database/seeders`: datos iniciales.
- `resources/views`: UI Blade.
- `resources/js`: frontend modular (app, mapa, location-picker).
- `resources/css`: estilos Tailwind.
- `public/images`: assets publicos (incluye icono de mapa).
