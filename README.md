# SysIncidencias - Arquitectura de Microservicios

Sistema completo de **Gestión de Incidencias** construido sobre **PHP Vanilla** con arquitectura de microservicios. El sistema cubre el ciclo completo: desde el reporte de una incidencia, su asignación a técnicos, hasta el registro de la solución y el cierre automático del caso.

---

## 🏗️ Arquitectura General

### Capas del Sistema
1. **Frontend (`/frontend`)**: SPA (Single Page Application) — no recarga el navegador.
   - Enrutador `app.js` inyecta módulos dinámicamente en el contenedor.
   - Cada módulo (`/modules/<nombre>/`) tiene su propio `.html` + `.js`.
2. **API Gateway (`/api-gateway`)**: Proxy inverso en el puerto `8000`.
   - Redirige peticiones al microservicio correcto según el parámetro `?service=`.
3. **Microservicios (Backend)**: Cada servicio corre en su propio puerto y gestiona su propia base de datos PostgreSQL de forma independiente.

---

## 🗺️ Mapeo de Puertos y Bases de Datos

| # | Servicio           | Puerto | Carpeta               | Base de Datos               |
|---|:-------------------|:------:|:----------------------|:----------------------------|
| 1 | **API Gateway**    | 8000   | `/api-gateway`        | N/A                         |
| 2 | **Categorías**     | 8001   | `/category-service`   | `service_category_db`       |
| 3 | **Roles**          | 8002   | `/rol-service`        | `service_rol_db`            |
| 4 | **Usuarios**       | 8003   | `/user-service`       | `service_user_db`           |
| 5 | **Incidencias**    | 8004   | `/incident-service`   | `service_incident_db`       |
| 6 | **Asignaciones**   | 8005   | `/assignment-service` | `service_assignment_db`     |
| 7 | **Soluciones**     | 8006   | `/solution-service`   | `service_solution_db`       |

---

## 📦 Módulos del Sistema

### 1. Gestión de Categorías
Permite crear, editar y desactivar las categorías de incidencias.

### 2. Gestión de Roles
Administración de los roles del sistema. Roles clave:
- **Administrador**: Acceso total.
- **Técnico**: Puede acceder y resolver incidencias asignadas.

### 3. Gestión de Usuarios
CRUD completo de usuarios, asignando un rol a cada uno. La contraseña se almacena con hash seguro (`password_hash`).

### 4. Gestión de Incidencias
Registro completo de incidencias con:
- Datos de cabecera (título, descripción, fecha, categoría, ubicación con mapa Leaflet).
- Múltiples **detalles por incidencia** (maestro-detalle).
- Estados automáticos:
  - `1` = **Pendiente** (azul)
  - `2` = **En Proceso** (amarillo) — se activa al asignar un técnico.
  - `3` = **Finalizado** (verde) — se activa cuando se registran todas las soluciones.

### 5. Asignaciones de Técnicos
Permite asignar una o más incidencias a uno o más técnicos. Al crear la asignación, el estado de la incidencia cambia automáticamente a **"En Proceso"** vía comunicación interna entre microservicios.

### 6. Registro de Soluciones *(solo para Técnicos)*
Los técnicos ven únicamente las incidencias que tienen asignadas. Pueden registrar soluciones de forma parcial (detalle por detalle), respondiendo con texto libre a cada detalle de la incidencia. Cuando todos los detalles tienen su solución, la incidencia se cierra automáticamente como **"Finalizada"**.

---

## 🚀 Instalación Paso a Paso

### Requisitos Previos
- **PHP 7.4** o superior con las extensiones `curl` y `pdo_pgsql` habilitadas.
- **PostgreSQL** corriendo en `localhost:5432`.
  - Usuario por defecto: `postgres`
  - Contraseña por defecto: `12345678`
- **PowerShell** (para el script de arranque).

> **Nota:** Si usas Laragon, PHP y PostgreSQL ya están incluidos. Asegúrate de que el servicio de PostgreSQL esté activo en el panel de Laragon.

---

### Paso 1: Ejecutar las Migraciones

Las migraciones crean las bases de datos y tablas necesarias. Se deben ejecutar **una sola vez** desde la raíz del proyecto.

Abre una terminal **PowerShell** (o CMD) en `C:\laragon\www\INCIDENCIAS_MICROSERVICIOS\` y ejecuta:

```powershell
# Servicio de Categorías
php category-service/migrate.php

# Servicio de Roles
php rol-service/migrate.php

# Servicio de Usuarios
php user-service/migrate.php

# Servicio de Incidencias
php incident-service/migrate.php

# Servicio de Asignaciones
php assignment-service/migrate.php

