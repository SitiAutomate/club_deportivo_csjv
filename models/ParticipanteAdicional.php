<?php

/**
 * Modelo para tabla participantes_adicionales
 * Columnas: documento_N, nombre_N, fechanacimiento_N, celular_N, email_N (N=1..5)
 */
class ParticipanteAdicional
{
    /** @var Medoo\Medoo */
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Guarda participantes adicionales para una inscripción.
     * @param int $inscripcionId IDInscripcion
     * @param int $idCurso ID del curso
     * @param array $participantes Array de arrays con keys: documento, nombre, fechanacimiento, celular, email
     */
    public function guardarParaInscripcion(int $inscripcionId, int $idCurso, array $participantes): void
    {
        if (empty($participantes)) return;

        $row = ['inscripcion' => $inscripcionId, 'idcurso' => $idCurso];
        $maxSlots = 5;
        $fields = ['documento', 'nombre', 'fechanacimiento', 'celular', 'email'];

        foreach (array_slice($participantes, 0, $maxSlots) as $i => $p) {
            $n = $i + 1;
            foreach ($fields as $f) {
                $col = $f . '_' . $n;
                $row[$col] = trim($p[$f] ?? '') ?: null;
            }
        }

        $this->db->insert('participantes_adicionales', $row);
    }
}
