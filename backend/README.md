# Backend

API REST base construida con Laravel 12. En FASE 0 solo expone endpoints de
salud y conserva la infraestructura mínima del framework.

## Comandos

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan test
php artisan serve
```

La verificación funcional está disponible en `GET /api/health`.

La conexión MySQL/MariaDB, Sanctum y las entidades del negocio se configurarán
en fases posteriores.
