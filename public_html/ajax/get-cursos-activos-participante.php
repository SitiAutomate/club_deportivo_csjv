<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$participanteDoc = trim($_GET['participante_id'] ?? $_GET['documento'] ?? '');
$anio = (int) ($_GET['anio'] ?? date('Y'));

if ($participanteDoc === '') {
    jsonResponse(['success' => true, 'cursos' => []]);
    exit;
}

$mesActual = (int) date('n');
$mesSiguiente = $mesActual >= 12 ? 1 : $mesActual + 1;
$meses = [
    str_pad((string) $mesActual, 2, '0', STR_PAD_LEFT),
    str_pad((string) $mesSiguiente, 2, '0', STR_PAD_LEFT)
];

$estadosValidos = ['ACTIVO', 'Confirmado', 'confirmado', 'Incapacitado', 'incapacitado'];

$rows = $database->select('inscripciones_1', [
    'IDCurso',
    'nombreCurso',
    'Mes',
    'Sede'
], [
    'validador_participante' => $participanteDoc,
    'Mes' => $meses,
    'Estado' => $estadosValidos,
    'año' => $anio,
    'Tipo' => 1
]);

$nombresMes = [
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
];

$cursos = array_map(function ($r) use ($nombresMes) {
    return [
        'id' => $r['IDCurso'],
        'nombre' => $r['nombreCurso'] ?? $r['IDCurso'],
        'mes' => $r['Mes'],
        'mes_nombre' => $nombresMes[$r['Mes'] ?? ''] ?? $r['Mes'],
        'sede' => $r['Sede'] ?? ''
    ];
}, $rows);

jsonResponse(['success' => true, 'cursos' => $cursos]);
