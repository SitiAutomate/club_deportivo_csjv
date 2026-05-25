<?php

/**
 * Modelo para la tabla `equipos`.
 * Almacena cada equipo asociado a una inscripción (inscripciones_1) de evento por equipos (tipo 18).
 */
class Equipo
{
    /** @var Medoo\Medoo */
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Inserta un equipo y devuelve el IDEquipo creado (0 si falla).
     */
    public function create(int $idInscripcion, string $idCurso, array $data): int
    {
        $this->db->insert('equipos', [
            'IDInscripcion' => $idInscripcion,
            'IDCurso' => $idCurso ?: null,
            'nombre_equipo' => trim($data['nombre_equipo'] ?? ''),
            'rama' => $data['rama'] ?? null,
            'categoria' => $data['categoria'] ?? null,
            'entrenador_nombre' => $data['entrenador_nombre'] ?? null,
            'entrenador_documento' => $data['entrenador_documento'] ?? null,
            'entrenador_contacto' => $data['entrenador_contacto'] ?? null,
            'asistente_nombre' => $data['asistente_nombre'] ?? null,
            'asistente_documento' => $data['asistente_documento'] ?? null,
            'asistente_contacto' => $data['asistente_contacto'] ?? null,
        ]);
        return (int) $this->db->id();
    }

    /**
     * Devuelve los equipos vinculados a una inscripción dada.
     */
    public function getByInscripcion(int $idInscripcion): array
    {
        return $this->db->select('equipos', '*', [
            'IDInscripcion' => $idInscripcion,
            'ORDER' => ['IDEquipo' => 'ASC']
        ]) ?: [];
    }
}
