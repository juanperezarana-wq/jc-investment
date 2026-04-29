# 🏦 JC Investments – Guía de instalación paso a paso

## ✅ Requisitos previos (instalar primero)

| Herramienta | Versión | Descarga |
|-------------|---------|---------|
| XAMPP       | 8.x     | https://www.apachefriends.org |
| Composer    | 2.x     | https://getcomposer.org |
| Node.js     | 18+     | https://nodejs.org (opcional) |

---

## 📦 PASO 1 – Crear el proyecto Laravel

Abre la terminal (CMD o PowerShell) y ejecuta:

```bash
# Ir a la carpeta de XAMPP
cd C:\xampp\htdocs

# Crear proyecto Laravel
composer create-project laravel/laravel jc-investments

# Entrar al proyecto
cd jc-investments
```

---

## 📁 PASO 2 – Copiar los archivos del proyecto

Reemplaza los siguientes archivos del proyecto recién creado
con los archivos que te entregué:

```
jc-investments/
├── routes/
│   └── web.php                          ← copiar
├── app/
│   ├── Models/
│   │   ├── Usuario.php                  ← copiar
│   │   └── Models.php                   ← copiar (contiene todos los modelos)
│   └── Http/
│       ├── Controllers/
│       │   ├── AuthController.php       ← copiar
│       │   └── Controllers.php          ← copiar
│       └── Middleware/
│           └── CheckRole.php            ← copiar
├── database/
│   ├── migrations/
│   │   ├── ...create_usuarios_table.php ← copiar
│   │   └── ...create_prestamos.php      ← copiar
│   └── seeders/
│       └── DatabaseSeeder.php           ← copiar
```

---

## 🗄️ PASO 3 – Configurar la base de datos

### 3.1 Crear la base de datos en phpMyAdmin
1. Abre tu navegador y ve a: `http://localhost/phpmyadmin`
2. Clic en **"Nueva"** (panel izquierdo)
3. Escribe el nombre: `jc_investments`
4. Cotejamiento: `utf8mb4_unicode_ci`
5. Clic en **Crear**

### 3.2 Configurar el archivo .env
Abre el archivo `.env` en la raíz del proyecto y edita:

```env
APP_NAME="JC Investments"
APP_URL=http://localhost/jc-investments/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jc_investments
DB_USERNAME=root
DB_PASSWORD=              ← dejar vacío si XAMPP no tiene contraseña
```

---

## ⚙️ PASO 4 – Registrar el Middleware de roles

Abre `app/Http/Kernel.php` y busca `$routeMiddleware`, agrega:

```php
protected $routeMiddleware = [
    // ... (los que ya existen)
    'role' => \App\Http\Middleware\CheckRole::class,
];
```

También cambia el modelo de autenticación en `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => App\Models\Usuario::class,   // ← cambiar de User a Usuario
    ],
],
```

---

## 🚀 PASO 5 – Ejecutar migraciones y seeder

```bash
# Crear todas las tablas en la base de datos
php artisan migrate

# Cargar datos de prueba (usuarios y tipos de préstamo)
php artisan db:seed

# Si quieres hacer todo de una sola vez:
php artisan migrate:fresh --seed
```

---

## 🌐 PASO 6 – Iniciar el servidor

```bash
# Desde la carpeta del proyecto
php artisan serve
```

Ahora abre tu navegador y entra a:
👉 `http://127.0.0.1:8000`

---

## 🔑 Usuarios de prueba (creados por el seeder)

| Rol       | Email                          | Contraseña   |
|-----------|--------------------------------|--------------|
| Admin     | admin@jcinvestments.com        | admin123     |
| Analista  | analista@jcinvestments.com     | analista123  |
| Cliente   | cliente@demo.com               | cliente123   |

---

## ☁️ PASO 7 – Subir a la nube GRATIS (Railway o Render)

### Opción A: Railway (recomendado, más fácil)
1. Ve a https://railway.app
2. Crea una cuenta gratis con GitHub
3. Clic en **"New Project"** → **"Deploy from GitHub"**
4. Sube tu proyecto a un repositorio GitHub primero
5. Railway detecta Laravel automáticamente
6. Agrega una base de datos MySQL desde el panel
7. Copia las variables de entorno de la BD al `.env`
8. ¡Listo! Te da una URL pública gratis

### Opción B: Netlify (solo para el frontend HTML)
- Solo sube el archivo `jc-investments.html` para la landing
- El backend necesita un servidor PHP (Railway o Render)

---

## 🛠️ Comandos útiles de Laravel

```bash
# Ver todas las rutas del sistema
php artisan route:list

# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Crear un nuevo controlador
php artisan make:controller NombreController

# Crear un nuevo modelo con migración
php artisan make:model NombreModelo -m

# Ver logs de errores
tail -f storage/logs/laravel.log
```

---

## 📋 Estructura del proyecto

```
jc-investments/
├── app/
│   ├── Http/
│   │   ├── Controllers/    ← Lógica de cada módulo
│   │   └── Middleware/     ← Control de roles y acceso
│   └── Models/             ← Modelos de la base de datos
├── database/
│   ├── migrations/         ← Estructura de tablas SQL
│   └── seeders/            ← Datos iniciales de prueba
├── resources/
│   └── views/              ← Vistas HTML (Blade templates)
├── routes/
│   └── web.php             ← Todas las rutas del sistema
├── public/                 ← Archivos públicos (CSS, JS, imágenes)
└── .env                    ← Configuración del entorno
```

---

## ❓ Solución de problemas comunes

| Error | Solución |
|-------|---------|
| `Access denied for user 'root'` | Verifica usuario/contraseña en `.env` |
| `Class not found` | Ejecuta `composer dump-autoload` |
| `View not found` | Verifica que el archivo existe en `resources/views` |
| `419 Page Expired` | Agrega `@csrf` a todos los formularios |
| Puerto 8000 ocupado | Usa `php artisan serve --port=8001` |

---

¿Tienes alguna duda? Comparte el error exacto y te ayudo a resolverlo. 🚀
