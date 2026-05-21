# 🛡️ SJ Armory

Sistema web para **gestión de armamento**, **asignaciones operativas**, **transferencias**, **documentación**, **trazabilidad** y **auditoría**, con foco en operación diaria (dashboard, mapa, alertas) y control de acceso por rol/nivel.

> ✅ Este `README.md` está generado a partir del análisis del codebase (Laravel 10, Reverb, policies, controllers y `.env.example`).

---

## 📌 Alcance funcional

- ✅ **Armas**: alta/edición, fotos (técnicas y permiso; en móvil **Tomar foto** o **Elegir de galería**; reverso autenticado según plantillas globales **porte** / **tenencia**), documentos (descarga del **permiso** como PDF frente + reverso), exportación, inventario; **historial cronológico de notas** en la ficha (asignaciones, novedades, documentos, transferencias, actualización de datos y fotos desde Revista armas).
- ✅ **Asignaciones**:
  - **Operativa** (arma ↔ cliente/responsable)
  - **Interna** (arma ↔ puesto y/o trabajador; ubicación en mapa prioriza puesto si existe; la columna de destino en el listado refleja principalmente al trabajador cuando hay trabajador activo)
- ✅ **Transferencias**: listado **unificado** (pendientes y enviadas en una tabla; serie en columna arma; munición/proveedores opcionales en el envío; aceptación; **cancelación** con restauración cuando aplica); con transferencia **pendiente**, la ficha del arma muestra un **aviso** (usuario normal: mensaje genérico; **ADMIN**: quién **envió** y quién **debe aceptar**); botón **Historial** (modal, últimas participaciones).
- ✅ **Clientes / Puestos / Trabajadores / Usuarios** (puestos y trabajadores: archivo, historial de cambios, políticas por rol)
- ✅ **Cargas masivas**: validación previa, preview, ejecución por chunks, trazabilidad por lote; en la vista **Subir armas**, el **ADMIN** gestiona las plantillas globales de reverso autenticado (porte y tenencia) usadas en el PDF y en la ficha.
- ✅ **Dashboard**: KPIs, métricas, gráficos y estado “as of”.
- ✅ **Alertas documentales** (`/alerts/documents`): tarjetas vencidos / por vencer / sin alertas; filtro **multi-mes** con panel de checkboxes (varios meses y años); exportación `.docx` y vista previa PDF con nombre `Revalidacion_{mes}_{año}`.
- ✅ **Revista armas** (`/revista-armas`): acceso temporal (12 h) para colaboradores de campo; usuarios temporales reutilizables; selección de armas visibles; subida de **4 fotos técnicas** a staging; revisión staff con filtro por usuario temporal (✓/✕ y **Ver**); confirmaciones en **modales** (no `confirm` del navegador); al **Actualizar** registra entrada en el historial de notas del arma; **ADMIN** con gestión global.
- ✅ **Mapa**: geocodificación y visualización operativa.
- ✅ **Auditoría**: registro de cambios y acciones críticas.
- ✅ **Realtime (Broadcasting)**: Laravel Reverb + Echo (WebSockets) para sincronización en tiempo real.
- ✅ **Notificaciones**: campana en barra superior con **solo no leídas**; menú de usuario con **Historial de notificaciones** (leídas y no leídas, mismo modal con `?history=1`); textos con actor y contexto (arma, cliente, puesto, etc.).
- ✅ **Reportes — Novedades operativas** (`/reports/weapon-incidents`): dashboard por año y tipo; botón **Lista** abre un modal con la tabla completa (mismo alcance que los filtros); **buscador** en el modal filtra por cualquier texto visible en la fila; columna **Arma** muestra solo el **número de serie** (enlace a la ficha del arma).

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

En **producción** (`APP_ENV=production`): `php artisan migrate --force`. Antes, **respaldo de la BD** y **`git pull`** en el servidor para que los archivos en `database/migrations/` coincidan con el repo; si migras con código desactualizado, una migración puede fallar o dejar el esquema incoherente.

**MySQL y `2026_05_08_120000_permit_authenticated_templates`:** esta migración elimina la FK de `weapons.permit_authenticated_file_id` usando el nombre real en `information_schema` y después borra la columna. Si ves `SQLSTATE[HY000]: 1828 Cannot drop column ... foreign key`, suele ser versión vieja del archivo de migración en el servidor o FK sin eliminar; actualiza el código, vuelve a ejecutar `migrate --force`, o en último caso elimina la FK manualmente en MySQL y repite la migración.

### 4) Instalar y compilar frontend

```bash
npm install
npm run build
```

**Dos flujos de compilación (Vite):**

| Comando | Config | Variables `VITE_*` | Salida |
|--------|--------|--------------------|--------|
| `npm run build` | `vite.config.js`, modo `localbuild` | `.env`, `.env.local`, opcional `.env.localbuild` (no lee `.env.production` en la raíz) | `public/build/` (desarrollo y LAN con Reverb) |
| `npm run build:deploy` | `vite.hosting.config.js` | **`build_hosting/.env.production`** (crear según sección final de `.env.example`) | **`build_hosting/build/`** |