# Servicio de Soluciones
php solution-service/migrate.php
```

Deberías ver mensajes como `Database ... created successfully.` y `Tables created successfully.` para cada uno. Si dice "ya existe", significa que ya fue ejecutado antes y está bien.

---

### Paso 2: Ejecutar el Seeder (Datos Iniciales)

El seeder crea los datos iniciales: el **rol Administrador** y el **usuario administrador** por defecto. Ejecútalo **una sola vez** después de las migraciones:

```powershell
php seeder.php
```

El resultado esperado es:

```
Iniciando Seeder del Sistema...
[+] Rol 'Administrador' creado con ID: X
[+] Usuario Administrador creado exitosamente.
    -> Correo: admin@admin.com
    -> Contrasena: admin123
```

> **Importante:** Una vez logueado como administrador, crea manualmente un usuario con rol **"Técnico"** desde el módulo de Usuarios, ya que el seeder sólo crea el administrador.

---

### Paso 3: Iniciar los Microservicios

Todos los servicios deben estar corriendo al mismo tiempo. El script automatizado levanta los 7 servidores en ventanas separadas:

```powershell
.\start_services.ps1
```

Se abrirán **7 ventanas de PowerShell** — una por cada servicio. **No las cierres** mientras usas la aplicación, ya que son los servidores activos.

Los servicios que se inician son:
- `API Gateway` → Puerto 8000
- `Category Service` → Puerto 8001
- `Role Service` → Puerto 8002
- `User Service` → Puerto 8003
- `Incident Service` → Puerto 8004
- `Assignment Service` → Puerto 8005
- `Solution Service` → Puerto 8006

---

### Paso 4: Abrir la Aplicación

Con todos los servicios corriendo, abre el archivo principal del frontend en tu navegador:

```
C:\laragon\www\INCIDENCIAS_MICROSERVICIOS\frontend\index.html
```

O si usas Laragon con su servidor web, accede directamente a:
```
http://localhost/INCIDENCIAS_MICROSERVICIOS/frontend/
```

**Credenciales de acceso inicial:**
- **Email:** `admin@admin.com`
- **Contraseña:** `admin123`

---

## 🔄 Flujo de Trabajo del Sistema

A continuación se describe el ciclo completo de vida de una incidencia:

```
1. Admin crea Categorías y Roles
         ↓
2. Admin crea Usuarios (asigna roles: Administrador / Técnico)
         ↓
3. Admin/Usuario registra una Incidencia con Detalles
         ↓  [Estado: PENDIENTE 🔵]
4. Admin asigna la Incidencia a un Técnico
         ↓  [Estado: EN PROCESO 🟡 — automático]
5. Técnico ingresa a "Soluciones" y registra respuestas (puede ser parcial)
         ↓
6. Al responder TODOS los detalles → Estado: FINALIZADO 🟢 (automático)
```

---

## 🔒 Seguridad y Acceso

- **Autenticación**: Al loguearse, se guarda el objeto `auth_user` en `localStorage`. El enrutador verifica su presencia antes de mostrar cualquier módulo.
- **Control por Rol (Frontend)**: El módulo **Soluciones** valida dinámicamente si el usuario tiene rol de técnico. Si no, muestra "Acceso Denegado".
- **CORS**: El API Gateway tiene CORS configurado para permitir comunicación desde el frontend.
- **Comunicación Interna**: Los microservicios se comunican entre sí directamente via `cURL` (sin pasar por el Gateway) para operaciones de sincronización de estado (ej: Asignaciones → Incidencias).

---

## 🗂️ Estructura de Carpetas

```
INCIDENCIAS_MICROSERVICIOS/
├── api-gateway/             # Proxy inverso (Puerto 8000)
├── category-service/        # CRUD categorías (Puerto 8001)
│   ├── config.php
│   ├── database.sql
│   ├── migrate.php
│   ├── models/
│   └── controllers/
├── rol-service/             # CRUD roles (Puerto 8002)
├── user-service/            # CRUD usuarios + login (Puerto 8003)
├── incident-service/        # CRUD incidencias + detalles (Puerto 8004)
├── assignment-service/      # Asignaciones técnico-incidencia (Puerto 8005)
├── solution-service/        # Registro de soluciones (Puerto 8006)
├── frontend/                # SPA (HTML + CSS + JS puro)
│   ├── index.html           # Shell principal
│   ├── login.html
│   ├── app.js               # Enrutador SPA
│   ├── style.css
│   └── modules/
│       ├── categories/
│       ├── roles/
│       ├── users/
│       ├── incidents/
│       ├── assignments/
│       └── solutions/
├── seeder.php               # Datos iniciales (Admin)
├── start_services.ps1       # Script de arranque de todos los servicios
└── README.md
```
