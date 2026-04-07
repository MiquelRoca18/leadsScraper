# Scraper Lead

Herramienta para extraer emails de negocios locales desde Google Maps.

---

## Requisitos

- Python 3.11+
- PHP 8.2+ y Composer
- Node.js 18+

---

## Instalación

### 1. Backend (Google Maps scraper)

```bash
cd mapleads
python3 -m venv venv
source venv/bin/activate
cp .env.example .env
pip install -r requirements.txt
```

> Los proxies son de [Webshare](https://www.webshare.io/). Obtén tu lista desde el dashboard → Proxy List.

### 2. Frontend (Laravel)

```bash
cd scraperLead-web
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Añade al `.env` de Laravel:

```env
APP_URL=http://localhost:8081
MAPLEADS_API_URL=http://localhost:8001
```

---

## Arrancar el proyecto

Abre **3 terminales**:

**Terminal 1 — Backend:**
```bash
cd mapleads
source venv/bin/activate
uvicorn backend.main:app --reload --port 8001
```

**Terminal 2 — Assets (Vite):**
```bash
cd scraperLead-web
npm run dev
```

**Terminal 3 — Frontend:**
```bash
cd scraperLead-web
php artisan serve --port 8081
```

Abre el navegador en **http://localhost:8081**

---

## Notas

- La base de datos SQLite se crea automáticamente en `mapleads/data/mapleads.db` al arrancar.
- El módulo de **Instagram no está operativo** en esta versión. No hace falta arrancarlo.
