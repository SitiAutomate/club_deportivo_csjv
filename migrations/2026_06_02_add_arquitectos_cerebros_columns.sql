-- Columnas para inscripción Arquitectos de Cerebros (tipo 19)
-- Ejecutar UNA SOLA VEZ contra la base de datos de inscripciones.

ALTER TABLE inscripciones_1
    ADD COLUMN familia_sjv VARCHAR(3) NULL
        COMMENT 'Sí/No — ¿Es familia San José de las Vegas?'
        AFTER Modalidad,
    ADD COLUMN organizacion VARCHAR(150) NULL
        COMMENT 'Organización a la que pertenece si no es familia SJV'
        AFTER familia_sjv;
