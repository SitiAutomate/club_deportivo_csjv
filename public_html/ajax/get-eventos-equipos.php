<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/eventos_equipos.php';

header('Content-Type: application/json; charset=utf-8');

$cursoModel = new Curso($database);
$items = [];
$schemas = [];

foreach (eventosEquiposActivos() as $id => $evento) {
    $row = $database->get('cursos_2025', [
        'ID_Curso',
        'Nombre_del_curso',
        'Nombre_Corto_Curso',
        'Tarifa_Curso',
        'Fecha_Inicio',
        'Fecha_Final',
        'Tipo',
        'Estado_del_curso',
    ], ['ID_Curso' => $id]);

    // Si el curso aún no está en BD, igual se expone desde config (útil en staging).
    $nombreCfg = (string) ($evento['nombre'] ?? $id);
    $nombre = $nombreCfg;
    $tarifa = '';
    $fechaInicio = '';
    $fechaFin = '';
    $tipoId = (int) ($evento['tipo_id'] ?? 18);

    if ($row) {
        $nombreDb = trim((string) ($row['Nombre_del_curso'] ?? $row['Nombre_Corto_Curso'] ?? ''));
        if ($nombreDb !== '') {
            $nombre = $nombreDb;
        }
        $tarifa = (string) ($row['Tarifa_Curso'] ?? '');
        $fechaInicio = (string) ($row['Fecha_Inicio'] ?? '');
        $fechaFin = (string) ($row['Fecha_Final'] ?? '');
        if (!empty($row['Tipo'])) {
            $tipoId = (int) $row['Tipo'];
        }
    }

    $schema = eventosEquiposSchemaPublico($evento);
    $schema['tipo_id'] = $tipoId;
    $schemas[$id] = $schema;

    $detalle = $evento['detalle'] ?? null;
    $info = (string) ($evento['info_corta'] ?? '');
    if (is_array($detalle)) {
        $parts = array_filter([
            $detalle['fecha'] ?? null,
            isset($detalle['hora']) ? 'Hora: ' . $detalle['hora'] : null,
            $detalle['lugar'] ?? null,
        ]);
        if ($parts) {
            $info = implode(' · ', $parts);
        }
    }

    $items[] = [
        'id' => (string) $id,
        'nombre' => $nombre,
        'nombre_display' => $nombre,
        'tarifa' => $tarifa,
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
        'modo' => (string) ($evento['modo'] ?? ''),
        'tipo_id' => $tipoId,
        'info_corta' => $info,
        'schema' => $schema,
        'en_bd' => (bool) $row,
    ];
}

$defaultId = '';
if (count($items) === 1) {
    $defaultId = (string) $items[0]['id'];
} elseif (isset($schemas['1817'])) {
    $defaultId = '1817';
} elseif (isset($schemas['2001'])) {
    $defaultId = '2001';
} elseif (!empty($items)) {
    $defaultId = (string) $items[0]['id'];
}

jsonResponse([
    'success' => true,
    'items' => $items,
    'schemas' => $schemas,
    'default_id' => $defaultId,
]);
