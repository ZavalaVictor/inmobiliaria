# SotyTech · Sitio público de propiedades

Aplicación pública separada del panel administrativo.

## Desarrollo local

El backend debe estar disponible en `http://127.0.0.1:8000`.

```bash
cd frontend-public
npm run dev
```

La aplicación queda disponible en `http://127.0.0.1:5174`.

El panel administrativo continúa en `http://localhost:5173` y el enlace
“Acceso administrativo” dirige a su login.

## Rutas públicas

- `/` y `/propiedades`: catálogo público con búsqueda, ubicación, tipo de inmueble,
  operación, rango de precio, orden y paginación.
- `/propiedades/:slug`: detalle público, galería, características y formulario de contacto.
- `/aviso-de-privacidad`: información visible sobre el uso de los datos del formulario.

La app consume exclusivamente los endpoints públicos de la API y no requiere
autenticación de administrador.
