# Crear y Ejecutar el .exe en Windows

## Requisitos
- Python 3.11+ instalado
- Estar en la carpeta `scraperLead-web`

---

## Paso 1: Generar el .exe (Una sola vez)

1. **Abre PowerShell o CMD** en la carpeta `scraperLead-web`
2. **Ejecuta:**
   ```
   build_exe.bat
   ```
3. **Espera 2-5 minutos** (verás mensajes de compilación)
4. Cuando termine, verás: `scraperLead-web/dist/MapLeads-Frontend/MapLeads-Frontend.exe`

---

## Paso 2: Ejecutar la aplicación

Simplemente **haz doble clic en `start_mapleads.bat`** (en la raíz del proyecto)

- Inicia el backend (scraper) y el frontend juntos
- Se abrirá automáticamente el navegador en `http://localhost:8081`
- Se abrirá una ventana con el backend (no la cierres mientras uses la app)
- Para cerrar: cierra ambas ventanas

---

## Configuración

Si tus backends están en otros puertos, edita `scraperLead-web/.env`:

```env
MAPLEADS_API_URL=http://localhost:8001
INSTALEADS_API_URL=http://localhost:8002
PORT=8081
```

Después reinicia la app con `run_mapleads.bat`.

---

## Distribución

Puedes copiar la carpeta `dist/MapLeads-Frontend` a otros ordenadores y ejecutar `run_mapleads.bat` directamente, sin necesidad de compilar de nuevo.
