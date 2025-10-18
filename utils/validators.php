<?php
// utils/validators.php
class TournamentValidators {
    
    public static function validateTeamData($data) {
        $errors = [];
        
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre del equipo es requerido';
        }
        
        if (empty($data['pais'])) {
            $errors[] = 'El país es requerido';
        }
        
        if (empty($data['grupo']) || !in_array($data['grupo'], ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'])) {
            $errors[] = 'Grupo válido es requerido (A-H)';
        }
        
        return $errors;
    }
    
    public static function validateMatchResult($data) {
        $errors = [];
        
        if (!isset($data['goles_local_regular']) || $data['goles_local_regular'] < 0) {
            $errors[] = 'Goles del equipo local deben ser mayor o igual a 0';
        }
        
        if (!isset($data['goles_visitante_regular']) || $data['goles_visitante_regular'] < 0) {
            $errors[] = 'Goles del equipo visitante deben ser mayor o igual a 0';
        }
        
        // Validar prórroga
        if ($data['hubo_prorroga']) {
            if (!isset($data['goles_local_prorroga']) || $data['goles_local_prorroga'] < 0) {
                $errors[] = 'Goles de prórroga del equipo local deben ser mayor o igual a 0';
            }
            
            if (!isset($data['goles_visitante_prorroga']) || $data['goles_visitante_prorroga'] < 0) {
                $errors[] = 'Goles de prórroga del equipo visitante deben ser mayor o igual a 0';
            }
        }
        
        // Validar penales
        if ($data['hubo_penales']) {
            if (!isset($data['penales_local']) || $data['penales_local'] < 0) {
                $errors[] = 'Penales del equipo local deben ser mayor o igual a 0';
            }
            
            if (!isset($data['penales_visitante']) || $data['penales_visitante'] < 0) {
                $errors[] = 'Penales del equipo visitante deben ser mayor o igual a 0';
            }
            
            // En penales, debe haber un ganador claro
            if ($data['penales_local'] === $data['penales_visitante']) {
                $errors[] = 'En la tanda de penales debe haber un ganador';
            }
        }
        
        return $errors;
    }
    
    public static function validateEliminationMatch($data, $fase_id) {
        $errors = self::validateMatchResult($data);
        
        // En eliminatorias, no puede haber empates sin definir ganador
        if ($fase_id > 1) { // Fases de eliminación directa
            $goles_local_total = $data['goles_local_regular'] + ($data['goles_local_prorroga'] ?? 0);
            $goles_visitante_total = $data['goles_visitante_regular'] + ($data['goles_visitante_prorroga'] ?? 0);
            
            if ($goles_local_total === $goles_visitante_total && !$data['hubo_penales']) {
                $errors[] = 'En fase eliminatoria debe definirse un ganador (prórroga y/o penales)';
            }
        }
        
        return $errors;
    }
}