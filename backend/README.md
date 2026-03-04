# TCG Engineering Hub - MVP SaaS

MVP SaaS para ejecutar el TCG Engineering Framework en proyectos.

## Stack

- **Backend**: Laravel 11 (API)
- **Frontend**: Vue 3 + Vuetify
- **Authentication**: Laravel Sanctum

## Requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL/MariaDB

## Instalación

### 1. Clonar el repositorio

```bash
# Estructura del proyecto
/Project_test
  /backend    # API Laravel
  /frontend   # Vue 3 app
```

### 2. Configurar Backend

```bash
cd backend

# Instalar dependencias
composer install

# Copiar archivo de configuración
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate

# Configurar base de datos en .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tcg_hub
DB_USERNAME=root
DB_PASSWORD=

# Crear base de datos
mysql -u root -p
CREATE DATABASE tcg_hub;
CREATE DATABASE tcg_hub_test;  # Para tests
exit;

# Ejecutar migraciones
php artisan migrate

# (Opcional) Poblar base de datos con datos de prueba
php artisan db:seed
```

### 3. Configurar Frontend

```bash
cd frontend

# Instalar dependencias
npm install

# Copiar archivo de configuración
cp .env.example .env

# Ejecutar en desarrollo
npm run dev
```

## Seed de Usuarios Demo

El proyecto incluye un seeder para crear usuarios de prueba con diferentes roles:

```bash
php artisan db:seed --class=DatabaseSeeder
```

### Usuarios creados:

| Email | Password | Rol |
|-------|----------|-----|
| admin@tcg.com | password | admin |
| pm@tcg.com | password | pm |
| engineer@tcg.com | password | engineer |
| viewer@tcg.com | password | viewer |

## Ejecutar Tests

```bash
# Ejecutar todos los tests
php artisan test

# Ejecutar tests específicos
php artisan test --filter=Gate1Test
php artisan test --filter=ModuleValidationTest
php artisan test --filter=ViewerAuthorizationTest
```

### Tests implementados:

1. **Gate1Test** - Verifica que domain_breakdown no puede completarse si big_picture no está hecho
2. **ModuleValidationTest** - Verifica que un módulo no puede validarse sin los campos requeridos
3. **Gate4ProjectExecutionTest** - Verifica que un proyecto no puede pasar a ejecución sin los artifacts requeridos
4. **ViewerAuthorizationTest** - Verifica que el rol viewer solo tiene acceso de lectura

## API Endpoints

### Autenticación
- `POST /api/auth/login` - Iniciar sesión
- `POST /api/auth/register` - Registrarse
- `POST /api/auth/logout` - Cerrar sesión
- `GET /api/auth/me` - Obtener usuario actual
- `PUT /api/auth/profile` - Actualizar perfil
- `PUT /api/auth/password` - Cambiar contraseña

### Proyectos
- `GET /api/projects` - Listar proyectos
- `POST /api/projects` - Crear proyecto
- `GET /api/projects/{id}` - Ver proyecto
- `PUT /api/projects/{id}` - Actualizar proyecto
- `DELETE /api/projects/{id}` - Eliminar proyecto
- `POST /api/projects/{id}/archive` - Archivar proyecto
- `POST /api/projects/{id}/restore` - Restaurar proyecto

### Artifacts
- `GET /api/artifacts` - Listar artifacts
- `POST /api/artifacts` - Crear artifact
- `GET /api/artifacts/{id}` - Ver artifact
- `PUT /api/artifacts/{id}` - Actualizar artifact
- `DELETE /api/artifacts/{id}` - Eliminar artifact
- `POST /api/artifacts/{id}/complete` - Completar artifact
- `POST /api/artifacts/{id}/change-status` - Cambiar estado
- `POST /api/artifacts/{id}/assign` - Asignar artifact

### Módulos
- `GET /api/modules` - Listar módulos
- `POST /api/modules` - Crear módulo
- `GET /api/modules/{id}` - Ver módulo
- `PUT /api/modules/{id}` - Actualizar módulo
- `DELETE /api/modules/{id}` - Eliminar módulo
- `POST /api/modules/{id}/validate` - Validar módulo
- `POST /api/modules/{id}/change-status` - Cambiar estado

### Usuarios
- `GET /api/users` - Listar usuarios (admin/pm)
- `POST /api/users` - Crear usuario (admin)
- `GET /api/users/{id}` - Ver usuario
- `PUT /api/users/{id}` - Actualizar usuario
- `DELETE /api/users/{id}` - Eliminar usuario
- `GET /api/users/list` - Lista corta de usuarios

## Roles y Permisos

| Rol | Permisos |
|-----|----------|
| admin | Acceso completo |
| pm | Gestionar proyectos, artifacts, módulos |
| engineer | Editar módulos, ver artifacts |
| viewer | Solo lectura |

## Artefactos del Framework

Los 7 tipos de artifacts del TCG Engineering Framework:

1. **Strategic Alignment** - Alineación estratégica
2. **Big Picture** - Visión general
3. **Domain Breakdown** - Desglose de dominios
4. **Module Matrix** - Matriz de módulos
5. **Module Engineering** - Ingeniería de módulos
6. **System Architecture** - Arquitectura del sistema
7. **Phase Scope** - Alcance de fase

## Gates (Reglas de Negocio)

### Gate 1
No se puede marcar domain_breakdown como "done" si big_picture no está completado.

### Gate 2
No se puede marcar module_matrix como "done" si domain_breakdown no está completado.

### Gate 3
No se puede marcar system_architecture como "done" si no hay al menos 3 módulos validados.

### Gate 4
No se puede mover el proyecto de discovery → execution si los siguientes artifacts no están completados:
- strategic_alignment
- big_picture
- domain_breakdown
- module_matrix

## Decisiones de Arquitectura

1. **API-First**: La aplicación está diseñada como una API RESTful, separando claramente el backend del frontend.

2. **RBAC con Policies**: Usa Laravel Policies para la autorización, manteniendo la lógica de permisos fuera de los controladores.

3. **Domain Service para Gates**: Las reglas de negocio están implementadas en `ArtifactGateService`, separando la lógica de dominio de los controladores.

4. **Soft Deletes**: Los proyectos usan soft delete para permitir recuperación.

5. **Audit Trail**: Se registra cada cambio importante (creación, actualización, cambio de estado) en la tabla audit_events.

6. **MySQL/MariaDB**: Base de datos configurada para MySQL/MariaDB en producción y tests.

## Mejoras Futuras (Pendientes)

- **Templates**: Capacidad de crear proyectos con artifacts pre-creados
- **Export**: Exportar proyectos a JSON
- **Notificaciones**: Sistema de alertas para gates bloqueados
- **Dashboard**: Métricas y estadísticas del proyecto
- **Versionado**: Historial de cambios en artifacts
- **Export PDF**: Generación de documentos PDF

## Licencia

MIT License

