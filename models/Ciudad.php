<?php

/**
 * Modelo para tabla ciudades
 */
class Ciudad
{
    /** @var Medoo\Medoo */
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function getAll(): array
    {
        return $this->db->select('ciudades', ['País', 'Nombre_Pais', 'Depto', 'Nombre_Dpto', 'Ciudad', 'Nombre_Ciudad'], [
            'ORDER' => ['Nombre_Ciudad' => 'ASC']
        ]);
    }

    /**
     * Obtener departamentos únicos (Colombia)
     */
    public function getDepartamentos(): array
    {
        return $this->db->select('ciudades', [
            'Depto',
            'Nombre_Dpto'
        ], [
            'GROUP' => ['Depto', 'Nombre_Dpto'],
            'ORDER' => ['Nombre_Dpto' => 'ASC']
        ]);
    }

    /**
     * Obtener ciudades por departamento
     */
    public function getCiudadesByDepto(string $depto): array
    {
        return $this->db->select('ciudades', [
            'Ciudad',
            'Nombre_Ciudad'
        ], [
            'Depto' => $depto,
            'ORDER' => ['Nombre_Ciudad' => 'ASC']
        ]);
    }
}
