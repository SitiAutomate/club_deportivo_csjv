# Despliegue en Hostinger (Git)

## Antes de subir a Git

1. **No subir archivos sensibles**
   - `.env` está en `.gitignore` (credenciales, APP_URL, etc.)
   - Usar `.env.example` como plantilla

2. **Instalar dependencias en producción**
   - Hostinger suele ejecutar `composer install` o debes subir la carpeta `vendor/`
   - Si `.gitignore` excluye `vendor/`, ejecutar `composer install --no-dev` en el servidor o antes de subir

## Pasos para Hostinger

### 1. Conectar repositorio

- En el panel de Hostinger: **Avanzado → Git**
- Conectar el repo y elegir la rama (ej. `main`)
- Seleccionar la carpeta de despliegue (normalmente `public_html` o la raíz del dominio)

### 2. Estructura esperada

Hostinger suele servir desde `public_html`. Si tu repo tiene la raíz con `public_html/` dentro:

- Opción A: Configurar el directorio raíz del sitio para que apunte a `public_html`
- Opción B: Subir/desplegar de forma que `public_html` sea el directorio raíz del dominio

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

### 7. Cache

- No hay cache de aplicación por defecto
- Para evitar caché antiguo de JS/CSS, usar versionado en las URLs: `app.css?v=1.0.2`

### 8. Verificar después del deploy

- [ ] Formulario carga correctamente
- [ ] Validación de participante/responsable
- [ ] Envío de inscripción
- [ ] Correo de confirmación
- [ ] `APP_URL` correcto para evitar errores 403 por origen
