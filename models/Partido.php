<?php
// models/Partido.php
class Partido {
    private $conn;
    private $table_name = "partidos";

    public $id;
    public $fase_id;
    public $equipo_local_id;
    public $equipo_visitante_id;
    public $estadio_id;
    public $fecha_partido;
    public $hora_partido;
    public $goles_local_regular;
    public $goles_visitante_regular;
    public $goles_local_prorroga;
    public $goles_visitante_prorroga;
    public $hubo_prorroga;
    public $penales_local;
    public $penales_visitante;
    public $hubo_penales;
    public $partido_jugado;
    public $ganador_id;
    public $grupo;
    public $jornada;
    public $deportividad_local;
    public $deportividad_visitante;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET fase_id=:fase_id, equipo_local_id=:equipo_local_id, 
                      equipo_visitante_id=:equipo_visitante_id, estadio_id=:estadio_id,
                      fecha_partido=:fecha_partido, hora_partido=:hora_partido,
                      grupo=:grupo, jornada=:jornada";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":fase_id", $this->fase_id);
        $stmt->bindParam(":equipo_local_id", $this->equipo_local_id);
        $stmt->bindParam(":equipo_visitante_id", $this->equipo_visitante_id);
        $stmt->bindParam(":estadio_id", $this->estadio_id);
        $stmt->bindParam(":fecha_partido", $this->fecha_partido);
        $stmt->bindParam(":hora_partido", $this->hora_partido);
        $stmt->bindParam(":grupo", $this->grupo);
        $stmt->bindParam(":jornada", $this->jornada);
        
        return $stmt->execute();
    }

    public function actualizarResultado() {
        // Determinar ganador
        $this->determinarGanador();
        
        $query = "UPDATE " . $this->table_name . " 
                  SET goles_local_regular=:goles_local_regular, 
                      goles_visitante_regular=:goles_visitante_regular,
                      goles_local_prorroga=:goles_local_prorroga,
                      goles_visitante_prorroga=:goles_visitante_prorroga,
                      hubo_prorroga=:hubo_prorroga,
                      penales_local=:penales_local,
                      penales_visitante=:penales_visitante,
                      hubo_penales=:hubo_penales,
                      partido_jugado=:partido_jugado,
                      ganador_id=:ganador_id,
                      deportividad_local=:deportividad_local,
                      deportividad_visitante=:deportividad_visitante,
                      estadio_id=:estadio_id,
                      fecha_partido=:fecha_partido,
                      hora_partido=:hora_partido
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":goles_local_regular", $this->goles_local_regular);
        $stmt->bindParam(":goles_visitante_regular", $this->goles_visitante_regular);
        $stmt->bindParam(":goles_local_prorroga", $this->goles_local_prorroga);
        $stmt->bindParam(":goles_visitante_prorroga", $this->goles_visitante_prorroga);
        $stmt->bindParam(":hubo_prorroga", $this->hubo_prorroga, PDO::PARAM_BOOL);
        $stmt->bindParam(":penales_local", $this->penales_local);
        $stmt->bindParam(":penales_visitante", $this->penales_visitante);
        $stmt->bindParam(":hubo_penales", $this->hubo_penales, PDO::PARAM_BOOL);
        $stmt->bindParam(":partido_jugado", $this->partido_jugado, PDO::PARAM_BOOL);
        $stmt->bindParam(":ganador_id", $this->ganador_id);
        $stmt->bindParam(":deportividad_local", $this->deportividad_local);
        $stmt->bindParam(":deportividad_visitante", $this->deportividad_visitante);
        $stmt->bindParam(":estadio_id", $this->estadio_id);
        $stmt->bindParam(":fecha_partido", $this->fecha_partido);
        $stmt->bindParam(":hora_partido", $this->hora_partido);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    private function determinarGanador() {
        $goles_local_total = $this->goles_local_regular + $this->goles_local_prorroga;
        $goles_visitante_total = $this->goles_visitante_regular + $this->goles_visitante_prorroga;
        
        if ($goles_local_total > $goles_visitante_total) {
            $this->ganador_id = $this->equipo_local_id;
        } elseif ($goles_visitante_total > $goles_local_total) {
            $this->ganador_id = $this->equipo_visitante_id;
        } else {
            // Empate - verificar penales
            if ($this->hubo_penales) {
                if ($this->penales_local > $this->penales_visitante) {
                    $this->ganador_id = $this->equipo_local_id;
                } else {
                    $this->ganador_id = $this->equipo_visitante_id;
                }
            } else {
                $this->ganador_id = null; // Empate en fase de grupos
            }
        }
        
        $this->partido_jugado = true;
    }

    public function leerPorGrupo($grupo) {
        $query = "SELECT p.*, 
                         el.nombre_oficial as equipo_local, el.nombre_corto as codigo_local,
                         el.pais as pais_local, el.logo as logo_local,
                         ev.nombre_oficial as equipo_visitante, ev.nombre_corto as codigo_visitante,
                         ev.pais as pais_visitante, ev.logo as logo_visitante,
                         e.nombre as estadio_nombre, e.ciudad as estadio_ciudad
                  FROM " . $this->table_name . " p
                  LEFT JOIN equipos el ON p.equipo_local_id = el.id
                  LEFT JOIN equipos ev ON p.equipo_visitante_id = ev.id
                  LEFT JOIN estadios e ON p.estadio_id = e.id
                  WHERE p.grupo = ? AND p.fase_id = 1
                  ORDER BY p.jornada, p.fecha_partido";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $grupo);
        $stmt->execute();
        return $stmt;
    }

    public function leerPorFase($fase_id) {
        $query = "SELECT p.*, 
                         el.nombre_oficial as equipo_local, el.nombre_corto as codigo_local,
                         el.pais as pais_local, el.logo as logo_local,
                         ev.nombre_oficial as equipo_visitante, ev.nombre_corto as codigo_visitante,
                         ev.pais as pais_visitante, ev.logo as logo_visitante,
                         eg.nombre_oficial as ganador,
                         e.nombre as estadio_nombre, e.ciudad as estadio_ciudad
                  FROM " . $this->table_name . " p
                  LEFT JOIN equipos el ON p.equipo_local_id = el.id
                  LEFT JOIN equipos ev ON p.equipo_visitante_id = ev.id
                  LEFT JOIN equipos eg ON p.ganador_id = eg.id
                  LEFT JOIN estadios e ON p.estadio_id = e.id
                  WHERE p.fase_id = ?
                  ORDER BY p.fecha_partido";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $fase_id);
        $stmt->execute();
        return $stmt;
    }
}
