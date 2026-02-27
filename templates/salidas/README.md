# Plantillas por salida (tipo 5)

**Actualización:** Se usa el sistema dinámico por tipo+id. Las plantillas de salidas están en:

- **Ubicación nueva:** `templates/5/{item_id}.html` (ej: `templates/5/5112.html`)

La carpeta `templates/salidas/` se mantiene por compatibilidad. Prefiera `templates/5/` para nuevas plantillas.

## Uso

1. Cree `templates/5/{ID}.html` con el ID de la salida.
2. Use la clase `template-detalle-card` en el contenedor principal para estilos consistentes.
3. El contenido se cargará automáticamente al seleccionar esa salida.
