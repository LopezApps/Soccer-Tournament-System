<?php
// grupo.php
require_once 'config/database.php';

// Obtener el grupo de la URL
$grupo = isset($_GET['grupo']) ? strtoupper($_GET['grupo']) : 'A';

// Validar que el grupo sea válido (A-H)
if (!preg_match('/^[A-H]$/', $grupo)) {
    $grupo = 'A';
}

// Verificar conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Error: No se pudo conectar a la base de datos.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupo <?php echo $grupo; ?> - Torneo de Fútbol</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .group-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .standings-table {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            overflow: hidden;
        }
        .standings-table thead {
            background: #f8f9fa;
        }
        .standings-table th {
            font-weight: 600;
            font-size: 0.875rem;
            padding: 1rem 0.75rem;
            border-bottom: 2px solid #dee2e6;
        }
        .standings-table td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
        }
        .standings-table tbody tr {
            transition: background-color 0.2s;
        }
        .standings-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .qualified-row {
            border-left: 4px solid #28a745;
            background-color: #d4edda;
        }
        .qualified-row:hover {
            background-color: #c3e6cb;
        }
        .team-logo-small {
            width: 30px;
            height: 30px;
            object-fit: contain;
            margin-right: 0.5rem;
        }
        .position-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #6c757d;
            color: white;
            font-weight: bold;
            font-size: 0.875rem;
        }
        .position-badge.first {
            background: #ffd700;
            color: #000;
        }
        .position-badge.second {
            background: #c0c0c0;
            color: #000;
        }
        .match-card {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 0.75rem;
            background: white;
            transition: all 0.2s;
        }
        .match-card:hover {
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
        }
        .match-card.played {
            border-left: 4px solid #28a745;
        }
        .match-card.pending {
            border-left: 4px solid #ffc107;
        }
        .match-teams {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .team-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        }
        .team-section.home {
            align-items: flex-end;
        }
        .team-section.away {
            align-items: flex-start;
        }
        .team-logo-match {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        .team-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
            text-align: center;
        }
        .match-score-display {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            min-width: 200px;
        }
        .match-score-display .score {
            font-size: 3rem;
            font-weight: bold;
            color: #2a5298;
            line-height: 1;
        }
        .match-score-display .separator {
            font-size: 2rem;
            font-weight: bold;
            color: #6c757d;
        }
        .match-score-display .vs {
            font-size: 2rem;
            font-weight: bold;
            color: #6c757d;
        }
        .match-status {
            text-align: center;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        .match-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 0.75rem;
            border-top: 1px solid #dee2e6;
            margin-top: 0.75rem;
            font-size: 0.85rem;
        }
        .jornada-badge {
            background: #e9ecef;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .team-flag-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .team-flag {
            width: 40px;
            height: 40px;
            object-fit: contain;
            border: 2px solid #e9ecef;
            border-radius: 0.25rem;
            padding: 0.125rem;
            background: #f8f9fa;
        }
        .score-input-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .score-input {
            width: 70px !important;
            height: 70px;
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            padding: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Header del Grupo -->
    <div class="group-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="mb-0">
                        <i class="fas fa-flag me-3"></i>
                        <span>GRUPO <?php echo $grupo; ?></span>
                    </h1>
                    <p class="mb-0 mt-2 opacity-75">Fase de Grupos - Copa del Mundo</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Torneo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Tabla de Posiciones -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-3">
                    <i class="fas fa-list-ol me-2"></i>Tabla de Posiciones
                </h3>
                <div class="standings-table">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Pos</th>
                                <th>Equipo</th>
                                <th class="text-center" style="width: 60px;">PJ</th>
                                <th class="text-center" style="width: 60px;">G</th>
                                <th class="text-center" style="width: 60px;">E</th>
                                <th class="text-center" style="width: 60px;">P</th>
                                <th class="text-center" style="width: 60px;">GF</th>
                                <th class="text-center" style="width: 60px;">GC</th>
                                <th class="text-center" style="width: 60px;">DG</th>
                                <th class="text-center" style="width: 70px;"><strong>Pts</strong></th>
                            </tr>
                        </thead>
                        <tbody id="standingsBody">
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="fas fa-spinner fa-spin me-2"></i>
                                    Cargando tabla de posiciones...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Los 2 primeros equipos clasifican a Octavos de Final
                    </small>
                </div>
            </div>
        </div>

        <!-- Partidos del Grupo -->
        <div class="row">
            <div class="col-12">
                <h3 class="mb-3">
                    <i class="fas fa-futbol me-2"></i>Partidos del Grupo
                </h3>
                <div class="row" id="matchesContainer">
                    <div class="col-12">
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                            <p>Cargando partidos...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Resultado -->
    <div class="modal fade" id="editMatchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Capturar Resultado
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="matchForm">
                        <input type="hidden" id="matchId">
                        
                        <!-- Información del Partido -->
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
                                    <i class="fas fa-calendar me-1"></i>Fecha del Partido
                                </label>
                                <input type="date" class="form-control" id="matchDate">
                            </div>
                            <div class="col-md-4">
                                <label for="matchTime" class="form-label">
                                    <i class="fas fa-clock me-1"></i>Hora del Partido
                                </label>
                                <input type="time" class="form-control" id="matchTime">
                            </div>
                            <div class="col-md-4">
                                <label for="matchStadium" class="form-label">
                                    <i class="fas fa-stadium me-1"></i>Estadio
                                </label>
                                <select class="form-select" id="matchStadium">
                                    <option value="">Seleccionar estadio...</option>
                                </select>
                            </div>
                        </div>

                        <!-- Marcadores con Banderas -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-futbol me-2"></i>Resultado del Partido
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-5">
                                        <div class="team-flag-container justify-content-end">
                                            <div class="text-end">
                                                <strong id="teamLocalName">Equipo Local</strong>
                                            </div>
                                            <img id="teamLocalFlag" src="" class="team-flag" alt="Local" style="display: none;">
                                        </div>
                                        <div class="score-input-container mt-2">
                                            <input type="number" class="form-control score-input" 
                                                   id="golesLocal" min="0" value="0">
                                        </div>
                                    </div>
                                    <div class="col-2 text-center">
                                        <div class="fs-3 fw-bold text-muted">VS</div>
                                    </div>
                                    <div class="col-5">
                                        <div class="team-flag-container">
                                            <img id="teamVisitanteFlag" src="" class="team-flag" alt="Visitante" style="display: none;">
                                            <div>
                                                <strong id="teamVisitanteName">Equipo Visitante</strong>
                                            </div>
                                        </div>
                                        <div class="score-input-container mt-2">
                                            <input type="number" class="form-control score-input" 
                                                   id="golesVisitante" min="0" value="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Puntos por Deportividad -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-handshake me-2"></i>Puntos por Deportividad
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <label for="deportividadLocal" class="form-label">Local</label>
                                        <input type="number" class="form-control" id="deportividadLocal" value="0">
                                        <small class="text-muted">Positivo: buen comportamiento, Negativo: sanciones</small>
                                    </div>
                                    <div class="col-6">
                                        <label for="deportividadVisitante" class="form-label">Visitante</label>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        let currentGroup = '<?php echo $grupo; ?>';
        let currentMatchId = null;
        let currentMatchData = null;
        const API_BASE = 'api';

        // Inicializar vista
        document.addEventListener('DOMContentLoaded', function() {
            loadGroupData();
            loadStadiumsList();
        });

        // Cargar datos del grupo
        async function loadGroupData() {
            await Promise.all([
                loadStandings(),
                loadMatches()
            ]);
        }

        // Cargar tabla de posiciones
        async function loadStandings() {
            try {
                const response = await fetch(`${API_BASE}/estadisticas/grupos?grupo=${currentGroup}`);
                const data = await response.json();
                displayStandings(data);
            } catch (error) {
                console.error('Error cargando estadísticas:', error);
                document.getElementById('standingsBody').innerHTML = `
                    <tr>
                        <td colspan="10" class="text-center text-danger py-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error al cargar tabla de posiciones
                        </td>
                    </tr>
                `;
            }
        }

        // Mostrar tabla de posiciones
        function displayStandings(equipos) {
            const tbody = document.getElementById('standingsBody');
            
            if (!equipos || equipos.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle me-2"></i>
                            No hay equipos en este grupo
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = '';
            
            equipos.forEach((equipo, index) => {
                const row = document.createElement('tr');
                const isQualified = index < 2;
                
                if (isQualified) {
                    row.classList.add('qualified-row');
                }
                
                const nombreEquipo = equipo.nombre_oficial || 'Sin nombre';
                const paisEquipo = equipo.pais || '';
                const logoEquipo = equipo.logo || '';
                
                row.innerHTML = `
                    <td>
                        <span class="position-badge ${index === 0 ? 'first' : index === 1 ? 'second' : ''}">${index + 1}</span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${logoEquipo ? `<img src="${escapeHtml(logoEquipo)}" class="team-logo-small" alt="${escapeHtml(nombreEquipo)}">` : ''}
                            <div>
                                <strong>${escapeHtml(nombreEquipo)}</strong>
                                ${paisEquipo ? `<div><small class="text-muted">${escapeHtml(paisEquipo)}</small></div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td class="text-center">${equipo.partidos_jugados || 0}</td>
                    <td class="text-center">${equipo.partidos_ganados || 0}</td>
                    <td class="text-center">${equipo.partidos_empatados || 0}</td>
                    <td class="text-center">${equipo.partidos_perdidos || 0}</td>
                    <td class="text-center">${equipo.goles_favor || 0}</td>
                    <td class="text-center">${equipo.goles_contra || 0}</td>
                    <td class="text-center">
                        <strong class="${(equipo.diferencia_goles || 0) > 0 ? 'text-success' : (equipo.diferencia_goles || 0) < 0 ? 'text-danger' : ''}">
                            ${(equipo.diferencia_goles || 0) > 0 ? '+' : ''}${equipo.diferencia_goles || 0}
                        </strong>
                    </td>
                    <td class="text-center">
                        <strong class="fs-5">${equipo.puntos || 0}</strong>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        }

        // Cargar partidos del grupo
        async function loadMatches() {
            try {
                const response = await fetch(`${API_BASE}/partidos/grupo?grupo=${currentGroup}`);
                const partidos = await response.json();
                displayMatches(partidos);
            } catch (error) {
                console.error('Error cargando partidos:', error);
                document.getElementById('matchesContainer').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error al cargar partidos
                    </div>
                `;
            }
        }

        // Mostrar partidos
        function displayMatches(partidos) {
            const container = document.getElementById('matchesContainer');
            
            if (!partidos || partidos.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>
                            No hay partidos generados para este grupo.
                            <br>
                            <a href="index.php" class="btn btn-primary mt-3">
                                <i class="fas fa-plus me-2"></i>Generar Partidos
                            </a>
                        </div>
                    </div>
                `;
                return;
            }

            // Agrupar por jornada
            const jornadasMap = {};
            partidos.forEach(partido => {
                const jornada = partido.jornada || 1;
                if (!jornadasMap[jornada]) {
                    jornadasMap[jornada] = [];
                }
                jornadasMap[jornada].push(partido);
            });

            container.innerHTML = '';

            // Renderizar por jornada
            Object.keys(jornadasMap).sort().forEach(jornada => {
                const jornadaDiv = document.createElement('div');
                jornadaDiv.className = 'col-12 mb-4';
                
                let jornadaContent = `
                    <h5 class="mb-3">
                        <span class="jornada-badge">Jornada ${jornada}</span>
                    </h5>
                    <div class="row">
                `;
                
                jornadasMap[jornada].forEach(partido => {
                    jornadaContent += `<div class="col-md-6">${createMatchCardHTML(partido)}</div>`;
                });
                
                jornadaContent += '</div>';
                jornadaDiv.innerHTML = jornadaContent;
                
                container.appendChild(jornadaDiv);
            });
        }

        // Crear HTML de tarjeta de partido
        function createMatchCardHTML(partido) {
            const golesLocalTotal = (partido.goles_local_regular || 0);
            const golesVisitanteTotal = (partido.goles_visitante_regular || 0);
            
            return `
                <div class="match-card ${partido.partido_jugado ? 'played' : 'pending'}">
                    <div class="match-teams">
                        <div class="team-section home">
                            ${partido.logo_local ? `<img src="${partido.logo_local}" class="team-logo-match" alt="${partido.equipo_local}">` : 
                                `<div style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 0.375rem;">
                                    <i class="fas fa-shield-alt fa-2x text-muted"></i>
                                </div>`
                            }
                            <div class="team-name">${escapeHtml(partido.equipo_local)}</div>
                        </div>
                        
                        <div style="text-align: center;">
                            <div class="match-score-display">
                                ${partido.partido_jugado ? 
                                    `<span class="score">${golesLocalTotal}</span>
                                     <span class="separator">:</span>
                                     <span class="score">${golesVisitanteTotal}</span>` :
                                    `<span class="vs">VS</span>`
                                }
                            </div>
                            <div class="match-status">
                                ${!partido.partido_jugado ? 
                                    `<small class="text-warning"><i class="fas fa-clock me-1"></i>Pendiente</small>` :
                                    `<small class="text-success"><i class="fas fa-check-circle me-1"></i>Fin del partido</small>`
                                }
                            </div>
                        </div>
                        
                        <div class="team-section away">
                            ${partido.logo_visitante ? `<img src="${partido.logo_visitante}" class="team-logo-match" alt="${partido.equipo_visitante}">` : 
                                `<div style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 0.375rem;">
                                    <i class="fas fa-shield-alt fa-2x text-muted"></i>
                                </div>`
                            }
                            <div class="team-name">${escapeHtml(partido.equipo_visitante)}</div>
                        </div>
                    </div>
                    
                    <div class="match-info">
                        <div>
                            ${partido.fecha_partido ? 
                                `<small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>${formatDate(partido.fecha_partido)}
                                </small>` : ''
                            }
                            ${partido.hora_partido ? 
                                `<small class="text-muted ms-3">
                                    <i class="fas fa-clock me-1"></i>${partido.hora_partido}
                                </small>` : ''
                            }
                            ${partido.estadio_nombre ? 
                                `<small class="text-muted ms-3">
                                    <i class="fas fa-stadium me-1"></i>${escapeHtml(partido.estadio_nombre)}
                                </small>` : ''
                            }
                        </div>
                        <button class="btn btn-sm btn-primary" onclick="editMatch(${partido.id})">
                            <i class="fas fa-edit me-1"></i>
                            ${partido.partido_jugado ? 'Editar' : 'Capturar'} Resultado
                        </button>
                    </div>
                </div>
            `;
        }

        // Cargar lista de estadios
        async function loadStadiumsList() {
            try {
                const response = await fetch(`${API_BASE}/estadios`);
                const estadios = await response.json();
                
                const select = document.getElementById('matchStadium');
                select.innerHTML = '<option value="">Seleccionar estadio...</option>';
                
                estadios.forEach(estadio => {
                    const option = document.createElement('option');
                    option.value = estadio.id;
                    option.textContent = `${estadio.nombre} - ${estadio.ciudad}`;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('Error cargando estadios:', error);
            }
        }

        // Editar partido
        async function editMatch(matchId) {
            currentMatchId = matchId;
            
            try {
                const response = await fetch(`${API_BASE}/partidos/${matchId}`);
                const partido = await response.json();
                
                currentMatchData = partido;
                loadMatchDataInForm(partido);
                new bootstrap.Modal(document.getElementById('editMatchModal')).show();
            } catch (error) {
                showAlert('Error al cargar datos del partido', 'danger');
            }
        }

        // Cargar datos del partido en el formulario
        function loadMatchDataInForm(partido) {
            document.getElementById('matchId').value = partido.id;
            document.getElementById('matchTeams').textContent = 
                `${partido.equipo_local} vs ${partido.equipo_visitante}`;
            document.getElementById('teamLocalName').textContent = partido.equipo_local;
            document.getElementById('teamVisitanteName').textContent = partido.equipo_visitante;
            
            // Mostrar banderas si existen
            const localFlag = document.getElementById('teamLocalFlag');
            const visitanteFlag = document.getElementById('teamVisitanteFlag');
            
            if (partido.logo_local) {
                localFlag.src = partido.logo_local;
                localFlag.style.display = 'block';
            } else {
                localFlag.style.display = 'none';
            }
            
            if (partido.logo_visitante) {
                visitanteFlag.src = partido.logo_visitante;
                visitanteFlag.style.display = 'block';
            } else {
                visitanteFlag.style.display = 'none';
            }
            
            // Cargar datos existentes
            document.getElementById('golesLocal').value = partido.goles_local_regular || 0;
            document.getElementById('golesVisitante').value = partido.goles_visitante_regular || 0;
            document.getElementById('deportividadLocal').value = partido.deportividad_local || 0;
            document.getElementById('deportividadVisitante').value = partido.deportividad_visitante || 0;
            document.getElementById('matchDate').value = partido.fecha_partido || '';
            document.getElementById('matchTime').value = partido.hora_partido || '';
            document.getElementById('matchStadium').value = partido.estadio_id || '';
        }

        // Guardar resultado del partido
        async function saveMatchResult() {
            const matchId = document.getElementById('matchId').value;
            
            const data = {
                goles_local_regular: parseInt(document.getElementById('golesLocal').value) || 0,
                goles_visitante_regular: parseInt(document.getElementById('golesVisitante').value) || 0,
                deportividad_local: parseInt(document.getElementById('deportividadLocal').value) || 0,
                deportividad_visitante: parseInt(document.getElementById('deportividadVisitante').value) || 0,
                fecha_partido: document.getElementById('matchDate').value || null,
                hora_partido: document.getElementById('matchTime').value || null,
                estadio_id: document.getElementById('matchStadium').value || null,
                goles_local_prorroga: 0,
                goles_visitante_prorroga: 0,
                hubo_prorroga: false,
                penales_local: 0,
                penales_visitante: 0,
                hubo_penales: false
            };

            try {
                const response = await fetch(`${API_BASE}/partidos/${matchId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                
                if (response.ok) {
                    showAlert('Resultado guardado correctamente', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('editMatchModal')).hide();
                    
                    // Recargar datos del grupo
                    await loadGroupData();
                } else {
                    showAlert(result.error || 'Error al guardar resultado', 'danger');
                }
            } catch (error) {
                showAlert('Error de conexión: ' + error.message, 'danger');
            }
        }

        // Utilidades
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            if (!dateString) return '';
            // La fecha viene en formato YYYY-MM-DD desde la base de datos
            // Crear la fecha en UTC para evitar problemas de zona horaria
            const parts = dateString.split('-');
            const year = parseInt(parts[0]);
            const month = parseInt(parts[1]) - 1; // Los meses en JS empiezan en 0
            const day = parseInt(parts[2]);
            
            const date = new Date(year, month, day);
            
            return date.toLocaleDateString('es-ES', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }

        function showAlert(message, type) {
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
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            alertContainer.appendChild(alert);

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
    </script>
</body>
</html>
                        