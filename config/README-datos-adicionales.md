# Configuración de datos adicionales

Edite `config/datos_adicionales.php` para definir los campos de la sección "4. Datos adicionales".

## Opciones por campo

- **enabled**: `true` o `false` — activa o desactiva el campo
- **required**: `true` o `false` — campo obligatorio
- **column**: columna en la tabla `inscripciones_1` donde se guarda el valor
- **type**: `checkbox` | `text` | `textarea` | `select` | `select_si_no_text`

## Tipos

- **checkbox**: valor "Sí" cuando está marcado
- **text** / **textarea**: valor directo del input
- **select**: valor de la opción elegida
- **select_si_no_text**: select Sí/No + textarea que se habilita con "Sí"

## Nuevas columnas

Si asigna un campo a una columna que no existe en `inscripciones_1`, debe crearla:

```sql
ALTER TABLE inscripciones_1 ADD COLUMN mi_campo VARCHAR(200) NULL;
```
