<?php

/**
 * Modelo para tabla tipos
 * PK: IDTipo (int)
 * 1=cursos, 2=campamentos, 3=salidas, etc.
 */
class InscripcionTipo
{
    /** @var Medoo\Medoo */
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Obtener todos los tipos de inscripción activos
     */
    public function getAll(): array
    {
        return $this->db->select('tipos', ['IDTipo', 'Nombre_Tipo', 'descripcion'], [
            'ESTADO' => 'ACTIVO',
            'ORDER' => 'IDTipo'
        ]);
    }
}
