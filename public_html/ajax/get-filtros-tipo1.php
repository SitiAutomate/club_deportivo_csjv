<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$tipoId = isset($_GET['tipo_id']) ? (int) $_GET['tipo_id'] : 1;

$mes = new Mes($database);
$todos = $mes->getAll();
$mesActual = (int) date('n');
$mesSiguiente = $mesActual >= 12 ? 1 : $mesActual + 1;
$numActual = str_pad((string) $mesActual, 2, '0', STR_PAD_LEFT);
$numSiguiente = str_pad((string) $mesSiguiente, 2, '0', STR_PAD_LEFT);
$meses = [];
foreach ($todos as $m) {
    $num = (string) $m['NumMes'];
    if ($num === $numActual || $num === $numSiguiente) {
        $meses[] = ['id' => $num, 'NumMes' => $num, 'Mes' => $m['Mes'] ?? '', 'Periodo' => $m['Periodo'] ?? ''];
    }
}
if (empty($meses)) {
    $meses = [
        ['id' => $numActual, 'NumMes' => $numActual, 'Mes' => date('F', mktime(0, 0, 0, $mesActual, 1)), 'Periodo' => ''],
        ['id' => $numSiguiente, 'NumMes' => $numSiguiente, 'Mes' => date('F', mktime(0, 0, 0, $mesSiguiente, 1)), 'Periodo' => '']
    ];
}

$linea = new Linea($database);
$lineas = $linea->getAll();

if ($tipoId === 1) {
    $actividadIds = $database->select('cursos_2025', 'Actividad', [
        'Estado_del_curso' => 'ACTIVO',
        'Tipo' => 1
    ]);
    $actividadIds = array_unique(array_filter(array_map('intval', $actividadIds)));
    $actividades = empty($actividadIds) ? [] : $database->select('actividades', ['IDActividad', 'Nombre_Actividad', 'CC', 'ESTADO'], [
        'IDActividad' => $actividadIds,
        'ESTADO' => 'ACTIVO',
        'ORDER' => 'Nombre_Actividad'
    ]);
} else {
    $actividad = new Actividad($database);
    $actividades = $actividad->getActivas();
}

jsonResponse([
    'success' => true,
    'meses' => $meses,
    'lineas' => $lineas,
    'actividades' => $actividades
]);
