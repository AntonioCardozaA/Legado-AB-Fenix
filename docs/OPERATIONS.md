# Operacion

Notas para instalar, levantar y mantener el proyecto sin alterar el flujo actual.

## Arranque Local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

En Windows PowerShell:

```powershell
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

## Desarrollo

```bash
composer run dev
```

Ese script levanta servidor Laravel, Vite, listener de cola y logs.

## Verificacion

```bash
php artisan test
npm run build
php artisan route:list --except-vendor
```

## Scheduler

El scheduler esta definido en `routes/console.php`.

En produccion debe ejecutarse cada minuto:

```bash
php artisan schedule:run
```

Comandos relacionados:

```bash
php artisan elongaciones:send-reminders --dry-run
php artisan notifications:send-activities
php artisan componentes:reset-estadisticas --simular
```

## Storage

Las evidencias se sirven desde `public/storage`, por eso se requiere:

```bash
php artisan storage:link
```

Si las imagenes no cargan, revisar:

- Que el enlace simbolico exista.
- Que `APP_URL` sea correcto.
- Que el servidor permita servir `public/storage`.

## Respaldos

Los respaldos SQL o ZIP no deben vivir versionados en Git. Guardarlos fuera del repositorio o en una ubicacion privada.
