# Backend

API REST base construida con Laravel 12. En FASE 0 solo expone endpoints de
salud y conserva la infraestructura mínima del framework.

## Comandos

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan test
composer run dev
```

El servidor de desarrollo aplica límites de subida de 10 MB por imagen y 12 MB
por solicitud. Tanto `composer run dev` como `php artisan serve` aplican estos
límites automáticamente:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

La verificación funcional está disponible en `GET /api/health`.

La conexión MySQL/MariaDB, Sanctum y las entidades del negocio se configurarán
en fases posteriores.
