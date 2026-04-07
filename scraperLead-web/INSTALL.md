# Guía de Instalación - MapLeads Frontend

## Para Usuarios No-Técnicos

Sigue estos pasos para instalar y ejecutar MapLeads en tu computadora Windows.

### Paso 1: Preparación Inicial (Una sola vez)

1. **Abre la carpeta `scraperLead-web`**
   - Deberías ver archivos como `README.md`, `main.py`, `build_exe.bat`, etc.

2. **Abre PowerShell o CMD en esta carpeta**
   - En Windows 11: Haz clic derecho en la carpeta → "Abrir en Terminal"
   - En Windows 10: Mantén Shift + Clic derecho → "Abrir PowerShell aquí"
   - O abre CMD y navega a la carpeta

3. **Instala las dependencias** (copia y pega este comando)
   ```
   python -m pip install -r requirements.txt
   ```
   - Espera a que termine (verás mensajes de instalación)

### Paso 2: Generar el Ejecutable (Una sola vez)

1. **Genera el .exe** (copia y pega este comando)
   ```
   pyinstaller build_mapleads.spec
   ```
   - Esto puede tardar 2-5 minutos
   - Verás mensajes como "Building EXE from EXE-00.toc"
   - Cuando termine, verás "Successfully generated"

2. **Verifica que se creó correctamente**
   - Deberías ver una carpeta nueva llamada `dist` en el directorio actual
   - Estructura completa:
     ```
     scraperLead-web/
     ├── dist/
     │   └── MapLeads-Frontend/
     │       └── MapLeads-Frontend.exe  ← Tu aplicación
     ├── run_mapleads.bat
     ├── main.py
     └── ...
     ```

### Paso 3: Ejecutar la Aplicación

#### Forma Fácil (Recomendado):
- Simplemente haz **doble clic en `run_mapleads.bat`**
- La aplicación se abrirá automáticamente

#### Forma Manual:
- Navega a `dist/MapLeads-Frontend/`
- Haz doble clic en `MapLeads-Frontend.exe`
- Se abrirá una ventana de consola y el navegador

### Paso 4: Usar la Aplicación

1. **Tu navegador abrirá automáticamente** en `http://localhost:8081`
2. **Si no se abre**, abre tu navegador favorito y escribe en la barra de direcciones:
   ```
   http://localhost:8081
   ```

3. **Deberías ver la página de inicio de MapLeads**

### Paso 5: Detener la Aplicación

- Presiona **CTRL + C** en la ventana de consola
- O simplemente cierra la ventana de consola

---

## Solución de Problemas

### ❌ "No se encuentra el comando 'python'"
**Solución:** Python no está instalado o no está en PATH
- Instala Python desde [python.org](https://www.python.org)
- Asegúrate de marcar "Add Python to PATH" durante la instalación
- Reinicia PowerShell/CMD

### ❌ "ModuleNotFoundError: No module named 'fastapi'"
**Solución:** Las dependencias no se instalaron correctamente
- Ejecuta nuevamente:
  ```
  python -m pip install -r requirements.txt
  ```
- Espera a que termine completamente

### ❌ "No se encuentra MapLeads-Frontend.exe"
**Solución:** El .exe no se compiló correctamente
- Verifica que no hay errores en la salida de `pyinstaller`
- Intenta compilar de nuevo
- Si persiste, ejecuta:
  ```
  python launcher.py
  ```
  (esto ejecutará la app directamente sin .exe)

### ❌ "Port 8081 is already in use"
**Solución:** Otro programa ya está usando ese puerto
- Cierra la otra aplicación que usa el puerto 8081
- O edita `.env` y cambia `PORT=8081` a otro número (p.ej., `PORT=8082`)

### ❌ El navegador no se abre automáticamente
**Solución:** Abre manualmente
- Abre tu navegador (Chrome, Firefox, Edge, etc.)
- Escribe en la barra de direcciones: `http://localhost:8081`

### ❌ Veo la aplicación pero muestra errores
**Solución:** Los backends no están corriendo
- MapLeads API debe estar ejecutándose en `http://localhost:8001`
- InstaLeads API debe estar ejecutándose en `http://localhost:8002`
- Verifica que ambos backends están activos

---

## Preguntas Frecuentes

**P: ¿Debo generar el .exe cada vez que inicio?**
A: No, solo una vez. Después, haz doble clic en `run_mapleads.bat`

**P: ¿Puedo compartir el .exe con otras personas?**
A: Sí, puedes copiar la carpeta `dist/MapLeads-Frontend` a otra computadora y usar `run_mapleads.bat`

**P: ¿Necesito conexión a internet?**
A: Solo para el CSS (Tailwind via CDN). Si no hay internet, el estilo será básico pero la aplicación funcionará

**P: ¿Se puede cerrar la ventana de consola?**
A: No, ciérrala solo cuando quieras detener la aplicación

**P: ¿Cómo cambio la URL del backend?**
A: Edita `.env` y cambia `MAPLEADS_API_URL` e `INSTALEADS_API_URL`

---

## Contacto

Si tienes problemas, contacta al equipo de desarrollo.
