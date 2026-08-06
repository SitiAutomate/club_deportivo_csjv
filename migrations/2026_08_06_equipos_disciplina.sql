-- Copa Vegas (equipos): disciplina por equipo
-- Ejecutar UNA SOLA VEZ.

ALTER TABLE equipos
    ADD COLUMN disciplina VARCHAR(50) NULL AFTER categoria;
