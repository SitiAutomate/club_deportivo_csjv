-- Acelera los COUNT de cupos sobre inscripciones_1 (consultas que Hostinger marcó como slow > 1s).
-- Ejecutar una sola vez en u328419981_inscrip_cbmaex.

CREATE INDEX idx_insc1_cupos
    ON inscripciones_1 (IDCurso, año, Mes, Estado, validador_participante);
