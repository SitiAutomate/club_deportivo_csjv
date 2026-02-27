<?php

/**
 * Modelo para tabla inscripciones_1
 * PK: IDInscripcion (int, auto_increment)
 * Campos principales: validador_participante (doc), validador_responsable (doc), Tipo (1,2,3...), IDCurso
 */
class Inscripcion
{
    /** @var Medoo\Medoo */
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Comprobar si ya existe inscripción para participante + curso + año + tipo
     */
    public function existeDuplicada(string $participanteDoc, string $idCurso, int $anio, int $tipoId): bool
    {
        $count = $this->db->count('inscripciones_1', [
            'validador_participante' => $participanteDoc,
            'IDCurso' => $idCurso,
            'año' => $anio,
            'Tipo' => $tipoId
        ]);
        return $count > 0;
    }

    /**
     * Crear inscripción
     * @param string $participanteDoc IDParticipante (documento)
     * @param string $responsableDoc IDResponsable (documento)
     * @param int $tipoId tipos.IDTipo (1=cursos, 2=campamentos, 3=salidas, etc.)
     * @param array $detalle IDCurso, Mes, Periodo, Sede, Transporte, año, nombreCurso, etc.
     */
    public function create(string $participanteDoc, string $responsableDoc, int $tipoId, array $detalle = []): int
    {
        $anio = (int) ($detalle['año'] ?? $detalle['anio'] ?? date('Y'));
        $uniqueId = $detalle['UniqueID'] ?? uniqid('insc_' . $anio . '_', true);

        $data = [
            'Tipo' => $tipoId,
            'validador_participante' => $participanteDoc,
            'validador_responsable' => $responsableDoc,
            'Verificador_participante' => $detalle['Verificador_participante'] ?? null,
            'verificador_responsable' => $detalle['verificador_responsable'] ?? null,
            'IDCurso' => $detalle['IDCurso'] ?? $detalle['curso_id'] ?? null,
            'Transporte' => $detalle['Transporte'] ?? null,
            'Sede' => $detalle['Sede'] ?? null,
            'Estado' => $detalle['Estado'] ?? null,
            'Fecha_Inscripción' => $detalle['Fecha_Inscripción'] ?? $detalle['fecha_inscripcion'] ?? date('Y-m-d'),
            'Mes' => $detalle['Mes'] ?? null,
            'Descuentos' => $detalle['Descuentos'] ?? null,
            'Periodo' => $detalle['Periodo'] ?? null,
            'Politicas' => $detalle['Politicas'] ?? null,
            'año' => $anio,
            'UniqueID' => $uniqueId,
            'nombreCurso' => $detalle['nombreCurso'] ?? null,
            'categoria' => $detalle['categoria'] ?? null,
            'club' => $detalle['club'] ?? null,
            'OBSERVACION' => $detalle['OBSERVACION'] ?? null,
            'Modalidad' => $detalle['Modalidad'] ?? null,
            'parqueadero' => $detalle['parqueadero'] ?? null,
            'IDAsign' => $detalle['IDAsign'] ?? null
        ];

        $this->db->insert('inscripciones_1', $data);
        return (int) $this->db->id();
    }
}
