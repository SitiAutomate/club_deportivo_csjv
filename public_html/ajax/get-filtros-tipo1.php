<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/tipo1_gate.php';

header('Content-Type: application/json; charset=utf-8');

$tipoId = isset($_GET['tipo_id']) ? (int) $_GET['tipo_id'] : 1;

$mes = new Mes($database);
$todos = $mes->getAll();
$numsOfrecer = tipo1MesesSelector();
$mesDefault = tipo1MesDefault();
$anio = tipo1AnioInscripcionDefault();

$meses = [];
foreach ($todos as $m) {
    $num = str_pad((string) ($m['NumMes'] ?? ''), 2, '0', STR_PAD_LEFT);
    if (in_array($num, $numsOfrecer, true)) {
        $meses[] = [
            'id' => $num,
            'NumMes' => $num,
            'Mes' => $m['Mes'] ?? '',
            'Periodo' => $m['Periodo'] ?? '',
        ];
    }
}

// Mantener el orden enero/febrero o actual/siguiente según numsOfrecer.
if ($meses !== []) {
    $porNum = [];
    foreach ($meses as $row) {
        $porNum[$row['NumMes']] = $row;
    }
    $mesesOrdenados = [];
    foreach ($numsOfrecer as $n) {
        if (isset($porNum[$n])) {
            $mesesOrdenados[] = $porNum[$n];
        }
    }
    $meses = $mesesOrdenados !== [] ? $mesesOrdenados : $meses;
}

if (empty($meses)) {
    $nombres = [
        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
        '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
        '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
    ];
    foreach ($numsOfrecer as $n) {
        $meses[] = [
            'id' => $n,
            'NumMes' => $n,
            'Mes' => $nombres[$n] ?? $n,
            'Periodo' => '',
        ];
    }
}

$linea = new Linea($database);
$lineas = $linea->getAll();

$actividad = new Actividad($database);
if ($tipoId === 1) {
    $actividades = $actividad->getActivasParaCursosTipo1();
} else {
    $actividades = $actividad->getActivas();
}

jsonResponse([
    'success' => true,
    'meses' => $meses,
    'mes_default' => $mesDefault,
    'anio' => $anio,
    'usa_enero_default' => tipo1DebeUsarEneroDefault(),
    'lineas' => $lineas,
    'actividades' => $actividades,
]);
