<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$tipoId = isset($_GET['tipo_id']) ? (int) $_GET['tipo_id'] : null;
$lineaId = isset($_GET['linea']) ? (int) $_GET['linea'] : null;
$actividad = new Actividad($database);

if ($tipoId === 1) {
    $actividades = $actividad->getActivasParaCursosTipo1($lineaId > 0 ? $lineaId : null);
} else {
    $actividades = $actividad->getActivas();
    if ($lineaId > 0) {
        $actividades = array_values(array_filter($actividades, function ($row) use ($lineaId) {
            return (int) ($row['IDNegocio'] ?? 0) === $lineaId;
        }));
    }
}

jsonResponse(['success' => true, 'actividades' => $actividades]);