En **hosting compartido** (p. ej. Pusher): compile en su PC con `npm run build:deploy` y suba **todo el contenido** de `build_hosting/build/` dentro de **`public/build/`** del servidor (no sobrescriba otros archivos del proyecto salvo el manifest y los assets de Vite).

> 🧩 Si `npm` no está en PATH, usa la terminal integrada de Laragon o exporta la ruta a Node antes de compilar.
>
> En Windows (Laragon), puedes invocar `npm` directo así:
>
> ```bash
> C:\laragon\bin\nodejs\node-v18\npm.cmd run build:deploy
> ```

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
| `app/Models` | Dominio / Eloquent (Weapon, Client, `Worker::roleLabels()` para cargos de trabajador, Assignments, Transfers, etc.) |
| `app/Policies` | Autorización por rol/alcance (`WeaponPolicy`, `ClientPolicy`, etc.) |
| `app/Services` | Lógica de negocio (métricas, importaciones, documentos, geocoding) |
| `app/Support` | Helpers de dominio (p. ej. comprobación de coordenadas para mapa) |
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
  - `update` (información del arma, edición general): solo ADMIN
  - `updatePhotos` (fotos técnicas + foto del permiso):
    - ADMIN: ✅
    - RESPONSABLE Nivel 1: ✅ solo si es responsable activo del arma
    - RESPONSABLE Nivel 2 / AUDITOR: ❌
  - `delete`: **siempre false** (borrado físico deshabilitado)

- `ClientPolicy`
  - `view` para RESPONSABLE: solo si el cliente está en su cartera (`user_clients`)
  - `update`: ADMIN, o RESPONSABLE **nivel 1** con el cliente en su cartera (misma regla que edición de puestos en cartera)
  - `create` / `delete`: solo ADMIN

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

> **Nota:** la autorización de estos canales está configurada como `true` (cualquier usuario autenticado puede suscribirse). Si los eventos broadcast llevan **datos operativos sensibles**, conviene **acotar por rol o alcance** en `routes/channels.php` y revisar los payloads en `app/Events`. En despliegues muy exigentes, trátelo como deuda de seguridad conocida.

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
| `SESSION_LIFETIME` | `30` | Minutos de **inactividad** (sin peticiones al servidor) antes de que la sesión deje de ser válida; cada petición renueva el temporizador. Ajustable en `.env`. |
| `SESSION_DRIVER` | `file` | Driver de almacenamiento de sesión (`file`, `database`, `redis`, …). |
| `SESSION_DOMAIN` | *(vacío)* | Dominio de cookie si aplica |
| `SANCTUM_STATEFUL_DOMAINS` | *(vacío)* | Hosts/ips stateful (si se usa por IP/red, incluirlos) |

### 🔐 Sesión expirada y rutas protegidas

- Si el usuario **no está autenticado** (incluye sesión caducada por inactividad según `SESSION_LIFETIME`) y accede a una ruta **web** protegida, Laravel redirige a **`/`** (vista **welcome**), no a `/login`.
- Peticiones que el framework interpreta como **JSON** (`Accept: application/json`, etc.): **401** sin redirección HTML.
- **Token CSRF inválido** (p. ej. formulario abierto tras caducar la sesión): redirección a **`/`** con mensaje flash; se guarda la URL previa para **`intended()`** después de iniciar sesión (`app/Exceptions/Handler.php`).

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

### Seguridad y operación (resumen de revisión interna)

Las rutas operativas viven bajo `auth`; imports y usuarios exigen **ADMIN** por middleware. Puntos a vigilar en evolución del proyecto:

- **Broadcasting (canales):** ver bloque **Realtime** arriba; suscripción permisiva + payload de eventos.
- **Geocodificación:** `GeocodingController` (`/geocode/search`, `/geocode/reverse`) exige sesión pero **no** restringe por rol ni rate limit; en producción puede usarse como proxy a Nominatim (abuso de ancho de banda / cuotas).

No sustituye una auditoría externa ni pentest; documenta decisiones conocidas del codebase.

---

## 🧯 Troubleshooting rápido

- 🧩 **No conecta Reverb** / `BroadcastException` (p. ej. HTML en el mensaje):
  - que **`php artisan reverb:start`** (o `npm run reverb`) esté corriendo sin cortar la salida con pipes
  - `REVERB_HOST` / `APP_URL` / cómo abres el sitio en el navegador (misma IP o hostname)
  - puerto **`REVERB_SERVER_PORT`** abierto en firewall (p. ej. `6001`)
  - mientras depuras backend sin socket: `BROADCAST_ENABLED=false` o `BROADCAST_CONNECTION=log`
