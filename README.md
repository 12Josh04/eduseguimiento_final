# EduSeguimiento
## Sistema de Seguimiento Académico y Conductual
### I.E. San Marcos — Huaraz, Perú · 2026

Stack: **Laravel 11 · PHP 8.2+ · MySQL (XAMPP) · Vite · Blade**

---

## REQUISITOS PREVIOS

| Herramienta | Versión mínima | Cómo verificar |
|---|---|---|
| XAMPP | 8.2+ | Abre XAMPP Control Panel |
| PHP | 8.2+ | `php -v` en CMD |
| Composer | 2.x | `composer -V` en CMD |
| Node.js | 18+ | `node -v` en CMD |
| npm | 9+ | `npm -v` en CMD |

> **PHP en el PATH:** Si `php` no se reconoce en CMD, agrega `C:\xampp\php` al PATH de Windows:
> Panel de Control → Variables de entorno → PATH → Nuevo → `C:\xampp\php`

---

## INSTALACIÓN PASO A PASO

### PASO 1 — Copiar el proyecto

Extrae el ZIP y copia la carpeta `eduseguimiento` dentro de:
```
C:\xampp\htdocs\eduseguimiento\
```

### PASO 2 — Crear la base de datos

1. Inicia XAMPP → enciende **Apache** y **MySQL**
2. Abre `http://localhost/phpmyadmin`
3. Clic en **Nueva** (panel izquierdo)
4. Nombre: `eduseguimiento`
5. Cotejamiento: `utf8mb4_unicode_ci`
6. Clic **Crear**

### PASO 3 — Configurar el entorno

Abre CMD o PowerShell **dentro de la carpeta del proyecto**:
```bash
cd C:\xampp\htdocs\eduseguimiento
```

Copia el archivo de entorno:
```bash
copy .env.example .env
```

Abre `.env` con VS Code y verifica estas líneas:
```env
APP_URL=http://localhost/eduseguimiento/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eduseguimiento
DB_USERNAME=root
DB_PASSWORD=          ← dejar vacío si XAMPP no tiene contraseña
```

### PASO 4 — Instalar dependencias PHP (Backend)

```bash
composer install
```

> Primera vez tarda 2-5 minutos descargando paquetes.

### PASO 5 — Generar clave de la aplicación

```bash
php artisan key:generate
```

Verás: `Application key set successfully.`

### PASO 6 — Instalar dependencias JS (Frontend)

```bash
npm install
```

### PASO 7 — Compilar assets frontend

Para desarrollo (con hot-reload):
```bash
npm run dev
```

O para producción (una sola vez):
```bash
npm run build
```

> **Nota:** Este proyecto sirve CSS/JS directamente desde `public/css/` y `public/js/`.
> El comando `npm run build` es opcional — el sistema funciona sin él.

### PASO 8 — Crear tablas y cargar datos de prueba

```bash
php artisan migrate:fresh --seed
```

Verás al final:
```
✅  Seeder completado.

  promotor@eduseguimiento.com    → Promotor   (password123)
  docente@eduseguimiento.com     → Docente    (password123)
  psicologa@eduseguimiento.com   → Psicóloga  (password123)
  estudiante@eduseguimiento.com  → Estudiante (password123)
```

### PASO 9 — Permisos de storage (solo Linux/Mac)

```bash
chmod -R 775 storage bootstrap/cache
```

En Windows con XAMPP esto no es necesario.

### PASO 10 — Abrir en el navegador

```
http://localhost/eduseguimiento/public
```

---

## CUENTAS DE ACCESO

| Rol | Email | Contraseña | Puede hacer |
|---|---|---|---|
| **Promotor** | promotor@eduseguimiento.com | password123 | Todo: usuarios, notas, conductas, reportes |
| **Docente** | docente@eduseguimiento.com | password123 | Registrar notas y conductas, ver estudiantes |
| **Psicóloga** | psicologa@eduseguimiento.com | password123 | Conductas, historial, seguimiento |
| **Estudiante** | estudiante@eduseguimiento.com | password123 | Ver sus propias notas y conductas |

---

## ESTRUCTURA DEL PROYECTO

