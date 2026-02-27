<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$tipoId = isset($_GET['tipo_id']) ? (int) $_GET['tipo_id'] : null;

if ($tipoId === 1) {
    // Solo actividades asociadas a cursos tipo 1
    $actividadIds = $database->select('cursos_2025', 'Actividad', [
        'Estado_del_curso' => 'ACTIVO',
        'Tipo' => 1
    ]);
    $actividadIds = array_unique(array_filter(array_map('intval', $actividadIds)));
    if (empty($actividadIds)) {
        jsonResponse(['success' => true, 'actividades' => []]);
        exit;
    }
    $actividades = $database->select('actividades', ['IDActividad', 'Nombre_Actividad', 'CC', 'ESTADO'], [
        'IDActividad' => $actividadIds,
        'ESTADO' => 'ACTIVO',
        'ORDER' => 'Nombre_Actividad'
    ]);
} else {
    $actividad = new Actividad($database);
    $actividades = $actividad->getActivas();
}

jsonResponse(['success' => true, 'actividades' => $actividades]);
