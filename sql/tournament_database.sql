-- Crear base de datos
CREATE DATABASE torneo_futbol CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE torneo_futbol;

-- Tabla de estadios
CREATE TABLE estadios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    pais VARCHAR(100) NOT NULL,
    capacidad INT NOT NULL,
    imagen VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de equipos (mejorada)
CREATE TABLE equipos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_oficial VARCHAR(150) NOT NULL,
    nombre_corto CHAR(3) NOT NULL UNIQUE,
    pais VARCHAR(100) NOT NULL,
    confederacion ENUM('UEFA', 'CONMEBOL', 'CONCACAF', 'CAF', 'AFC', 'OFC') NOT NULL,
    logo VARCHAR(255) NULL,
    grupo CHAR(1) NULL, -- A-H para fase de grupos
    eliminado BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de fases del torneo
CREATE TABLE fases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL, -- 'grupos', 'octavos', 'cuartos', 'semifinales', 'final', 'tercer_lugar'
    descripcion VARCHAR(100) NOT NULL,
    orden_fase INT NOT NULL,
    activa BOOLEAN DEFAULT FALSE
);

-- Tabla de partidos
CREATE TABLE partidos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fase_id INT NOT NULL,
    equipo_local_id INT NOT NULL,
    equipo_visitante_id INT NOT NULL,
    estadio_id INT NULL,
    fecha_partido DATE NULL,
    hora_partido TIME NULL,
    -- Marcadores tiempo regular
    goles_local_regular INT DEFAULT 0,
    goles_visitante_regular INT DEFAULT 0,
    -- Marcadores prórroga
    goles_local_prorroga INT DEFAULT 0,
    goles_visitante_prorroga INT DEFAULT 0,
    hubo_prorroga BOOLEAN DEFAULT FALSE,
    -- Penales
    penales_local INT DEFAULT 0,
    penales_visitante INT DEFAULT 0,
    hubo_penales BOOLEAN DEFAULT FALSE,
    -- Estado del partido
    partido_jugado BOOLEAN DEFAULT FALSE,
    ganador_id INT NULL, -- ID del equipo ganador
    -- Para fase de grupos
    grupo CHAR(1) NULL,
    jornada INT NULL,
    -- Puntos por deportividad
    deportividad_local INT DEFAULT 0,
    deportividad_visitante INT DEFAULT 0,
    -- Metadatos
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (fase_id) REFERENCES fases(id),
    FOREIGN KEY (equipo_local_id) REFERENCES equipos(id),
    FOREIGN KEY (equipo_visitante_id) REFERENCES equipos(id),
    FOREIGN KEY (ganador_id) REFERENCES equipos(id),
    FOREIGN KEY (estadio_id) REFERENCES estadios(id)
);

-- Tabla de estadísticas por equipo en fase de grupos
CREATE TABLE estadisticas_grupos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipo_id INT NOT NULL,
    grupo CHAR(1) NOT NULL,
    partidos_jugados INT DEFAULT 0,
    partidos_ganados INT DEFAULT 0,
    partidos_empatados INT DEFAULT 0,
    partidos_perdidos INT DEFAULT 0,
    goles_favor INT DEFAULT 0,
    goles_contra INT DEFAULT 0,
    diferencia_goles INT DEFAULT 0,
    puntos INT DEFAULT 0,
    puntos_deportividad INT DEFAULT 0,
    posicion_grupo INT DEFAULT 0,
    clasificado BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (equipo_id) REFERENCES equipos(id),
    UNIQUE KEY unique_equipo_grupo (equipo_id, grupo)
);

-- Insertar las fases del torneo
INSERT INTO fases (nombre, descripcion, orden_fase, activa) VALUES
('grupos', 'Fase de Grupos', 1, TRUE),
('octavos', 'Octavos de Final', 2, FALSE),
('cuartos', 'Cuartos de Final', 3, FALSE),
('semifinales', 'Semifinales', 4, FALSE),
('final', 'Final', 5, FALSE),
('tercer_lugar', 'Tercer Lugar', 6, FALSE);

-- Índices para optimización
CREATE INDEX idx_partidos_fase_grupo ON partidos(fase_id, grupo);
CREATE INDEX idx_partidos_equipos ON partidos(equipo_local_id, equipo_visitante_id);
CREATE INDEX idx_estadisticas_grupo ON estadisticas_grupos(grupo, puntos DESC, diferencia_goles DESC);
CREATE INDEX idx_equipos_grupo ON equipos(grupo);