```
eduseguimiento/
│
├── app/
│   ├── Helpers/
│   │   └── Auth.php                 ← Auth propio por sesión (NO usa Breeze)
│   ├── Http/
│   │   ├── Controllers/             ← AuthController, Dashboard, Notas, etc.
│   │   └── Middleware/
│   │       └── AuthMiddleware.php   ← Verifica sesión y roles
│   ├── Models/
│   │   └── User.php                 ← Modelo mínimo (no usado en auth real)
│   ├── Providers/
│   │   └── AppServiceProvider.php  ← Directivas @auth/@guest por sesión
│   └── helpers.php                  ← Función current_user()
│
├── bootstrap/
│   ├── app.php                      ← Registra AuthMiddleware como 'auth.session'
│   └── providers.php
│
├── config/
│   ├── app.php                      ← Alias Auth → App\Helpers\Auth (CRÍTICO)
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── queue.php
│   └── session.php
│
├── database/
│   ├── factories/UserFactory.php
│   ├── migrations/                  ← 11 migraciones en orden
│   └── seeders/DatabaseSeeder.php   ← 17 estudiantes + notas + conductas
│
├── public/
│   ├── css/app.css                  ← CSS propio del sistema (sin frameworks)
│   ├── js/app.js                    ← JS vanilla con gráficas simples
│   ├── index.php                    ← Punto de entrada web
│   └── .htaccess                    ← Rewrite rules para Apache/XAMPP
│
├── resources/
│   ├── css/app.css                  ← Entry point para Vite (vacío)
│   ├── js/app.js                    ← Entry point para Vite (vacío)
│   └── views/
│       ├── auth/login.blade.php
│       ├── layouts/app.blade.php    ← Layout principal: navbar + sidebar
│       ├── dashboard/index.blade.php
│       ├── estudiantes/{index,show}.blade.php
│       ├── notas/{index,promedios}.blade.php
│       ├── conductas/{index,historial}.blade.php
│       ├── usuarios/index.blade.php
│       └── errors/403.blade.php
│
├── routes/
│   ├── web.php                      ← Todas las rutas del sistema
│   └── console.php
│
├── storage/                         ← Logs, caché, sesiones (auto-creado)
├── tests/
├── .env.example                     ← Plantilla de configuración
├── .gitignore
├── artisan                          ← CLI de Laravel
├── composer.json                    ← Dependencias PHP
├── package.json                     ← Dependencias Node/Vite
├── phpunit.xml
└── vite.config.js
```

---

## TABLAS DE LA BASE DE DATOS

| Migración | Tabla | Descripción |
|---|---|---|
| 000001 | `roles` | 4 roles del sistema |
| 000002 | `users` | Todos los usuarios |
| 000003 | `grados` | 1°A, 1°B … 5°A |
| 000004 | `cursos` | Matemática, Comunicación, etc. |
| 000005 | `periodos` | Bimestre I, II, III, IV |
| 000006 | `estudiantes` | Datos del estudiante |
| 000007 | `matriculas` | Estudiante ↔ Grado |
| 000008 | `apoderados` | Padres/tutores |
| 000009 | `notas` | Calificaciones |
| 000010 | `conductas` | Registro conductual |
| 000011 | `asignaciones_docente` | Docente ↔ Curso ↔ Grado |

---

## SOLUCIÓN A PROBLEMAS COMUNES

**`php` no se reconoce en CMD**
```
Agregar C:\xampp\php al PATH de Windows y reiniciar CMD
```

**Error: `Application key not set`**
```bash
php artisan key:generate
```

**Error de conexión a BD**
- Verificar que MySQL esté corriendo en XAMPP
- Verificar que `.env` tenga `DB_PASSWORD=` vacío (si XAMPP no tiene contraseña)
- Verificar que la BD `eduseguimiento` exista en phpMyAdmin

**Página en blanco o CSS no carga**
```
Abrir: http://localhost/eduseguimiento/public  (con /public al final)
```

**Error 500 después de `composer install`**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

**Las sesiones no funcionan / login redirige en loop**
```bash
php artisan config:clear
# Verificar que storage/framework/sessions/ exista y tenga permisos de escritura
```

---

## COMANDOS ÚTILES

```bash
# Recrear BD desde cero con datos de prueba
php artisan migrate:fresh --seed

# Limpiar todo el caché
php artisan optimize:clear

# Ver todas las rutas registradas
php artisan route:list

# Consola interactiva
php artisan tinker

# Servidor de desarrollo (alternativa a XAMPP)
php artisan serve
# Abre: http://127.0.0.1:8000
```

---

*EduSeguimiento © 2026 — I.E. San Marcos, Huaraz, Ancash, Perú*
