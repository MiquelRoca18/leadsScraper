# Configuración de MapLeads Frontend

## Variables de Entorno

El archivo `.env` contiene la configuración de la aplicación. Si no existe, créalo con estos valores:

```env
# URL del backend MapLeads (Google Maps scraper)
MAPLEADS_API_URL=http://localhost:8001

# URL del backend InstaLeads (Instagram scraper)
INSTALEADS_API_URL=http://localhost:8002

# Puerto en el que escucha el frontend
PORT=8081
```

## Cambiar Configuración

### 1. URL de los Backends

Si los backends están en otro puerto o máquina, edita el `.env`:

```env
# Ejemplo: backends en otra máquina
MAPLEADS_API_URL=http://192.168.1.100:8001
INSTALEADS_API_URL=http://192.168.1.100:8002
```

### 2. Puerto de la Aplicación

Para ejecutar en otro puerto (p.ej., 8000):

```env
PORT=8000
```

Luego accede a `http://localhost:8000`

## Verificar Configuración

Para verificar que todo está correcto:

1. **Asegúrate que los backends están ejecutándose:**
   - MapLeads: `http://localhost:8001` (o tu URL configurada)
   - InstaLeads: `http://localhost:8002` (o tu URL configurada)

2. **Inicia el frontend:**
   ```bash
   python launcher.py
   ```

3. **Deberías ver en la consola:**
   ```
   ==================================================
     MapLeads - Frontend
   ==================================================

   Iniciando servidor en http://127.0.0.1:8081...
   ```

4. **Accede a la aplicación:**
   - El navegador abrirá automáticamente
   - O navega a tu URL configurada

## Resetear a Configuración Predeterminada

Si algo está roto, puedes resetear `.env` a los valores predeterminados:

```env
MAPLEADS_API_URL=http://localhost:8001
INSTALEADS_API_URL=http://localhost:8002
PORT=8081
```

Luego reinicia la aplicación.
