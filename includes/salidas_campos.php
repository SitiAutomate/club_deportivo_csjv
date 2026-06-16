<?php

function salidasCamposConfig(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $path = __DIR__ . '/../config/salidas_campos.php';
        $cfg = file_exists($path) ? require $path : [];
    }
    return $cfg;
}

function salidasCamposPorCurso(string $idCurso): ?array
{
    $cfg = salidasCamposConfig();
    $id = (string) $idCurso;
    return $cfg[$id] ?? null;
}

function salidasCamposCategoriaValida(string $idCurso, string $categoria): bool
{
    $campos = salidasCamposPorCurso($idCurso);
    if (empty($campos['categoria']['options'])) {
        return $categoria === '';
    }
    return array_key_exists($categoria, $campos['categoria']['options']);
}

function salidasCamposAplicarDesdeInput(string $idCurso, array $input, array &$detalle): ?string
{
    $campos = salidasCamposPorCurso($idCurso);
    if (empty($campos['categoria'])) {
        return null;
    }
    $categoria = trim((string) ($input['salida_categoria'] ?? $input['Modalidad'] ?? ''));
    if ($categoria === '' || !salidasCamposCategoriaValida($idCurso, $categoria)) {
        return 'Seleccione una categoría válida.';
    }
    $detalle['Modalidad'] = $categoria;
    return null;
}
