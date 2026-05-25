-- Tablas para inscripciones por equipos (eventos tipo 18, Festivegas y similares)
-- Ejecutar UNA SOLA VEZ contra la base de datos de inscripciones.

CREATE TABLE IF NOT EXISTS equipos (
    IDEquipo INT AUTO_INCREMENT PRIMARY KEY,
    IDInscripcion INT NOT NULL,
    IDCurso VARCHAR(20) NULL,
    nombre_equipo VARCHAR(150) NOT NULL,
    rama VARCHAR(20) NULL,
    categoria VARCHAR(50) NULL,
    entrenador_nombre VARCHAR(150) NULL,
    entrenador_documento VARCHAR(30) NULL,
    entrenador_contacto VARCHAR(30) NULL,
    asistente_nombre VARCHAR(150) NULL,
    asistente_documento VARCHAR(30) NULL,
    asistente_contacto VARCHAR(30) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inscripcion (IDInscripcion),
    INDEX idx_curso (IDCurso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS equipo_deportistas (
    IDDeportista INT AUTO_INCREMENT PRIMARY KEY,
    IDEquipo INT NOT NULL,
    orden INT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    fecha_nacimiento DATE NULL,
    documento VARCHAR(30) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_equipo (IDEquipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
