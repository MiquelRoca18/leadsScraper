# Scraper Lead

Herramienta para extraer emails de negocios locales desde Google Maps.

---

## Requisitos

- Python 3.11+

---

## Instalación

### 1. Backend (Google Maps scraper)

**Mac/Linux:**
```bash
cd mapleads
python3 -m venv venv
source venv/bin/activate
cp .env.example .env
pip install -r requirements.txt
```

**Windows:**
```cmd
cd mapleads
python -m venv venv
venv\Scripts\activate
copy .env.example .env
pip install -r requirements.txt
```

> Los proxies son de [Webshare](https://www.webshare.io/). Obtén tu lista desde el dashboard → Proxy List.

### 2. Frontend (FastAPI)

**Mac/Linux:**
```bash
cd scraperLead-web
cp .env.example .env
pip install -r requirements.txt
```

**Windows:**
```cmd
cd scraperLead-web
copy .env.example .env
pip install -r requirements.txt
```

El `.env` ya viene configurado con los valores por defecto. Edítalo si tus backends están en puertos diferentes:

```env
MAPLEADS_API_URL=http://localhost:8001
INSTALEADS_API_URL=http://localhost:8002
PORT=8081
```

---

## Arrancar el proyecto

### Windows

Doble clic en **`start_mapleads.bat`** (raíz del proyecto). Inicia el backend y el frontend juntos automáticamente.

> Antes de usarlo por primera vez, compila el frontend: entra en `scraperLead-web` y ejecuta `build_exe.bat`.

### Mac/Linux

Abre **2 terminales**:

**Terminal 1 — Backend:**
```bash
cd mapleads
source venv/bin/activate
uvicorn backend.main:app --port 8001
```

**Terminal 2 — Frontend:**
```bash
cd scraperLead-web
python launcher.py
```

El navegador se abrirá automáticamente en **http://localhost:8081**

---

## Notas

- La base de datos SQLite se crea automáticamente en `mapleads/data/mapleads.db` al arrancar.
- El módulo de **Instagram no está operativo** en esta versión. No hace falta arrancarlo.
