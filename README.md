# Sistema Web de Gestión Inmobiliaria

Monorepo base para un sistema de gestión inmobiliaria con una API REST en
Laravel y una aplicación React independiente.

## Estructura

```text
backend/    API REST Laravel 12
frontend/   React + TypeScript + Vite + Tailwind CSS
docs/       Requisitos y documentación del proyecto
scripts/    Scripts auxiliares del proyecto
```

El backend y el frontend se mantienen separados. React se compila a archivos
estáticos para producción; no se requiere Node/Vite ejecutándose de forma
permanente en el servidor de producción.

## Requisitos locales

- PHP 8.2+
- Composer
- Node.js 20.19+ y npm
- Git
- SQLite para las pruebas iniciales

La conexión definitiva a MySQL/MariaDB queda pendiente de una fase posterior.

## Instalación

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan test
```

### Frontend

```bash
cd frontend
npm install
npm run lint
npm run build
```

## Desarrollo

En terminales separadas:

```bash
cd backend && php artisan serve
cd frontend && npm run dev
```

La API base queda disponible en `http://localhost:8000/api/health` y Vite en
`http://localhost:5173`.

## Estado de FASE 0

Esta fase contiene únicamente el andamiaje técnico. No incluye autenticación,
Sanctum, usuarios de negocio, clientes, inmuebles, citas, reportes, Firebase,
notificaciones ni módulos funcionales.
