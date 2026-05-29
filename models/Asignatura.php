<?php

/**
 * Modelo para tabla asignaturas
 * PK: IDAsignatura (int)
 */
class Asignatura
{
    /** @var Medoo\Medoo */
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getAll(): array
    {
        return $this->db->select('asignaturas', ['IDAsignatura', 'Asignatura'], ['ORDER' => 'Asignatura']);
    }

    public function getById(int $id): ?array
    {
        $row = $this->db->get('asignaturas', ['IDAsignatura', 'Asignatura'], ['IDAsignatura' => $id]);
        return $row ?: null;
    }

    public function findByNombre(string $nombre): ?array
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return null;
        }
        $row = $this->db->get('asignaturas', ['IDAsignatura', 'Asignatura'], [
            'Asignatura' => $nombre,
        ]);
        return $row ?: null;
    }

    public function create(string $nombre): int
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return 0;
        }
        $existente = $this->findByNombre($nombre);
        if ($existente) {
            return (int) ($existente['IDAsignatura'] ?? 0);
        }
        $this->db->insert('asignaturas', ['Asignatura' => $nombre]);
        return (int) $this->db->id();
    }
}
