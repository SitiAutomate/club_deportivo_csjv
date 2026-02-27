# Inscripciones Club

Sistema de inscripciones para cursos, campamentos y salidas.

## Requisitos

- PHP 7.4+
- MySQL/MariaDB
- Composer

## Instalación

1. Clonar o copiar el proyecto.
2. `composer install`
3. **Variables de entorno**: Copiar `.env.example` como `.env` en la **raíz del proyecto** (junto a `composer.json`) y configurar:
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` – Base de datos
   - `SHOW_CUPOS=true` – Opcional: mostrar cupos disponibles en el listado de cursos
4. Alternativa: usar `config/database.local.php` para credenciales de BD.
5. Crear la base de datos y ejecutar `sql/schema.sql`.
6. En Hostinger, configurar la raíz web como `public_html/`.

## Uso

Acceder a `public_html/index.php` (o la URL configurada). El flujo del formulario:

1. **Participante**: Ingrese documento → Validar. Si no existe, se abre un modal para registrarlo.
2. **Responsable**: Ingrese documento → Validar. Si no es el asignado, se ofrece cambiar o registrar uno nuevo.
3. **Tipo**: Seleccione Cursos, Campamentos o Salidas. Los campos se cargan dinámicamente según el tipo.
4. Enviar inscripción.

## Estructura

- `config/` – Configuración de BD
- `includes/` – Bootstrap y utilidades
- `models/` – Modelos (Participante, Responsable, Inscripcion, etc.)
- `public_html/` – Página principal, favicon y endpoints AJAX. `logo.png` en `public_html/assets/images/`
- `sql/` – Esquema de base de datos
