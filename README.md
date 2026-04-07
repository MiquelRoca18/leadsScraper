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
pip install -r requirements.txt
```

Crea el archivo `.env` en `mapleads/`:

```env
PROXY_LIST=http://usuario:contraseña@ip:puerto,http://usuario:contraseña@ip2:puerto2
WEBSHARE_PROXY_USER=tu_usuario
WEBSHARE_PROXY_PASS=tu_contraseña
WEBSHARE_PROXY_HOST=proxy.webshare.io
WEBSHARE_PROXY_PORT=80
DB_PATH=./data/mapleads.db
LOG_LEVEL=INFO
MAX_REQUESTS_PER_PROXY_BEFORE_COOLDOWN=40
PROXY_COOLDOWN_SECONDS=360
MAX_CONCURRENT_REQUESTS=15
REQUEST_DELAY_MIN_SECONDS=0.5
REQUEST_DELAY_MAX_SECONDS=1.5
ERROR_RATE_THRESHOLD=0.30
HIGH_ERROR_COOLDOWN_SECONDS=600
MAX_REQUESTS_PER_DAY=10000
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
