<?php

/**
 * Modelo para la tabla `equipo_deportistas`.
 * Lista de deportistas (jugadores) por equipo. Una fila por jugador.
 */
class EquipoDeportista
{
    /** @var Medoo\Medoo */
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Inserta los deportistas de un equipo. Cada item es un array con keys:
     *  - nombre_completo (obligatorio)
     *  - fecha_nacimiento (Y-m-d) (opcional)
     *  - documento (opcional)
     */
    public function crearLote(int $idEquipo, array $deportistas): int
    {
        $insertados = 0;
        foreach ($deportistas as $i => $d) {
            $nombre = trim((string) ($d['nombre_completo'] ?? $d['nombre'] ?? ''));
            if ($nombre === '') {
                continue;
            }
            $fecha = trim((string) ($d['fecha_nacimiento'] ?? ''));
            $documento = trim((string) ($d['documento'] ?? ''));
            $this->db->insert('equipo_deportistas', [
                'IDEquipo' => $idEquipo,
                'orden' => $i + 1,
                'nombre_completo' => $nombre,
                'fecha_nacimiento' => $fecha !== '' ? $fecha : null,
                'documento' => $documento !== '' ? $documento : null,
            ]);
            $insertados++;
        }
        return $insertados;
    }

    public function getByEquipo(int $idEquipo): array
    {
        return $this->db->select('equipo_deportistas', '*', [
            'IDEquipo' => $idEquipo,
            'ORDER' => ['orden' => 'ASC']
        ]) ?: [];
    }
}
