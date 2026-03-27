# Task 9 - Decisiones finales de verificacion

## Contrato del proxy

- El backend Laravel expone un proxy restringido con rutas permitidas (`/api/search`, `/api/jobs/{jobId}`, `/api/leads`, `/api/leads/{leadId}`, `/api/export/{jobId}`, `/api/proxy/status`, `/api/proxy/capacity`).
- Se bloquean metodos/path/headers fuera de la lista permitida.
- Ante timeout o error de upstream, el contrato devuelve `502` consistente para consumo del frontend.

## Politica CSRF

- La app mantiene excepcion CSRF para `api/*` en `bootstrap/app.php`.
- Esto permite llamadas `fetch` desde las vistas actuales sin inyectar token en cada request.
- Se deja registrado como deuda tecnica de hardening: migrar a token explicito cuando se cierre el ajuste del proxy/API.

## Contrato de busqueda geografica

- El frontend envia `query`, `location`, `max_results` y, cuando aplica, `lat`, `lng`, `radius_km`.
- La UI mantiene geocodificacion/reverse geocodificacion y comunica estados de backend no disponible sin romper render.
- Los estados de error upstream se muestran con CTA de reintento en home, search, leads e historial.

## Modulos JS actuales (frontend)

- `resources/js/modules/search-form.js`: formulario de busqueda, mapa, polling, resultados, export.
- `resources/js/modules/home-page.js`: carga de jobs y estado vacio/error en home.
- `resources/js/modules/leads-page.js`: tabla de leads, paginacion y acciones relacionadas.
- `resources/js/lib/dom-utils.js`: utilidades seguras de renderizado y sanitizacion de texto/URL.

## Cierre de Task 9

- Verificacion final ejecutada: `php artisan test` y `npm run build` en verde.
- Anti-patrones de `innerHTML`/`onclick` eliminados en `resources/`.
- Se conserva `validateCsrfTokens(except: ['api/*'])` como excepcion documentada.
