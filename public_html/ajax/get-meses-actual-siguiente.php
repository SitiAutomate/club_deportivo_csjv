<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/tipo1_gate.php';

header('Content-Type: application/json; charset=utf-8');

$mes = new Mes($database);
$todos = $mes->getAll();

$numsOfrecer = tipo1MesesSelector();
$opciones = [];
foreach ($todos as $m) {
    $num = str_pad((string) ($m['NumMes'] ?? ''), 2, '0', STR_PAD_LEFT);
    if (in_array($num, $numsOfrecer, true)) {
        $opciones[] = [
            'id' => $num,
            'NumMes' => $num,
            'Mes' => $m['Mes'] ?? '',
            'Periodo' => $m['Periodo'] ?? '',
        ];
    }
}

if ($opciones !== []) {
    $porNum = [];
    foreach ($opciones as $row) {
        $porNum[$row['NumMes']] = $row;
    }
    $ordenadas = [];
    foreach ($numsOfrecer as $n) {
        if (isset($porNum[$n])) {
            $ordenadas[] = $porNum[$n];
        }
    }
    $opciones = $ordenadas !== [] ? $ordenadas : $opciones;
}

if (empty($opciones)) {
    $nombres = [
        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
        '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
        '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
    ];
    foreach ($numsOfrecer as $n) {
        $opciones[] = [
            'id' => $n,
            'NumMes' => $n,
            'Mes' => $nombres[$n] ?? $n,
            'Periodo' => '',
        ];
    }
}

jsonResponse([
    'success' => true,
    'meses' => $opciones,
    'mes_default' => tipo1MesDefault(),
    'anio' => tipo1AnioInscripcionDefault(),
    'usa_enero_default' => tipo1DebeUsarEneroDefault(),
]);
