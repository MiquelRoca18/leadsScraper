# MapLeads - Frontend

Frontend de Scraper Lead — construido con **FastAPI + Jinja2**.

## Requisitos

- Python 3.11+ (debe estar instalado en tu sistema)

## Instalación Rápida (Usuarios No-Técnicos)

### Opción 1: Usando el Ejecutable (.exe) — Recomendado

**Paso 1: Generar el ejecutable**
1. Abre `PowerShell` o `CMD` en este directorio
2. Ejecuta:
   ```bash
   python -m pip install -r requirements.txt
   pyinstaller build_mapleads.spec
   ```
3. Espera a que termine (puede tardar 2-5 minutos)

**Paso 2: Ejecutar la aplicación**
- Haz doble clic en `run_mapleads.bat`
- La aplicación se abrirá automáticamente en tu navegador en `http://localhost:8081`

**Paso 3: Detener la aplicación**
- Presiona `CTRL+C` en la ventana de consola o ciérrala

> El ejecutable se genera una única vez. Después puedes compartir `run_mapleads.bat` a otros usuarios sin necesidad de compilar de nuevo.

### Opción 2: Ejecutar en Desarrollo

**Instalación:**
```bash
pip install -r requirements.txt
```

**Configuración:**
Edita el archivo `.env` con las URLs de los backends:

```env
MAPLEADS_API_URL=http://localhost:8001
INSTALEADS_API_URL=http://localhost:8002
PORT=8081
```

**Arranque:**

Con recarga automática en desarrollo:
```bash
uvicorn main:app --reload --port 8081
```

O usando el launcher (abre el navegador automáticamente):
```bash
python launcher.py
```

Abre `http://localhost:8081` en tu navegador.

## Estructura

```
scraperLead-web/
├── launcher.py              # Script para iniciar la app (abre navegador)
├── main.py                  # App FastAPI: rutas, handlers y proxy
├── build_mapleads.spec      # Configuración de PyInstaller
├── build_exe.bat            # Script batch para compilar .exe
├── run_mapleads.bat         # Script batch para ejecutar .exe
├── requirements.txt         # Dependencias Python
├── .env                     # Variables de entorno
├── templates/               # HTML con Jinja2
│   ├── base.html           # Layout base (sidebar + nav)
│   ├── home.html           # Dashboard
│   ├── search.html         # Búsqueda en Google Maps
│   ├── leads.html          # Base de datos de leads
│   ├── history.html        # Historial de búsquedas
│   ├── databases.html      # Resumen de bases de datos
│   └── instagram.html      # Extracción de Instagram
└── static/
    └── js/                  # Módulos JavaScript
        ├── app.js          # Entry point
        ├── bootstrap.js
        ├── lib/
        │   ├── dom-utils.js
        │   └── proxy-state.js
        └── modules/
            ├── search-form.js
            ├── instagram-form.js
            └── proxy-status.js
```

## Solución de Problemas

### El .exe no se abre / dice que falta un módulo
- Asegúrate de que ejecutaste `pyinstaller build_mapleads.spec` completamente
- Si ves errores, ejecuta nuevamente el script batch

### La aplicación se abre pero no carga el contenido
- Verifica que los backends están corriendo:
  - MapLeads API: `http://localhost:8001`
  - InstaLeads API: `http://localhost:8002`
- Edita `.env` con las URLs correctas si es necesario

### El navegador no se abre automáticamente
- Abre manualmente `http://localhost:8081` en tu navegador
- El servidor estará disponible en ese puerto

## Para Desarrolladores

**Desarrollo con recarga automática:**
```bash
uvicorn main:app --reload --port 8081
```

**Formateo de código:**
```bash
pip install black
black main.py launcher.py
```

**Compilar .exe:**
```bash
pip install pyinstaller
pyinstaller build_mapleads.spec
```

El ejecutable se genera en `dist/MapLeads-Frontend/MapLeads-Frontend.exe`
