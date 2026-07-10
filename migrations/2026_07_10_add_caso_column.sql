-- Columna Caso para género (y usos similares) en eventos tipo 18
-- Ejecutar UNA SOLA VEZ contra la base de datos de inscripciones.

ALTER TABLE inscripciones_1
    ADD COLUMN Caso VARCHAR(50) NULL
        COMMENT 'Género u otro dato de caso (ej. Femenino/Masculino en TKD Nacional)'
        AFTER categoria;
