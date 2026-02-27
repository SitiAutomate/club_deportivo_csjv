# Plantillas por tipo e ID

Sistema dinámico de plantillas HTML: `templates/{tipo_id}/{item_id}.html`

## Estructura

- **tipo_id**: ID del tipo de inscripción (5=salidas, 14=otro tipo con templates, etc.)
- **item_id**: ID del curso/salida/campamento seleccionado

## Ejemplos

- `templates/5/5112.html` → detalle de la salida con ID 5112
- `templates/14/1.html` → Future Makers (renombrar a `{ID_Curso}.html` si el ID es distinto)

## Configuración

En `config/tipos_inscripcion.php` agregue `'tieneTemplate' => true` al tipo que use plantillas.
