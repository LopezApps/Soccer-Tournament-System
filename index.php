<?php
// index.php
require_once 'config/database.php';
require_once 'models/Equipo.php';
require_once 'models/Partido.php';
require_once 'models/TorneoManager.php';

// Verificar conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Error: No se pudo conectar a la base de datos. Verifique la configuración.");
}

// Obtener estadísticas básicas del torneo
try {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM equipos");
    $stmt->execute();
    $equipos_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM partidos WHERE partido_jugado = 1");
    $stmt->execute();
    $partidos_jugados = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM partidos WHERE fase_id = 1");
    $stmt->execute();
    $partidos_grupos_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $progreso_grupos = $partidos_grupos_total > 0 ? round(($partidos_jugados / $partidos_grupos_total) * 100, 1) : 0;
} catch (Exception $e) {
    $equipos_count = 0;
    $partidos_jugados = 0;
    $progreso_grupos = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Torneo de Fútbol</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .tournament-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stats-card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            display: block;
        }
        .group-card {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s ease-in-out;
        }
        .group-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .group-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            font-weight: bold;
            color: #495057;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .team-row {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.2s ease;
        }
        .team-row:hover {
            background-color: #f8f9fa;
        }
        .team-row:last-child {
            border-bottom: none;
        }
        .team-stats {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .team-logo-small {
            width: 30px;
            height: 30px;
            object-fit: contain;
            margin-right: 0.5rem;
            flex-shrink: 0;
        }
        .team-info-wrapper {
            display: flex;
            align-items: center;
            flex: 1;
            min-width: 0;
        }
        .team-name-wrapper {
            flex: 1;
            min-width: 0;
        }
        .qualified {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
        }
        .qualified:hover {
            background-color: #c3e6cb;
        }
        .match-card {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background: white;
            transition: box-shadow 0.2s ease-in-out;
        }
        .match-card:hover {
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
        }
        .match-teams {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .match-score {
            font-size: 1.25rem;
            font-weight: bold;
            color: #495057;
            text-align: center;
            min-width: 100px;
            padding: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 0.25rem;
        }
        .nav-pills .nav-link.active {
            background-color: #2a5298;
        }
        .phase-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            background-color: #e9ecef;
            border: 2px solid transparent;
            border-radius: 0.5rem;
            text-decoration: none;
            color: #495057;
            transition: all 0.3s ease-in-out;
            font-weight: 500;
        }
        .phase-badge:hover {
            background-color: #2a5298;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
        }
        .phase-badge.active {
            background-color: #2a5298;
            color: white;
            border-color: #1e3c72;
        }
        .penalty-inputs {
            border-top: 1px solid #dee2e6;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
        }
        .admin-panel {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .progress-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: conic-gradient(#28a745 0deg, #28a745 var(--progress, 0deg), #e9ecef var(--progress, 0deg));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #495057;
        }
    </style>
</head>
<body>
    <!-- Header del Torneo -->
    <div class="tournament-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="mb-0">
                        <i class="fas fa-trophy me-3"></i>
                        Sistema de Torneo de Fútbol
                    </h1>
                    <p class="mb-0 mt-2 opacity-75">Gestión completa de torneo con 32 equipos</p>
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="stats-card">
                                <span class="stats-number"><?php echo $equipos_count; ?></span>
                                <small>Equipos Registrados</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <span class="stats-number"><?php echo $partidos_jugados; ?></span>
                                <small>Partidos Jugados</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="progress-circle" style="--progress: <?php echo $progreso_grupos * 3.6; ?>deg;">
                                    <?php echo $progreso_grupos; ?>%
                                </div>
                                <small class="mt-2">Progreso Grupos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Panel de Administración -->
        <div class="admin-panel">
            <h5 class="mb-3">
                <i class="fas fa-cogs me-2"></i>Panel de Administración
            </h5>
            <div class="row">
                <div class="col-md-2">
                    <button class="btn btn-info w-100 mb-2" onclick="window.location.href='equipos.php'">
                        <i class="fas fa-users me-2"></i>Ver Equipos
                    </button>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-success w-100 mb-2" onclick="window.location.href='estadios.php'">
                        <i class="fas fa-stadium me-2"></i>Ver Estadios
                    </button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-primary w-100 mb-2" onclick="generateGroupMatches()">
                        <i class="fas fa-calendar-plus me-2"></i>Generar Partidos
                    </button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-success w-100 mb-2" onclick="updateStatistics()">
                        <i class="fas fa-sync me-2"></i>Actualizar Estadísticas
                    </button>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100 mb-2" onclick="exportData()">
                        <i class="fas fa-download me-2"></i>Exportar Datos
                    </button>
                </div>
            </div>
        </div>

        <!-- Navegación de Fases -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="text-center">
                    <a href="#" class="phase-badge active" onclick="showPhase('grupos')">
                        <i class="fas fa-users me-2"></i>Fase de Grupos
                    </a>
                    <a href="#" class="phase-badge" onclick="showPhase('octavos')">
                        <i class="fas fa-trophy me-2"></i>Octavos de Final
                    </a>
                    <a href="#" class="phase-badge" onclick="showPhase('cuartos')">
                        <i class="fas fa-medal me-2"></i>Cuartos de Final
                    </a>
                    <a href="#" class="phase-badge" onclick="showPhase('semifinales')">
                        <i class="fas fa-star me-2"></i>Semifinales
                    </a>
                    <a href="#" class="phase-badge" onclick="showPhase('final')">
                        <i class="fas fa-crown me-2"></i>Final
                    </a>
                    <a href="#" class="phase-badge" onclick="showPhase('tercer_lugar')">
                        <i class="fas fa-medal me-2"></i>3er Lugar
                    </a>
                </div>
            </div>
        </div>

        <!-- Contenido de Fases -->
        
        <!-- Fase de Grupos -->
        <div id="fase-grupos" class="phase-content">
            <div class="row">
                <?php for($grupo = 'A'; $grupo <= 'H'; $grupo++): ?>
                <div class="col-lg-6 col-xl-3">
                    <div class="group-card">
                        <div class="group-header">
                            <span>
                                <i class="fas fa-flag me-2"></i>GRUPO <?php echo $grupo; ?>
                            </span>
                            <div class="btn-group">
                                <a href="grupo.php?grupo=<?php echo $grupo; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Ver Detalle
                                </a>
                            </div>
                        </div>
                        <div id="tabla-grupo-<?php echo $grupo; ?>">
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-spinner fa-spin me-2"></i>
                                Cargando equipos...
                            </div>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Fases de Eliminación Directa -->
        <div id="fase-octavos" class="phase-content" style="display: none;">
            <div class="row mb-3">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Octavos de Final</h3>
                    <button class="btn btn-success" onclick="generateRoundOfSixteen()">
                        <i class="fas fa-magic me-2"></i>Generar Octavos
                    </button>
                </div>
            </div>
            <div class="text-center text-muted">
                <i class="fas fa-info-circle me-2"></i>
                Los octavos de final se generarán automáticamente al completar la fase de grupos
            </div>
        </div>

        <div id="fase-cuartos" class="phase-content" style="display: none;">
            <h3 class="mb-4">Cuartos de Final</h3>
            <div class="text-center text-muted">
                <i class="fas fa-info-circle me-2"></i>
                Los cuartos se generarán automáticamente al completar los octavos de final
            </div>
        </div>

        <div id="fase-semifinales" class="phase-content" style="display: none;">
            <h3 class="mb-4">Semifinales</h3>
            <div class="text-center text-muted">
                <i class="fas fa-info-circle me-2"></i>
                Las semifinales se generarán automáticamente al completar los cuartos de final
            </div>
        </div>

        <div id="fase-final" class="phase-content" style="display: none;">
            <h3 class="mb-4">Final</h3>
            <div class="text-center text-muted">
                <i class="fas fa-info-circle me-2"></i>
                La final se generará automáticamente al completar las semifinales
            </div>
        </div>

        <div id="fase-tercer_lugar" class="phase-content" style="display: none;">
            <h3 class="mb-4">Partido por el Tercer Lugar</h3>
            <div class="text-center text-muted">
                <i class="fas fa-info-circle me-2"></i>
                El partido por el tercer lugar se generará automáticamente al completar las semifinales
            </div>
        </div>
    </div>

    <!-- Modales incluidos aquí (mismo código que en el artifact anterior) -->
    
    <!-- Modal para partidos del grupo (se eliminó, ahora se usa grupo.php) -->

        <!-- Modal para editar partido en fases eliminatorias -->
    <div class="modal fade" id="editMatchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Resultado
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="matchForm">
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="text-center p-3" style="background: #f8f9fa; border-radius: 0.5rem;">
                                    <h5 class="mb-0" id="matchTeams">Equipo Local vs Equipo Visitante</h5>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Fecha, Hora y Estadio -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="matchDate" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>Fecha
                                </label>
                                <input type="date" class="form-control" id="matchDate">
                            </div>
                            <div class="col-md-4">
                                <label for="matchTime" class="form-label">
                                    <i class="fas fa-clock me-1"></i>Hora
                                </label>
                                <input type="time" class="form-control" id="matchTime">
                            </div>
                            <div class="col-md-4">
                                <label for="matchStadium" class="form-label">
                                    <i class="fas fa-stadium me-1"></i>Estadio
                                </label>
                                <select class="form-select" id="matchStadium">
                                    <option value="">Seleccionar...</option>
                                </select>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Tiempo Regular</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <label class="form-label">Goles Local</label>
                                        <input type="number" class="form-control form-control-lg text-center" 
                                               id="golesLocalRegular" min="0" value="0">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Goles Visitante</label>
                                        <input type="number" class="form-control form-control-lg text-center" 
                                               id="golesVisitanteRegular" min="0" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="huboProrroga">
                            <label class="form-check-label" for="huboProrroga">
                                <i class="fas fa-plus-circle me-1"></i>¿Hubo prórroga?
                            </label>
                        </div>

                        <div id="prorrogaSection" style="display: none;">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Prórroga</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label">Goles Local (Prórroga)</label>
                                            <input type="number" class="form-control form-control-lg text-center" 
                                                   id="golesLocalProrroga" min="0" value="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Goles Visitante (Prórroga)</label>
                                            <input type="number" class="form-control form-control-lg text-center" 
                                                   id="golesVisitanteProrroga" min="0" value="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="huboPenales">
                            <label class="form-check-label" for="huboPenales">
                                <i class="fas fa-futbol me-1"></i>¿Hubo penales?
                            </label>
                        </div>

                        <div id="penalesSection" style="display: none;">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-futbol me-2"></i>Tanda de Penales</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label">Penales Local</label>
                                            <input type="number" class="form-control form-control-lg text-center" 
                                                   id="penalesLocal" min="0" value="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Penales Visitante</label>
                                            <input type="number" class="form-control form-control-lg text-center" 
                                                   id="penalesVisitante" min="0" value="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-handshake me-2"></i>Puntos por Deportividad</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <label class="form-label">Deportividad Local</label>
                                        <input type="number" class="form-control" id="deportividadLocal" value="0">
                                        <small class="text-muted">Positivo: buen comportamiento, Negativo: sanciones</small>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Deportividad Visitante</label>
                                        <input type="number" class="form-control" id="deportividadVisitante" value="0">
                                        <small class="text-muted">Positivo: buen comportamiento, Negativo: sanciones</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveMatchResult()">
                        <i class="fas fa-save me-2"></i>Guardar Resultado
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6>Sistema de Torneo de Fútbol</h6>
                    <p class="text-muted small mb-0">
                        Sistema completo para la gestión de torneos de fútbol con 32 equipos.
                        Incluye fase de grupos, eliminatorias y sistema de puntuación FIFA.
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <h6>Características</h6>
                    <ul class="list-unstyled text-muted small">
                        <li><i class="fas fa-check text-success me-1"></i>32 equipos en 8 grupos</li>
                        <li><i class="fas fa-check text-success me-1"></i>Sistema de puntuación FIFA</li>
                        <li><i class="fas fa-check text-success me-1"></i>Gestión de estadios</li>
                        <li><i class="fas fa-check text-success me-1"></i>Logos y confederaciones</li>
                        <li><i class="fas fa-check text-success me-1"></i>Vistas detalladas por grupo</li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>

        // Configuración base del sistema
        const API_BASE = 'api';
        
        // Variables globales
        let currentPhase = 'grupos';
        let currentMatchId = null;

        // Inicialización cuando el DOM está listo
        document.addEventListener('DOMContentLoaded', function() {
            initializeTournament();
            
            // Convertir nombre corto a mayúsculas automáticamente
            const nombreCorto = document.getElementById('teamNombreCorto');
            if (nombreCorto) {
                nombreCorto.addEventListener('input', function(e) {
                    e.target.value = e.target.value.toUpperCase();
                });
            }
        });

        function initializeTournament() {
            loadInitialData();
            setupEventListeners();
        }

        async function loadInitialData() {
            try {
                await Promise.all([
                    loadAllGroups(),
                    loadGroupStandings()
                ]);
            } catch (error) {
                console.error('Error cargando datos iniciales:', error);
                showAlert('Error al cargar datos del torneo', 'danger');
            }
        }

        function setupEventListeners() {
            // Event listeners para el formulario de partido
            const huboProrroga = document.getElementById('huboProrroga');
            const huboPenales = document.getElementById('huboPenales');
            
            if (huboProrroga) {
                huboProrroga.addEventListener('change', function() {
                    document.getElementById('prorrogaSection').style.display = 
                        this.checked ? 'block' : 'none';
                });
            }

            if (huboPenales) {
                huboPenales.addEventListener('change', function() {
                    document.getElementById('penalesSection').style.display = 
                        this.checked ? 'block' : 'none';
                });
            }

            // Validación automática
            ['golesLocalRegular', 'golesVisitanteRegular'].forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('change', validateMatchState);
                }
            });
        }

        // ===== FUNCIONES DE API =====
        async function apiCall(endpoint, method = 'GET', data = null) {
            const config = {
                method,
                headers: {
                    'Content-Type': 'application/json'
                }
            };

            if (data) {
                config.body = JSON.stringify(data);
            }

            try {
                const response = await fetch(`${API_BASE}/${endpoint}`, config);
                
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || 'Error en la petición');
                }

                return await response.json();
            } catch (error) {
                console.error('Error en API call:', error);
                throw error;
            }
        }

        async function loadAllGroups() {
            try {
                const equipos = await apiCall('equipos');
                displayTeamsInGroups(equipos);
            } catch (error) {
                console.error('Error cargando equipos:', error);
            }
        }

        function displayTeamsInGroups(equipos) {
            // Agrupar equipos por grupo
            const grupos = {};
            equipos.forEach(equipo => {
                if (equipo.grupo && equipo.grupo.match(/[A-H]/)) {
                    if (!grupos[equipo.grupo]) {
                        grupos[equipo.grupo] = [];
                    }
                    grupos[equipo.grupo].push(equipo);
                }
            });

            // Mostrar equipos en cada grupo (A-H)
            for (let grupo = 'A'.charCodeAt(0); grupo <= 'H'.charCodeAt(0); grupo++) {
                const letter = String.fromCharCode(grupo);
                const container = document.getElementById(`tabla-grupo-${letter}`);
                if (container) {
                    if (grupos[letter] && grupos[letter].length > 0) {
                        renderGroupTeams(container, grupos[letter]);
                    } else {
                        container.innerHTML = `
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-plus-circle me-2"></i>
                                No hay equipos en este grupo
                            </div>
                        `;
                    }
                }
            }
        }

        function renderGroupTeams(container, equipos) {
            container.innerHTML = '';
            equipos.forEach((equipo, index) => {
                const teamRow = document.createElement('div');
                teamRow.className = 'team-row';
                teamRow.innerHTML = `
                    <div>
                        <strong>${escapeHtml(equipo.nombre_oficial)}</strong>
                        <div class="team-stats">${escapeHtml(equipo.pais)}</div>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Pos. ${index + 1}</small>
                    </div>
                `;
                container.appendChild(teamRow);
            });
        }

        // ===== GESTIÓN DE ESTADÍSTICAS =====
        async function loadGroupStandings() {
            try {
                const estadisticas = await apiCall('estadisticas/grupos');
                displayGroupStandings(estadisticas);
            } catch (error) {
                console.error('Error cargando estadísticas:', error);
                // Si no hay estadísticas, mostrar equipos básicos
                await loadAllGroups();
            }
        }

        function displayGroupStandings(gruposData) {
            Object.keys(gruposData).forEach(grupo => {
                const container = document.getElementById(`tabla-grupo-${grupo}`);
                if (container && gruposData[grupo]) {
                    renderGroupStandings(container, gruposData[grupo]);
                }
            });
        }

        function renderGroupStandings(container, equipos) {
            container.innerHTML = '';
            
            equipos.forEach((equipo, index) => {
                const teamRow = document.createElement('div');
                teamRow.className = `team-row ${equipo.clasificado ? 'qualified' : ''}`;
                
                teamRow.innerHTML = `
                    <div class="team-info-wrapper">
                        ${equipo.logo ? 
                            `<img src="${escapeHtml(equipo.logo)}" class="team-logo-small" alt="${escapeHtml(equipo.nombre_oficial)}">` : 
                            `<div class="team-logo-small d-flex align-items-center justify-content-center" style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.25rem;">
                                <i class="fas fa-shield-alt text-muted" style="font-size: 0.875rem;"></i>
                            </div>`
                        }
                        <div class="team-name-wrapper">
                            <strong>${escapeHtml(equipo.nombre_oficial)}</strong>
                            <div class="team-stats">${escapeHtml(equipo.pais)}</div>
                        </div>
                    </div>
                    <div class="text-end" style="flex-shrink: 0;">
                        <div><strong>${equipo.puntos || 0} pts</strong></div>
                        <div class="team-stats">
                            ${equipo.diferencia_goles > 0 ? '+' : ''}${equipo.diferencia_goles || 0} DG
                        </div>
                    </div>
                `;
                
                container.appendChild(teamRow);
            });
        }

        // ===== GESTIÓN DE PARTIDOS =====
        async function showGroupMatches(group) {
            document.getElementById('modalGroupName').textContent = `Grupo ${group}`;
            
            try {
                const partidos = await apiCall(`partidos/grupo?grupo=${group}`);
                displayGroupMatches(partidos);
                new bootstrap.Modal(document.getElementById('matchesModal')).show();
            } catch (error) {
                showAlert('Error al cargar partidos del grupo', 'danger');
            }
        }

        function displayGroupMatches(partidos) {
            const container = document.getElementById('groupMatches');
            container.innerHTML = '';

            if (partidos.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-muted p-4">
                        <i class="fas fa-info-circle me-2"></i>
                        No hay partidos generados para este grupo.
                        <br>
                        <button class="btn btn-primary mt-2" onclick="generateGroupMatches()">
                            <i class="fas fa-plus me-1"></i>Generar Partidos
                        </button>
                    </div>
                `;
                return;
            }

            partidos.forEach(partido => {
                const matchCard = document.createElement('div');
                matchCard.className = 'match-card';
                
                const scoreDisplay = formatMatchScore(partido);
                
                matchCard.innerHTML = `
                    <div class="row align-items-center">
                        <div class="col-4 text-end">
                            <strong>${escapeHtml(partido.equipo_local)}</strong>
                            <div class="small text-muted">${escapeHtml(partido.pais_local)}</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="match-score">${scoreDisplay}</div>
                            ${partido.partido_jugado ? getMatchStatus(partido) : '<small class="text-muted">Pendiente</small>'}
                        </div>
                        <div class="col-4">
                            <strong>${escapeHtml(partido.equipo_visitante)}</strong>
                            <div class="small text-muted">${escapeHtml(partido.pais_visitante)}</div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12 text-center">
                            <button class="btn btn-sm btn-outline-primary" onclick="editMatch(${partido.id})">
                                <i class="fas fa-edit me-1"></i>
                                ${partido.partido_jugado ? 'Editar' : 'Capturar'} Resultado
                            </button>
                            ${partido.jornada ? `<small class="text-muted ms-2">Jornada ${partido.jornada}</small>` : ''}
                        </div>
                    </div>
                `;
                
                container.appendChild(matchCard);
            });
        }

        function formatMatchScore(partido) {
            if (!partido.partido_jugado) {
                return '- vs -';
            }

            const golesLocalTotal = parseInt(partido.goles_local_regular) + parseInt(partido.goles_local_prorroga || 0);
            const golesVisitanteTotal = parseInt(partido.goles_visitante_regular) + parseInt(partido.goles_visitante_prorroga || 0);
            
            let score = `${golesLocalTotal} - ${golesVisitanteTotal}`;
            
            if (partido.hubo_prorroga) {
                score += ` (${partido.goles_local_regular}-${partido.goles_visitante_regular})`;
            }
            
            if (partido.hubo_penales) {
                score += `<br><small>Pen: ${partido.penales_local}-${partido.penales_visitante}</small>`;
            }
            
            return score;
        }

        function getMatchStatus(partido) {
            if (partido.hubo_penales) {
                return '<small class="text-info">Definido por Penales</small>';
            } else if (partido.hubo_prorroga) {
                return '<small class="text-warning">Con Prórroga</small>';
            } else {
                return '<small class="text-muted">Tiempo Regular</small>';
            }
        }

        async function editMatch(matchId) {
            currentMatchId = matchId;
            
            try {
                const partido = await apiCall(`partidos/${matchId}`);
                loadMatchDataInForm(partido);
                
                // Ocultar modal de partidos y mostrar modal de edición
                const matchesModal = bootstrap.Modal.getInstance(document.getElementById('matchesModal'));
                if (matchesModal) {
                    matchesModal.hide();
                }
                
                setTimeout(() => {
                    new bootstrap.Modal(document.getElementById('editMatchModal')).show();
                }, 300);
            } catch (error) {
                showAlert('Error al cargar datos del partido', 'danger');
            }
        }

        function loadMatchDataInForm(partido) {
            document.getElementById('matchTeams').textContent = 
                `${partido.equipo_local} vs ${partido.equipo_visitante}`;
            
            // Cargar datos existentes
            document.getElementById('golesLocalRegular').value = partido.goles_local_regular || 0;
            document.getElementById('golesVisitanteRegular').value = partido.goles_visitante_regular || 0;
            document.getElementById('golesLocalProrroga').value = partido.goles_local_prorroga || 0;
            document.getElementById('golesVisitanteProrroga').value = partido.goles_visitante_prorroga || 0;
            document.getElementById('penalesLocal').value = partido.penales_local || 0;
            document.getElementById('penalesVisitante').value = partido.penales_visitante || 0;
            document.getElementById('deportividadLocal').value = partido.deportividad_local || 0;
            document.getElementById('deportividadVisitante').value = partido.deportividad_visitante || 0;
            
            // Checkboxes
            document.getElementById('huboProrroga').checked = !!partido.hubo_prorroga;
            document.getElementById('huboPenales').checked = !!partido.hubo_penales;
            
            // Mostrar/ocultar secciones según estado
            document.getElementById('prorrogaSection').style.display = 
                partido.hubo_prorroga ? 'block' : 'none';
            document.getElementById('penalesSection').style.display = 
                partido.hubo_penales ? 'block' : 'none';
        }

        async function saveMatchResult() {
            const data = {
                goles_local_regular: parseInt(document.getElementById('golesLocalRegular').value) || 0,
                goles_visitante_regular: parseInt(document.getElementById('golesVisitanteRegular').value) || 0,
                goles_local_prorroga: parseInt(document.getElementById('golesLocalProrroga').value) || 0,
                goles_visitante_prorroga: parseInt(document.getElementById('golesVisitanteProrroga').value) || 0,
                penales_local: parseInt(document.getElementById('penalesLocal').value) || 0,
                penales_visitante: parseInt(document.getElementById('penalesVisitante').value) || 0,
                deportividad_local: parseInt(document.getElementById('deportividadLocal').value) || 0,
                deportividad_visitante: parseInt(document.getElementById('deportividadVisitante').value) || 0,
                hubo_prorroga: document.getElementById('huboProrroga').checked,
                hubo_penales: document.getElementById('huboPenales').checked
            };

            // Validaciones
            if (currentPhase !== 'grupos') {
                const golesLocalTotal = data.goles_local_regular + data.goles_local_prorroga;
                const golesVisitanteTotal = data.goles_visitante_regular + data.goles_visitante_prorroga;
                
                if (golesLocalTotal === golesVisitanteTotal && !data.hubo_penales) {
                    showAlert('En fase eliminatoria, si hay empate debe definirse el ganador por penales', 'warning');
                    return;
                }
            }

            if (data.hubo_penales && data.penales_local === data.penales_visitante) {
                showAlert('En la tanda de penales debe haber un ganador', 'warning');
                return;
            }

            try {
                await apiCall(`partidos/${currentMatchId}`, 'PUT', data);
                
                showAlert('Resultado guardado correctamente', 'success');
                bootstrap.Modal.getInstance(document.getElementById('editMatchModal')).hide();
                
                // Recargar estadísticas
                if (currentPhase === 'grupos') {
                    await loadGroupStandings();
                }
                
                // Actualizar progreso en tiempo real
                updateProgress();
            } catch (error) {
                showAlert(error.message, 'danger');
            }
        }

        function validateMatchState() {
            const localRegular = parseInt(document.getElementById('golesLocalRegular').value) || 0;
            const visitanteRegular = parseInt(document.getElementById('golesVisitanteRegular').value) || 0;
            
            if (currentPhase !== 'grupos' && localRegular === visitanteRegular) {
                // En eliminatorias, si hay empate en tiempo regular, sugerir prórroga
                document.getElementById('huboProrroga').checked = true;
                document.getElementById('prorrogaSection').style.display = 'block';
            }
        }

        // ===== GESTIÓN DE FASES =====
        function showPhase(phase) {
            // Ocultar todas las fases
            document.querySelectorAll('.phase-content').forEach(content => {
                content.style.display = 'none';
            });
            
            // Mostrar fase seleccionada
            const phaseElement = document.getElementById(`fase-${phase}`);
            if (phaseElement) {
                phaseElement.style.display = 'block';
            }
            
            // Actualizar navegación
            document.querySelectorAll('.phase-badge').forEach(badge => {
                badge.classList.remove('active');
            });
            event.target.classList.add('active');
            
            currentPhase = phase;
        }

        // ===== FUNCIONES DE ADMINISTRACIÓN =====
        async function generateGroupMatches() {
            if (!confirm('¿Está seguro de generar todos los partidos de la fase de grupos?')) {
                return;
            }

            try {
                await apiCall('torneo/generar-grupos', 'POST');
                showAlert('Partidos de fase de grupos generados correctamente', 'success');
                
                // Recargar la página o actualizar datos
                location.reload();
            } catch (error) {
                showAlert(error.message, 'danger');
            }
        }

        async function generateRoundOfSixteen() {
            if (!confirm('¿Está seguro de generar los octavos de final? Esto requerirá que la fase de grupos esté completada.')) {
                return;
            }

            try {
                await apiCall('torneo/generar-octavos', 'POST');
                showAlert('Octavos de final generados correctamente', 'success');
            } catch (error) {
                showAlert(error.message, 'danger');
            }
        }

        async function updateStatistics() {
            try {
                await apiCall('torneo/actualizar-estadisticas', 'POST');
                showAlert('Estadísticas actualizadas correctamente', 'success');
                await loadGroupStandings();
                updateProgress();
            } catch (error) {
                showAlert(error.message, 'danger');
            }
        }

        async function exportData() {
            try {
                const [equipos, estadisticas] = await Promise.all([
                    apiCall('equipos'),
                    apiCall('estadisticas/grupos')
                ]);

                const data = {
                    equipos,
                    estadisticas,
                    timestamp: new Date().toISOString(),
                    torneo: 'Copa del Mundo - 32 Equipos'
                };

                const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `torneo-${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                showAlert('Datos exportados correctamente', 'success');
            } catch (error) {
                showAlert('Error al exportar datos', 'danger');
            }
        }

        // ===== UTILIDADES =====
        function showAlert(message, type = 'info') {
            // Crear contenedor de alertas si no existe
            let alertContainer = document.getElementById('alertContainer');
            if (!alertContainer) {
                alertContainer = document.createElement('div');
                alertContainer.id = 'alertContainer';
                alertContainer.className = 'position-fixed top-0 end-0 p-3';
                alertContainer.style.zIndex = '1055';
                document.body.appendChild(alertContainer);
            }

            const alertId = 'alert-' + Date.now();
            const alert = document.createElement('div');
            alert.id = alertId;
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                <strong>${type === 'success' ? 'Éxito!' : type === 'danger' ? 'Error!' : 'Info:'}</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            alertContainer.appendChild(alert);

            // Auto-dismiss después de 5 segundos
            setTimeout(() => {
                const alertElement = document.getElementById(alertId);
                if (alertElement) {
                    try {
                        bootstrap.Alert.getOrCreateInstance(alertElement).close();
                    } catch (e) {
                        alertElement.remove();
                    }
                }
            }, 5000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function updateProgress() {
            try {
                // Obtener estadísticas actualizadas para el progreso
                const response = await fetch('api/estadisticas/progreso');
                if (response.ok) {
                    location.reload(); // Recargar para mostrar progreso actualizado
                }
            } catch (error) {
                console.error('Error actualizando progreso:', error);
            }
        }

        // ===== FUNCIONES DE VALIDACIÓN =====
        async function validateGroupCompletion(grupo) {
            try {
                const partidos = await apiCall(`partidos/grupo?grupo=${grupo}`);
                return partidos.every(partido => partido.partido_jugado);
            } catch (error) {
                return false;
            }
        }

        async function checkAllGroupsCompleted() {
            const grupos = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
            const completionPromises = grupos.map(grupo => validateGroupCompletion(grupo));
            const results = await Promise.all(completionPromises);
            return results.every(completed => completed);
        }
    </script>
</body>
</html>