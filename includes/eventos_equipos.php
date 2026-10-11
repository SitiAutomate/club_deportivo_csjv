<?php

require_once __DIR__ . '/copa_vegas.php';

function eventosEquiposConfig(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $path = __DIR__ . '/../config/eventos_equipos.php';
        $cfg = file_exists($path) ? require $path : ['eventos' => []];
    }
    return $cfg;
}

/**
 * @return array<string, array>
 */
function eventosEquiposTodos(): array
{
    $eventos = eventosEquiposConfig()['eventos'] ?? [];
    return is_array($eventos) ? $eventos : [];
}

/**
 * @return array<string, array>
 */
function eventosEquiposActivos(): array
{
    $out = [];
    foreach (eventosEquiposTodos() as $id => $ev) {
        if (!empty($ev['activo'])) {
            $out[(string) $id] = $ev;
        }
    }
    return $out;
}

function eventosEquiposPorId(string $idCurso): ?array
{
    $id = trim($idCurso);
    if ($id === '') {
        return null;
    }
    $ev = eventosEquiposTodos()[$id] ?? null;
    return is_array($ev) ? $ev : null;
}

function eventosEquiposEsValido(string $idCurso): bool
{
    $ev = eventosEquiposPorId($idCurso);
    return $ev !== null && !empty($ev['activo']);
}

function eventosEquiposCampoActivo(array $evento, string $campo): bool
{
    $campos = $evento['campos'] ?? [];
    return !empty($campos[$campo]);
}

function eventosEquiposMaxEnvio(array $evento): int
{
    return max(1, (int) ($evento['max_equipos_por_envio'] ?? 1));
}

function eventosEquiposMaxTotal(array $evento): int
{
    return max(1, (int) ($evento['max_equipos_total'] ?? eventosEquiposMaxEnvio($evento)));
}

/**
 * @return list<string>
 */
function eventosEquiposCategorias(array $evento, ?string $disciplina = null): array
{
    $cats = $evento['categorias'] ?? null;
    if (is_array($cats) && $cats !== []) {
        return array_values(array_map('strval', $cats));
    }
    // Copa Vegas: categorías por disciplina
    if (($evento['modo'] ?? '') === 'copa_vegas' && $disciplina) {
        $disc = copaVegasDisciplinas()[$disciplina] ?? null;
        return is_array($disc['categorias'] ?? null) ? array_values($disc['categorias']) : [];
    }
    return [];
}

function eventosEquiposCategoriaValida(array $evento, string $categoria, ?string $disciplina = null): bool
{
    return in_array($categoria, eventosEquiposCategorias($evento, $disciplina), true);
}

/**
 * Payload público del schema para el frontend.
 */
function eventosEquiposSchemaPublico(array $evento): array
{
    $modo = (string) ($evento['modo'] ?? '');
    $disciplinas = [];
    if ($modo === 'copa_vegas') {
        foreach (copaVegasDisciplinasEquipo() as $nombre) {
            $meta = copaVegasDisciplinas()[$nombre] ?? [];
            $precio = (int) ($meta['precio'] ?? 0);
            $disciplinas[] = [
                'nombre' => $nombre,
                'categorias' => array_values($meta['categorias'] ?? []),
                'precio' => $precio,
                'precio_display' => $precio > 0 ? copaVegasFormatearPrecio($precio) : '',
            ];
        }
    }

    return [
        'id' => (string) ($evento['id'] ?? ''),
        'modo' => $modo,
        'tipo_id' => (int) ($evento['tipo_id'] ?? 18),
        'nombre' => (string) ($evento['nombre'] ?? ''),
        'max_equipos_por_envio' => eventosEquiposMaxEnvio($evento),
        'max_equipos_total' => eventosEquiposMaxTotal($evento),
        'campos' => $evento['campos'] ?? [],
        'disciplina_fija' => $evento['disciplina_fija'] ?? null,
        'categorias' => is_array($evento['categorias'] ?? null) ? array_values($evento['categorias']) : [],
        'ramas' => array_values($evento['ramas'] ?? []),
        'disciplinas' => $disciplinas,
        'deportistas' => $evento['deportistas'] ?? [
            'campos' => ['nombre_completo', 'documento', 'fecha_nacimiento'],
            'requiere_fecha_nacimiento' => true,
            'min' => 1,
            'max' => 50,
        ],
        'nota_responsable' => (string) ($evento['nota_responsable'] ?? ''),
        'label_responsable' => (string) ($evento['label_responsable'] ?? 'Responsable'),
        'info_corta' => (string) ($evento['info_corta'] ?? ''),
        'detalle' => $evento['detalle'] ?? null,
        'template_tipo' => (int) ($evento['template_tipo'] ?? ($evento['tipo_id'] ?? 18)),
    ];
}
