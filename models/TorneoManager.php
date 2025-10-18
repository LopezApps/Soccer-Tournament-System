<?php
// models/TorneoManager.php
class TorneoManager {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function generarPartidosFaseGrupos() {
        // Obtener equipos por grupo
        for ($grupo = 'A'; $grupo <= 'H'; $grupo++) {
            $query = "SELECT id FROM equipos WHERE grupo = ? ORDER BY nombre_oficial";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $grupo);
            $stmt->execute();
            
            $equipos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($equipos) == 4) {
                $this->generarPartidosGrupo($equipos, $grupo);
            }
        }
    }

    private function generarPartidosGrupo($equipos, $grupo) {
        $partido = new Partido($this->conn);
        
        // Generar todos los enfrentamientos posibles (6 partidos por grupo)
        $enfrentamientos = [
            [$equipos[0], $equipos[1]], // Jornada 1
            [$equipos[2], $equipos[3]],
            [$equipos[0], $equipos[2]], // Jornada 2
            [$equipos[1], $equipos[3]],
            [$equipos[0], $equipos[3]], // Jornada 3
            [$equipos[1], $equipos[2]]
        ];

        foreach ($enfrentamientos as $index => $enfrentamiento) {
            $partido->fase_id = 1; // Fase de grupos
            $partido->equipo_local_id = $enfrentamiento[0];
            $partido->equipo_visitante_id = $enfrentamiento[1];
            $partido->grupo = $grupo;
            $partido->jornada = intval($index / 2) + 1;
            $partido->fecha_partido = null;
            $partido->hora_partido = null;
            $partido->estadio_id = null;
            
            $partido->crear();
        }
    }

    public function actualizarEstadisticasGrupos() {
        for ($grupo = 'A'; $grupo <= 'H'; $grupo++) {
            $this->calcularEstadisticasGrupo($grupo);
        }
    }

    private function calcularEstadisticasGrupo($grupo) {
        // Limpiar estadísticas existentes
        $query = "DELETE FROM estadisticas_grupos WHERE grupo = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$grupo]);

        // Obtener equipos del grupo
        $query = "SELECT id FROM equipos WHERE grupo = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$grupo]);
        $equipos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Calcular estadísticas para cada equipo
        foreach ($equipos as $equipo_id) {
            $stats = $this->calcularEstadisticasEquipo($equipo_id, $grupo);
            $this->insertarEstadisticas($equipo_id, $grupo, $stats);
        }

        // Ordenar equipos según criterios FIFA
        $this->ordenarGrupo($grupo);
    }

    private function calcularEstadisticasEquipo($equipo_id, $grupo) {
        $query = "SELECT 
                    COUNT(*) as partidos_jugados,
                    SUM(CASE 
                        WHEN ganador_id = ? THEN 1 
                        ELSE 0 
                    END) as ganados,
                    SUM(CASE 
                        WHEN ganador_id IS NULL AND partido_jugado = 1 THEN 1 
                        ELSE 0 
                    END) as empatados,
                    SUM(CASE 
                        WHEN ganador_id != ? AND ganador_id IS NOT NULL THEN 1 
                        ELSE 0 
                    END) as perdidos,
                    COALESCE(SUM(CASE 
                        WHEN equipo_local_id = ? THEN (COALESCE(goles_local_regular, 0) + COALESCE(goles_local_prorroga, 0))
                        ELSE (COALESCE(goles_visitante_regular, 0) + COALESCE(goles_visitante_prorroga, 0))
                    END), 0) as goles_favor,
                    COALESCE(SUM(CASE 
                        WHEN equipo_local_id = ? THEN (COALESCE(goles_visitante_regular, 0) + COALESCE(goles_visitante_prorroga, 0))
                        ELSE (COALESCE(goles_local_regular, 0) + COALESCE(goles_local_prorroga, 0))
                    END), 0) as goles_contra,
                    COALESCE(SUM(CASE 
                        WHEN equipo_local_id = ? THEN COALESCE(deportividad_local, 0)
                        ELSE COALESCE(deportividad_visitante, 0)
                    END), 0) as puntos_deportividad
                  FROM partidos 
                  WHERE (equipo_local_id = ? OR equipo_visitante_id = ?) 
                    AND grupo = ? AND partido_jugado = 1 AND fase_id = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$equipo_id, $equipo_id, $equipo_id, $equipo_id, $equipo_id, $equipo_id, $equipo_id, $grupo]);
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Validar que existan datos
        if (!$stats || $stats['partidos_jugados'] == 0) {
            $stats = [
                'partidos_jugados' => 0,
                'ganados' => 0,
                'empatados' => 0,
                'perdidos' => 0,
                'goles_favor' => 0,
                'goles_contra' => 0,
                'puntos_deportividad' => 0
            ];
        }
        
        // Calcular puntos (3 por victoria, 1 por empate, 0 por derrota)
        $stats['puntos'] = ($stats['ganados'] * 3) + $stats['empatados'];
        $stats['diferencia_goles'] = $stats['goles_favor'] - $stats['goles_contra'];
        
        return $stats;
    }

    private function insertarEstadisticas($equipo_id, $grupo, $stats) {
        $query = "INSERT INTO estadisticas_grupos 
                  SET equipo_id=?, grupo=?, partidos_jugados=?, partidos_ganados=?, 
                      partidos_empatados=?, partidos_perdidos=?, goles_favor=?, 
                      goles_contra=?, diferencia_goles=?, puntos=?, puntos_deportividad=?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $equipo_id, $grupo, $stats['partidos_jugados'], $stats['ganados'],
            $stats['empatados'], $stats['perdidos'], $stats['goles_favor'],
            $stats['goles_contra'], $stats['diferencia_goles'], $stats['puntos'],
            $stats['puntos_deportividad']
        ]);
    }

    private function ordenarGrupo($grupo) {
        $query = "SELECT eg.*, e.nombre_oficial 
                  FROM estadisticas_grupos eg 
                  JOIN equipos e ON eg.equipo_id = e.id 
                  WHERE eg.grupo = ? 
                  ORDER BY eg.puntos DESC, eg.diferencia_goles DESC, 
                           eg.goles_favor DESC, eg.puntos_deportividad DESC, e.nombre_oficial ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$grupo]);
        $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Asignar posiciones del 1 al 4 y marcar clasificados
        $posicion = 1;
        foreach ($equipos as $equipo) {
            // Los primeros 2 lugares clasifican a octavos de final
            $clasificado = ($posicion <= 2) ? true : false;
            
            $query = "UPDATE estadisticas_grupos 
                      SET posicion_grupo = ?, clasificado = ? 
                      WHERE equipo_id = ? AND grupo = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $posicion, PDO::PARAM_INT);
            $stmt->bindParam(2, $clasificado, PDO::PARAM_BOOL);
            $stmt->bindParam(3, $equipo['equipo_id'], PDO::PARAM_INT);
            $stmt->bindParam(4, $grupo, PDO::PARAM_STR);
            $stmt->execute();
            
            $posicion++;
        }
    }

    public function generarOctavosFinal() {
        // Obtener clasificados de cada grupo (primeros 2 lugares)
        $primeros = [];
        $segundos = [];

        for ($grupo = 'A'; $grupo <= 'H'; $grupo++) {
            $query = "SELECT eg.equipo_id, e.nombre_oficial 
                      FROM estadisticas_grupos eg 
                      JOIN equipos e ON eg.equipo_id = e.id 
                      WHERE eg.grupo = ? AND eg.clasificado = 1 
                      ORDER BY eg.posicion_grupo";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$grupo]);
            $equipos_grupo = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($equipos_grupo) >= 2) {
                $primeros[$grupo] = $equipos_grupo[0]['equipo_id'];
                $segundos[$grupo] = $equipos_grupo[1]['equipo_id'];
            }
        }

        // Generar enfrentamientos de octavos según formato FIFA
        $enfrentamientos = [
            [$primeros['A'], $segundos['B']], // 1A vs 2B
            [$primeros['C'], $segundos['D']], // 1C vs 2D
            [$primeros['E'], $segundos['F']], // 1E vs 2F
            [$primeros['G'], $segundos['H']], // 1G vs 2H
            [$primeros['B'], $segundos['A']], // 1B vs 2A
            [$primeros['D'], $segundos['C']], // 1D vs 2C
            [$primeros['F'], $segundos['E']], // 1F vs 2E
            [$primeros['H'], $segundos['G']]  // 1H vs 2G
        ];

        // Crear partidos de octavos
        $partido = new Partido($this->conn);
        foreach ($enfrentamientos as $enfrentamiento) {
            $partido->fase_id = 2; // Octavos de final
            $partido->equipo_local_id = $enfrentamiento[0];
            $partido->equipo_visitante_id = $enfrentamiento[1];
            $partido->grupo = null;
            $partido->jornada = null;
            $partido->fecha_partido = null;
            $partido->hora_partido = null;
            $partido->estadio_id = null;
            
            $partido->crear();
        }
    }
}
