# Despliegue en Hostinger (Git)

## Antes de subir a Git

1. **No subir archivos sensibles**
   - `.env` está en `.gitignore` (credenciales, APP_URL, etc.)
   - Usar `.env.example` como plantilla

2. **Excluidos en `.gitignore`**
   - `sql/`, `COLUMNS.json`, `bd.md` (no deben subirse)
   - Si `sql/` ya estaba en el repo: `git rm -r --cached sql/` y commit

3. **Instalar dependencias en producción**
   - Hostinger suele ejecutar `composer install` o debes subir la carpeta `vendor/`
   - Si `.gitignore` excluye `vendor/`, ejecutar `composer install --no-dev` en el servidor o antes de subir

## Pasos para Hostinger

### 1. Conectar repositorio

- En el panel de Hostinger: **Avanzado → Git**
- Conectar el repo y elegir la rama (ej. `main`)
- **Carpeta de despliegue:** Usar la raíz del dominio o una carpeta que contenga todo el repo (p. ej. `domains/tudominio.com/` o la carpeta padre de `public_html`)

### 2. Estructura y directorio raíz (IMPORTANTE)

El proyecto tiene esta estructura:

```
raíz/
├── public_html/     ← Solo esto debe ser accesible por web
│   ├── index.php
│   ├── assets/
│   └── ajax/
├── includes/
├── models/
├── config/
├── templates/
└── vendor/
```

Si Hostinger clona el repo dentro de `public_html`, quedaría mal: `includes/`, `models/`, `config/` quedarían expuestos.

**Solución:** Configurar el **directorio raíz (document root)** del sitio:

1. En Hostinger: **Dominios** → tu dominio → **Document root** / **Raíz del sitio**
2. Cambiarlo a la subcarpeta `public_html` dentro de donde se despliega el repo
   - Ejemplo: si Git despliega en `domains/tudominio.com/`, el document root debe ser `domains/tudominio.com/public_html`
   - O si despliega en `public_html/`, cambiar el document root a `public_html/public_html` (la carpeta interna)

Así solo `public_html/` es accesible por web; `includes/`, `models/`, `config/` quedan fuera del alcance del servidor web.

**Alternativa:** Si Hostinger permite elegir la carpeta de deploy, desplegar en una carpeta *padre* (p. ej. `inscripciones/`) y poner el document root en `inscripciones/public_html`.

### 3. Variables de entorno

Crear el archivo `.env` en la raíz del proyecto (fuera de Git):

```bash
cp .env.example .env
# Editar .env con credenciales reales de Hostinger
```

Variables necesarias:

- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `APP_URL` (URL pública, ej. `https://inscripciones.tudominio.com`) – importante para CSRF/Origin
- `EMAIL_HOST`, `EMAIL_USER`, `EMAIL_PASS` (para correos de confirmación)

### 4. Composer

```bash
composer install --no-dev --optimize-autoloader
```

### 5. Permisos

- Carpeta `vendor/`: lectura
- `.env`: lectura (y permisos que impidan acceso desde la web)
- `public_html/`: según la configuración del hosting

### 6. Base de datos

- Crear la BD en el panel de Hostinger
- Ejecutar migraciones o scripts SQL necesarios
- Configurar las credenciales en `.env`

### 7. Cache y actualizaciones

- **PHP / HTML:** Los usuarios ven los cambios al refrescar; no hay cache de aplicación por defecto.
- **JS / CSS:** El proyecto usa versionado automático (`?v=timestamp`) en `app.css` e `inscripcion.js`, así que al desplegar un cambio nuevo, el navegador descarga la versión actualizada sin pedir a los usuarios que borren la caché.
- Si no se ve un cambio: recargar con Ctrl+Shift+R (o Cmd+Shift+R en Mac) fuerza recarga sin caché.

### 8. Verificar después del deploy

- [ ] Formulario carga correctamente
- [ ] Validación de participante/responsable
- [ ] Envío de inscripción
- [ ] Correo de confirmación
- [ ] `APP_URL` correcto para evitar errores 403 por origen