- 🧱 **Cambiaste `VITE_*` y no se refleja**: ejecuta `npm run build` (local) o `npm run build:deploy` (artefacto para hosting) o `npm run dev`.
- ✉️ **No se envía correo** (p. ej. credenciales desde Usuarios):
  - El host `mailpit` en `.env` suele **no resolver** fuera de Docker; en Laragon/Windows use `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025` y deje `MAIL_USERNAME` / `MAIL_PASSWORD` vacíos si usa Mailpit **sin** autenticación.
  - Si el error indica **conexión rechazada a `127.0.0.1:1025`**, no hay ningún servicio SMTP escuchando: arranque [Mailpit](https://github.com/axllent/mailpit) (u otro capture SMTP en ese puerto) o cambie a SMTP real de su proveedor.
  - **Sin servidor local:** use `MAIL_MAILER=log` y revise `storage/logs/laravel.log` (el mensaje se registra, no sale a Internet).
  - Con `APP_DEBUG=true`, el aviso en pantalla puede incluir el detalle del fallo SMTP (útil en desarrollo; no dejar `APP_DEBUG=true` en producción con datos reales).
- 🗺️ **Mapa / selector de ubicación**: comparten capas **Satélite (híbrido)** (Esri: imagen + vías + límites) y **Calles (OpenStreetMap)**. Tras tocar `map.js` o `location-picker.js`, vuelva a compilar y refresque sin caché. El popup del mapa de armas limita la altura de la tabla (~5 filas visibles) con scroll para el resto. Si el **cursor parpadea o desaparece** al mover el ratón sobre el mapa (Chrome/Edge en Windows): la vista `maps/index` evita `overflow-hidden` en el card del mapa y `app.css` unifica el cursor (`grab` solo en el contenedor Leaflet, `inherit` en paneles/teselas); despliegue el CSS compilado actualizado en `public/build`.
- 🎨 **Parpadeo muy breve al cambiar de vista o al usar menús:** suele ser la **hidratación de Alpine.js** después de cargar el bundle de Vite (`resources/js/app.js`); existe `[x-cloak]` global en `resources/css/app.css` y los modales lo usan. Una red lenta o recarga completa de página amplía la ventana. No suele indicar error de Blade ni fuga de código al usuario final.

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

**Plantillas de reverso autenticado (permiso)**

- Solo **ADMIN**, desde el listado del centro de cargas (`weapon-imports.index`).
- Modal por tipo **porte** / **tenencia**: subida de imagen con recorte (cropper); se persisten en `permit_authenticated_templates` (una fila por tipo) y en `files`; disco `local`, carpeta `storage/app/permit-authenticated-templates/`.
- Ruta de actualización: `weapon-imports.permit-authenticated.update` (`POST /weapon-imports/permit-authenticated/{porte|tenencia}`).
- Las mismas plantillas alimentan la **descarga del permiso en PDF** (véase §5.8) y la tarjeta de reverso en la ficha del arma cuando el tipo de permiso coincide.

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

### 5.3 Asignacion interna (puesto y/o trabajador)

Controlador: `app/Http/Controllers/WeaponInternalAssignmentController.php`  
Utilidad: `app/Support/MapCoordinates.php` (comprueba latitud/longitud numericas antes de cerrar la asignacion)

Reglas:

- Requiere destino operativo activo (cliente asignado).
- Debe indicarse al menos uno: `post_id` y/o `worker_id`.
  - **Solo puesto** o **puesto + trabajador**: en el mapa la coordenada se toma del **puesto** (si hay puesto activo).
  - **Solo trabajador**: en el mapa la coordenada se toma del **cliente** del trabajador.
- Pueden coexistir **un** registro activo en `weapon_post_assignments` y **un** registro activo en `weapon_worker_assignments` para la misma arma (p. ej. trabajador como titular operativo y puesto para ubicacion).
- Antes de guardar, si la ubicacion que usaria el mapa **no esta definida**:
  - con puesto seleccionado: el **puesto** debe tener latitud y longitud; si no, se muestra un **modal** centrado con mensaje, boton **Asignar ubicacion** (edicion del puesto) y **Cancelar** (flash `internal_assignment_location_modal`).
  - solo trabajador: el **cliente** del trabajador debe tener coordenadas; si no, mismo modal apuntando a la edicion del **cliente** (clientes de carga masiva sin coordenadas quedan bloqueados hasta completar ubicacion).
- Si ya existe asignacion interna activa y no se marca reemplazo, muestra advertencia (confirmacion en UI + `replace`).
- Para RESPONSABLE:
  - Debe ser nivel 1 y responsable activo del arma.
  - Debe pertenecer a su cartera.
  - Si es trabajador, debe estar a su cargo.
- Permite retiro manual de asignacion interna activa (cierra puesto y trabajador activos).
- Auditoria: ademas de `internal_assigned_post` y `internal_assigned_worker`, existe `internal_assigned_worker_and_post` cuando se guardan ambos.

Listado de armas (`resources/views/weapons/partials/index_rows.blade.php`):

- Columna **Puesto o trabajador**: si hay trabajador activo, muestra el **nombre** del trabajador (tambien cuando hay puesto combinado); si solo hay puesto, el nombre del puesto.
- Columna **Cedula**: documento del trabajador activo, o `-` si no hay trabajador.
- **Exportación** (misma página `resources/views/weapons/index.blade.php`): modales **Exportar filtrado** y **Exportar selección** con preview y formato xlsx/csv; el modal usa **z-index** por encima de la barra fija `.sj-nav` y el diálogo en **flex columna** con scroll solo en la tabla previa, de modo que el **pie con botones** siga visible.

### 5.4 Transferencias

Controlador: `app/Http/Controllers/WeaponTransferController.php`

Estados:

- `pending`
- `accepted`
- `rejected`
- `cancelled` (cancelación por remitente, destinatario o admin; restaura destino previo si aplica)

Flujo:

- Solicitud masiva (`bulkStore`) de una o varias armas.
- Al solicitar (opcional por lote): **munición** y/o **proveedores** con cantidad; si no se marcan los interruptores, el envío es solo el arma. Los valores quedan en `weapon_transfers.ammo_count` / `provider_count` y, al **aceptar**, se aplican a la asignación interna nueva (puesto y/o trabajador) si existe.
- Al solicitar: **no** se retira el destino operativo ni la asignación interna; el arma **sigue asignada** al responsable y cliente actuales hasta que el destinatario **acepte** (o se cancele la transferencia).
- Mientras exista una transferencia **pendiente** para un arma, no se puede cambiar su destino operativo, ni su asignación interna, ni iniciar otra transferencia; el administrador ve en el mensaje **quién envió** y **quién debe aceptar**.
- **Listado e inventario** (`weapons.index`, exportaciones): la columna **Cliente** y el **Responsable** usan la asignación activa si existe; si no (p. ej. datos previos a no retirar al solicitar), se muestran **`from_client_id` / `from_user_id`** de la transferencia **pendiente** para no dejar el arma como “Sin destino” en pantalla.
- Aceptacion:
  - El usuario que acepta solo puede asignar **clientes de su cartera** (y el sistema valida en backend que el `client_id` pertenezca a la cartera del destinatario).
  - En el modal, **Puestos** y **Trabajadores** solo se muestran luego de seleccionar cliente y se filtran por ese cliente.
  - Para RESPONSABLE: los **trabajadores** visibles/seleccionables son solo los que tiene a cargo.
  - Si hay error de validacion/alcance, no se muestra pantalla de excepcion: se redirige a `transfers.index` con una alerta y opciones para reintentar la seleccion o cancelar.
  - Asigna nuevo cliente responsable.
  - Opcionalmente asigna **puesto y/o trabajador** (puede elegir ambos; el mapa prioriza el puesto cuando hay puesto). La validacion de coordenadas del puesto o del cliente (solo trabajador) es la misma que en la asignacion interna desde el detalle del arma.
- **Cancelación** (`transfers.cancel`): remitente, destinatario o administrador pueden cancelar una pendiente; **no** altera el destino si la asignación sigue activa (flujo actual). Si la transferencia es antigua y el arma quedó sin cliente (migración de comportamiento previo), se intenta **restaurar** desde `from_client_id` / `from_user_id`. La confirmación en pantalla es un **modal** (`resources/views/transfers/index.blade.php`, `cancel-transfer`), no el cuadro nativo del navegador; en la tabla, **Aceptar** y **Cancelar** usan estilos tipo botón (contraste alto) para lectura clara.
- Rechazo: la ruta `transfers.reject` fue sustituida por cancelación unificada (`cancelled`); registros antiguos pueden seguir en estado `rejected`.

### 5.5 Clientes

Controlador: `app/Http/Controllers/ClientController.php`

- Listado y detalle: RESPONSABLE ve solo clientes de su cartera.
- Alta y borrado: solo ADMIN (`ClientPolicy`).
- Edicion: ADMIN o RESPONSABLE **nivel 1** para clientes que estan en su cartera (politica `ClientPolicy::update`).
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
- Roles de trabajador (campo `workers.role`, validado en alta/edición): `ESCOLTA`, `SUPERVISOR`, `GUARDA`, `MOTORIZADO`, `GUARDA_INFRAESTRUCTURA`. Etiquetas en español para formularios y listados: `Worker::roleLabels()` (constant `ROLE_*` en el modelo).

### 5.8 Documentos de arma

Controlador: `app/Http/Controllers/WeaponDocumentController.php`

- Carga de archivo al disco `local`.
- Registro en `files` y `weapon_documents`.
- Descarga por ruta protegida.
- Si es documento de renovacion, se regenera al descargar.
- Eliminacion de documento + archivo fisico.
- **Visibilidad y descarga del documento de Revalidación (`is_renewal = true`)**: solo ADMIN.
  - La fila de **Revalidación** se oculta de la tabla de documentos para responsables y auditores.
  - El endpoint `weapons.documents.download` valida `is_renewal` y aborta con `403` si el usuario no es ADMIN, aun cuando intente entrar por la URL directa.
  - Los responsables y auditores siguen viendo y descargando el documento de **Permiso** y los demás documentos manuales.

**Descarga del permiso como PDF**

- Si el documento está marcado como permiso (`is_permit`), la descarga genera un **PDF** (`WeaponDocumentService::buildPermitPdf`, vista Blade `resources/views/weapons/permit-pdf.blade.php`, Dompdf) en lugar de devolver el archivo subido tal cual.
- Debe existir **foto de permiso** del arma (`permit_file_id`), tipo de permiso del arma **porte** o **tenencia**, y la **plantilla de reverso** correspondiente en `permit_authenticated_templates`; si falta el reverso cargado, la descarga responde con error controlado.
- Contenido: **frente** (imagen del permiso del arma) + **reverso** (plantilla global del mismo tipo). Medidas por cara: **8,56 cm × 5,4 cm**, una hoja carta vertical.
- Nombre del archivo entregado: `Permiso_Porte_<serie>.pdf` o `Permiso_Tenencia_<serie>.pdf` (serie sanitizada; si no hay serie, `sin-serie`).

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
- **Autorización por `WeaponPolicy::updatePhotos`**: ADMIN siempre puede; RESPONSABLE Nivel 1 puede subir/reemplazar/eliminar fotos solo en armas donde es responsable activo. La edición de la información del arma (`update`) sigue siendo exclusiva del ADMIN.
- **Recortar foto existente:** `WeaponPhotoController::update` asigna primero el nuevo `file_id` y luego borra el archivo anterior (evita borrado en cascada de `weapon_photos`). El editor en ficha exporta JPEG redimensionado, muestra toast «Imagen guardada» y bloquea clics repetidos en **Guardar**.
- La actualización de la **foto del permiso** (`WeaponController::updatePermitPhoto`) usa la misma policy `updatePhotos`, así el responsable Nivel 1 puede mantener actualizada la imagen del permiso desde la grilla de fotos.
- UI: el toggle **Modo edición** en la tarjeta de fotos aparece para los usuarios autorizados; el switch usa estilos propios (`.sj-toggle*`) embebidos en el partial, sin dependencia de clases Tailwind dinámicas (no requiere recompilar Vite).
- **Móvil (cámara + galería):** al subir o cambiar una imagen (ficha del arma `resources/views/weapons/partials/photos.blade.php` y formulario crear/editar `form.blade.php`), se muestra un modal **Agregar imagen** con **Tomar foto** (`input` con `capture="environment"`) y **Elegir de galería** (`accept="image/*"` sin `capture`). Tras elegir, el flujo sigue con el editor Cropper y la subida por AJAX o formulario. En escritorio se mantienen arrastrar y pegar. Requiere **HTTPS** en producción para usar la cámara desde el navegador.
- **Editor de imagen en móvil:** el modal **Editar imagen** (`#image_editor_modal`) limita la altura al viewport (`max-h` con `100dvh`), reduce la zona de recorte en pantallas pequeñas (clase `.sj-image-editor-canvas`, ~42dvh) y apila el pie: controles de giro/arriba y fila fija **Cancelar** / **Guardar** (`min-h-11`) con margen para `safe-area-inset-bottom`, para que el botón **Guardar** no quede fuera de pantalla.

Descripciones tecnicas soportadas (`WeaponPhoto::DESCRIPTIONS`):

- `lado_derecho`
- `lado_izquierdo`
- `canon_disparador_marca`
- `serie`
- `impronta`

### 5.9.1 Historial de notas (ficha del arma)

La tarjeta **Notas** en `weapons/show` (`resources/views/weapons/partials/history-panel.blade.php`) muestra un **historial cronológico append-only** (`weapon_histories`), no el texto único de `weapons.notes` como panel principal.

| Componente | Ubicación |
|------------|-----------|
| Tabla | `weapon_histories` — migración `2026_05_19_180000_create_weapon_histories_table.php` |
| Modelo | `App\Models\WeaponHistory` — tipos: `created`, `note`, `update`, `destination`, `internal`, `incident`, `transfer`, `document`, `photos` |
| Servicio | `App\Services\WeaponHistoryService` |
| Relación | `Weapon::histories()` |
| Vista | `resources/views/weapons/partials/history-panel.blade.php` (scroll `max-h-96`) |

**Qué genera entradas automáticamente**

- Alta del arma (`recordCreated`) y edición de datos (`recordWeaponUpdate`: resumen de cambios en campos rastreados + texto del textarea **Notas** si tiene contenido).
- Asignación / retiro de **destino operativo** (incluye `reason` del formulario).
- Asignación / retiro de **asignación interna** (observaciones, puesto, trabajador).
- **Novedades** operativas (alta, seguimiento, cierre, reapertura).
- Carga de **documentos** manuales.
- **Transferencias** (solicitud, aceptación, cancelación).
- Aprobación de fotos en **Revista armas** (`recordRevistaPhotosApproved`: fecha, cantidad de fotos, colaborador temporal).

Si no hay filas en `weapon_histories` pero sí texto en `weapons.notes` heredado, el panel muestra una **nota heredada**; si no hay nada, el estado vacío con mensaje explicativo.

`WeaponController::show` carga `histories` y `histories.user`. El partial también hace `load` si la relación no venía eager-loaded (evita panel vacío por omisión de carga).

Tests: `tests/Feature/WeaponHistoryTest.php`, `RevistaArmasTest::test_approve_staging_photos_records_weapon_history`.

> La auditoría técnica (`audit_logs`) sigue existiendo; el panel **Notas** de la ficha no la sustituye.

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
- **Novedades operativas** (`WeaponIncidentReportController`, `WeaponIncidentReportService`): vista con KPIs, gráficos por tipo/modalidad y tendencia; el detalle tabular no ocupa el scroll principal: el botón **Lista** del encabezado abre un modal (`x-modal`) con la tabla (`resources/views/reports/weapon-incidents/partials/incidents-table.blade.php`), listado alineado al mismo criterio de consulta que el dashboard (colección completa para el periodo/tipo, sin paginación de 20); **Alpine.js** filtra filas por coincidencia en el texto visible; expediente **Gestionar** / **Ver caso** usa los modales existentes (`reports-incidents.js`), con **z-index** del modal de expediente por encima del modal Lista (`resources/css/app.css`).

Alertas:

- Rutas: `GET alerts/documents`, `POST alerts/documents/preview`, `POST alerts/documents/download` (`alerts.documents*`).
- Soporte: `app/Support/AlertDocumentPeriod.php` (validación `months[]`, filtro OR por `valid_until`, etiqueta de períodos y nombre de archivo).
- Vista general por tarjetas:
  - Documentos vencidos
  - Documentos por vencer
  - Armas sin alertas
- Filtro por uno o varios meses calendario (pueden ser de años distintos):
  - botón **Meses** abre un panel con navegación de **año** y **12 checkboxes** (Ene–Dic); **Limpiar** quita la selección; **Filtrar** aplica (`months[]=YYYY-MM` en query)
  - compatibilidad con URL antigua `?month=YYYY-MM` (se normaliza a `months[]`)
  - sin meses seleccionados: muestra todo el sistema
  - con meses seleccionados: documentos de revalidación cuyo `valid_until` cae en **cualquiera** de esos meses
- Nombre de archivo al descargar o previsualizar (solo nombre; el contenido del Word/PDF no incluye el filtro):
  - formato: `Revalidacion_{mes_español}_{año}.docx` / `.pdf` (ej. `Revalidacion_mayo_2025.docx`)
  - un mes en filtro: ese mes
  - varios meses: el período **más cercano a la fecha actual** (empate: el más reciente)
  - sin filtro: mes y año actuales
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

### 5.14 Revista armas (fotos en campo)

Módulo para que colaboradores de campo suban **4 fotos técnicas** por arma (staging) y el staff las revise antes de pasarlas a las fotos oficiales del inventario.

#### Acceso

| Actor | Requisito | Rutas principales |
|--------|-----------|-------------------|
| **Staff** | `ADMIN` o `RESPONSABLE` **nivel 1** (`EnsureRevistaStaff`) | `/revista-armas`, `/revista-armas/usuarios-temporales` |
| **Invitado** | Código de acceso 12 h (sin fila en `users`) | `/revista-armas/ingreso`, `/revista-armas/mis-armas` |

Sesión invitado: `revista_grant_id` (grant activo en `temporary_photo_access_grants`).

Nombres de ruta staff relevantes: prefijo `revista-armas.*`; CRUD de temporales con nombres `revista-armas.temporary-users.*` (URL `/revista-armas/usuarios-temporales`).

#### Usuarios temporales (`temporary_photo_users`)

- CRUD reutilizable en `/revista-armas/usuarios-temporales`.
- Campo **Responsable dueño** (`owner_responsible_user_id`): solo usuarios del sistema con rol `RESPONSABLE` (el **ADMIN** elige en el formulario; el responsable nivel 1 queda asignado a sí mismo).
- Desactivar usuario temporal o revocar acceso **no borra** filas en `weapon_photo_staging`.
- **ADMIN**: ve y gestiona todos los temporales activos. **RESPONSABLE nivel 1**: solo los que tiene como dueño.

#### Asignación de acceso

- Tablas: `temporary_photo_access_grants` + `temporary_photo_access_weapons`.
- Modal **Asignar acceso temporal** en el listado staff: elige usuario temporal + armas (checkboxes).
- Código válido **12 h**, correo `RevistaTemporaryAccessMail`; modal de éxito con enlace, correo y código copiable.
- Revocar acceso no elimina staging ya subido.

#### Vista staff (`/revista-armas`)

- Lista armas según alcance (`RevistaArmasScopeService`: global para **ADMIN**, cartera/responsable activo para **RESPONSABLE** nivel 1).
- Filtro **Usuario temporal (columna Realizado)** + botón **Filtrar** (`?temporary_photo_user_id=`).
  - Sin usuario temporal seleccionado: columna **Realizado** muestra `—` y **Acciones** va vacía (comportamiento esperado).
  - Con filtro aplicado: **Realizado** = ✓ si 4/4 fotos en staging de **ese** colaborador; ✕ si falta alguna; botón **Ver** abre modal de revisión.
  - El filtro es necesario porque el progreso y la revisión son por par **(arma, usuario temporal)**, no por arma sola.
- Modal **Ver**: muestra las **4 casillas** (con o sin imagen); API `revista-armas.review` devuelve `slots`, `uploaded_count`, `pending_count`, `is_complete`.
- **Actualizar**:
  - Si faltan fotos (`is_complete === false`): modal de **aviso** centrado — *«No se pueden actualizar las imágenes oficiales porque faltan N foto(s) pendiente(s).»* (sin `confirm` del navegador).
  - Si están las 4: modal de **confirmación** centrado; al aceptar, `POST` `revista-armas.review.approve` → copia a `weapon_photos` + `syncRenewalDocument` + entrada en `weapon_histories` (tipo **Fotografías**, con fecha y colaborador temporal).
  - Errores del servidor (p. ej. 422): modal de aviso; no recarga la página a ciegas.
- **Rechazar**: modal de confirmación; elimina staging de ese temporal en esa arma.
- UI: modales `#revista-confirm-modal`, `#revista-alert-modal` en `resources/views/revista-armas/index.blade.php` (misma familia visual que el modal de revisión).

#### Vista invitado (`/revista-armas/mis-armas`)

- Tabla de armas asignadas al grant vigente; **Realizado** ✓/✕ según 4/4 en staging.
- **Ver** abre modal de captura (Cropper: cámara o galería) vía `revista-armas.partials.photo-capture-kit`.
- Tras cada foto guardada: el modal de fotos **permanece abierto**; toast verde **«Imagen guardada»** (~4,5 s); se refrescan las miniaturas y la columna **Realizado** sin recargar la página.
- Captura móvil (`photo-capture-kit`): exporta JPEG redimensionado (máx. 1920 px), botón **Guardar** con estado **Guardando…** (evita clics repetidos), Cropper tras `onload`, errores en modal propio (no `alert` del navegador).
- Layout invitado: `layouts/revista-guest.blade.php` incluye `@stack('styles')` y `@stack('scripts')` (requerido para el JS del modal).

#### Staging y slots

- Tabla `weapon_photo_staging`; descripciones fijas en `App\Support\RevistaWeaponPhotoSlots`: `lado_derecho`, `lado_izquierdo`, `canon_disparador_marca`, `serie`.
- Servicios: `WeaponPhotoStagingService` (inyecta `WeaponHistoryService` al aprobar), `TemporaryPhotoAccessService`, `RevistaArmasScopeService`.
- Controladores: `RevistaArmasController`, `RevistaPhotoReviewController` (`approve` / `reject`).
- Migraciones: `2026_05_19_140000_create_revista_armas_tables.php` (índices/FK cortos para MySQL ≤ 64 caracteres); `2026_05_19_180000_create_weapon_histories_table.php`.
- Tests: `tests/Feature/RevistaArmasTest.php`, `tests/Feature/WeaponHistoryTest.php`.

### 5.15 Dashboard operativo

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
  - distribucion interna (Solo puesto / Solo trabajador / Puesto y trabajador / Sin asignacion interna; conteos mutuamente excluyentes)

Comportamiento relevante:
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
- Desde el listado de usuarios, acción **Enviar**: modal de confirmación y `POST users.send-access-credentials` genera contraseña temporal, marca `must_change_password` y envía correo (`UserAccessCredentialsMail`) con enlace (`APP_URL`), usuario (correo) e instrucciones de cambio obligatorio al primer ingreso.
- Cambio de estado de usuario.
- Carga/actualizacion de fotos.
- Carga de documentos.
- Asignaciones cliente e internas (incluye combinacion trabajador + puesto y bloqueo sin coordenadas en mapa).
- Cierres de asignaciones por transferencia/cambio cliente.
- Solicitud, aceptación y **cancelación** de transferencias (registros antiguos pueden figurar como rechazados).
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
- `weapon_transfers` (incluye `ammo_count`, `provider_count` opcionales en el envío)

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
  - `profile.edit/update/destroy` (la UI de **editar perfil** no expone eliminación de cuenta; la ruta `destroy` puede seguir existiendo por compatibilidad o pruebas).
- Administracion:
  - `users.*`, `users.status`, `users.send-access-credentials` (POST: reenvío de credenciales por correo con contraseña temporal nueva, solo ADMIN desde el listado).
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
  - `transfers.index`, `transfers.bulk`, `transfers.accept`, `transfers.cancel`.
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

Entradas Vite:

- **`vite.config.js`** (local): `resources/css/app.css`, `resources/js/app.js`, `resources/js/map.js`, `resources/js/location-picker.js`.
- **`vite.hosting.config.js`** (deploy): mismas entradas; `envDir` = `build_hosting/`, salida bajo `build_hosting/build/` (no modifica `public/build` local).

Caracteristicas:

- Navegacion responsive por rol en `resources/views/layouts/navigation.blade.php`.
- Idioma con cambio de session (`es`, `en`).
- Modales de seleccion de ubicacion con mapa, buscador textual y control de capas (hibrido / calles).
- Cluster de mapa con icono personalizado y contador; popup de lista de armas con zona scrolleable.
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
- Plantillas reverso autenticado (global, porte/tenencia): `storage/app/permit-authenticated-templates` (metadatos en `files` / `permit_authenticated_templates`)
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
- `SESSION_LIFETIME=30` (o el valor vigente en `.env`; inactividad en minutos)
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

### 12.4 Tiempo real en hosting compartido (Pusher)

En planes tipo Hostinger **no suele poder** mantener `reverb:start` como proceso permanente. Lo habitual es usar **Pusher** (Channels): Laravel publica eventos por HTTPS hacia Pusher y el navegador se conecta a los servidores de Pusher; no hace falta abrir puertos WebSocket propios.

1. Cree una app en [Pusher Channels](https://pusher.com/) (el plan gratuito permite pruebas).
2. En `.env` del servidor y en **`build_hosting/.env.production`** (solo para `npm run build:deploy` en tu PC):

   - `BROADCAST_CONNECTION=pusher`
   - `BROADCAST_ENABLED=true`
   - `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER` (según el panel de Pusher)
   - `PUSHER_SCHEME=https` en producción si el sitio usa HTTPS
   - `VITE_BROADCAST_CONNECTION=pusher`
   - `VITE_PUSHER_APP_KEY` igual que `PUSHER_APP_KEY`
   - `VITE_PUSHER_APP_CLUSTER` igual que `PUSHER_APP_CLUSTER`
   - `VITE_PUSHER_SCHEME=https`

3. Regenerar assets para el hosting sin tocar el build local: `npm run build:deploy` (usa `build_hosting/.env.production`) y suba al servidor el contenido de `build_hosting/build/` dentro de `public/build/`. En local: `npm run build` → `public/build` (modo `localbuild`: toma `VITE_*` de `.env` / `.env.local`, no de `.env.production` en la raíz).
4. En el servidor: `php artisan config:clear` (o `config:cache`).

`resources/js/bootstrap.js` usa Reverb o Pusher según `VITE_BROADCAST_CONNECTION`. Para **Soketi** u otro servidor compatible, configure `PUSHER_HOST` y `VITE_PUSHER_HOST` además del puerto y el esquema.

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
- Feature de inventario operativo (`WeaponOperationalInventoryTest`), incluyendo listado con transferencia pendiente y asignación de cliente legacy cerrada (`operationalDisplayClient`).

Comando:

- `php artisan test`

Configuracion de testing:

- `phpunit.xml` define variables de entorno para PHPUnit (`APP_ENV=testing`, SQLite en memoria, etc.)

Con esto, `php artisan test` no debe tocar la base real del proyecto.

## 16. Operacion y despliegue (resumen)

**PHP y Blade** no se “compilan” como un binario: el servidor escucha PHP y Laravel puede **cachear** rutas, configuración y vistas compiladas para rendimiento. Si solo cambió backend o plantillas Blade **sin** tocar `resources/js` / `resources/css` ni variables `VITE_*`, **no** hace falta volver a ejecutar `npm run build` / `build:deploy`. Tras actualizar vistas, si usó `view:cache` antes, conviene `php artisan view:clear` y volver a `view:cache` (o dejar que Blade recompilara en caliente si no usa caché de vistas).

Checklist minimo de produccion:

1. Configurar `.env` de produccion.
2. `composer install --no-dev --optimize-autoloader`
3. **Frontend**: en la PC de build, `npm ci` y luego **`npm run build:deploy`** (requiere **crear** `build_hosting/.env.production`; vea `.env.example`); subir el contenido de **`build_hosting/build/`** a **`public/build/`** del servidor. Si compila directamente en el servidor con un solo `.env` que ya incluye los `VITE_*` de produccion, puede usar `npm run build`. **No hace falta** volver a generar este build si el cambio fue solo backend, README o vistas Blade **sin** tocar `resources/js`, `resources/css` ni variables `VITE_*`.
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
