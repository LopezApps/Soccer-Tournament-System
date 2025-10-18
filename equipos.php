<?php
// equipos.php
require_once 'config/database.php';

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
    <title>Gestión de Equipos - Torneo de Fútbol</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .page-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .team-card {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background: white;
            transition: all 0.3s ease;
            height: 100%;
        }
        .team-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transform: translateY(-3px);
        }
        .team-logo-card {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border: 2px solid #e9ecef;
            border-radius: 0.375rem;
            padding: 0.25rem;
            background: #f8f9fa;
            flex-shrink: 0;
        }
        .team-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .team-name {
            font-size: 1rem;
            font-weight: 600;
            color: #2a5298;
            margin: 0;
            line-height: 1.2;
        }
        .team-code {
            display: inline-block;
            background: #2a5298;
            color: white;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            font-weight: bold;
            font-size: 0.75rem;
        }
        .team-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #6c757d;
            font-size: 0.85rem;
        }
        .confederacion-badge {
            display: inline-block;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .UEFA { background-color: #0066cc; color: white; }
        .CONMEBOL { background-color: #dc3545; color: white; }
        .CONCACAF { background-color: #ffc107; color: #000; }
        .CAF { background-color: #28a745; color: white; }
        .AFC { background-color: #6610f2; color: white; }
        .OFC { background-color: #17a2b8; color: white; }
        .group-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            font-weight: bold;
            color: #495057;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .logo-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: contain;
            border: 2px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.5rem;
            background: #f8f9fa;
        }
        .logo-placeholder {
            width: 100px;
            height: 100px;
            border: 2px dashed #dee2e6;
            border-radius: 0.375rem;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        .filter-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 2rem;
        }
        .filter-tab {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin-right: 0.5rem;
            border-radius: 0.375rem 0.375rem 0 0;
            cursor: pointer;
            transition: all 0.2s;
            background: transparent;
            border: none;
            color: #6c757d;
        }
        .filter-tab:hover {
            background: #f8f9fa;
        }
        .filter-tab.active {
            background: #2a5298;
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="mb-0">
                        <i class="fas fa-shield-alt me-3"></i>
                        Gestión de Equipos
                    </h1>
                    <p class="mb-0 mt-2 opacity-75">Administración de equipos participantes</p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-light btn-lg me-2" onclick="window.location.href='index.php'">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Torneo
                    </button>
                    <button class="btn btn-success btn-lg" onclick="showTeamModal()">
                        <i class="fas fa-plus me-2"></i>Agregar Equipo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="container">
        <!-- Filtros -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filterByGroup('all')">
                        <i class="fas fa-globe me-1"></i>Todos (<span id="count-all">0</span>)
                    </button>
                    <button class="filter-tab" onclick="filterByGroup('A')">Grupo A (<span id="count-A">0</span>)</button>
                    <button class="filter-tab" onclick="filterByGroup('B')">Grupo B (<span id="count-B">0</span>)</button>
                    <button class="filter-tab" onclick="filterByGroup('C')">Grupo C (<span id="count-C">0</span>)</button>
                    <button class="filter-tab" onclick="filterByGroup('D')">Grupo D (<span id="count-D">0</span>)</button>
                    <button class="filter-tab" onclick="filterByGroup('E')">Grupo E (<span id="count-E">0</span>)</button>
                    <button class="filter-tab" onclick="filterByGroup('F')">Grupo F (<span id="count-F">0</span>)</button>
                    <button class="filter-tab" onclick="filterByGroup('G')">Grupo G (<span id="count-G">0</span>)</button>
                    <button class="filter-tab" onclick="filterByGroup('H')">Grupo H (<span id="count-H">0</span>)</button>
                </div>
            </div>
        </div>

        <!-- Buscador -->
        <div class="row mb-4">
            <div class="col-md-6">
                <input type="text" class="form-control" id="searchTeam" 
                       placeholder="Buscar por nombre, país o código..." onkeyup="filterTeams()">
            </div>
            <div class="col-md-6 text-end">
                <select class="form-select d-inline-block" style="width: auto;" id="filterConfederation" onchange="filterTeams()">
                    <option value="">Todas las confederaciones</option>
                    <option value="UEFA">UEFA</option>
                    <option value="CONMEBOL">CONMEBOL</option>
                    <option value="CONCACAF">CONCACAF</option>
                    <option value="CAF">CAF</option>
                    <option value="AFC">AFC</option>
                    <option value="OFC">OFC</option>
                </select>
            </div>
        </div>

        <!-- Lista de Equipos -->
        <div class="row" id="teamsContainer">
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <h5>Cargando equipos...</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Agregar/Editar Equipo -->
    <div class="modal fade" id="teamModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-shield-alt me-2"></i><span id="modalTitle">Agregar Equipo</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="teamForm" enctype="multipart/form-data">
                        <input type="hidden" id="teamId">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="teamNombreOficial" class="form-label">
                                        Nombre Oficial del Equipo <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="teamNombreOficial" 
                                           placeholder="Ej: Selección de Brasil" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="teamNombreCorto" class="form-label">
                                                Código FIFA (3 letras) <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control text-uppercase" 
                                                   id="teamNombreCorto" maxlength="3" 
                                                   placeholder="BRA" pattern="[A-Za-z]{3}" required>
                                            <small class="text-muted">3 letras alfabéticas</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="teamPais" class="form-label">
                                                País <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="teamPais" 
                                                   placeholder="Brasil" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="teamConfederacion" class="form-label">
                                                Confederación FIFA <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="teamConfederacion" required>
                                                <option value="">Seleccionar...</option>
                                                <option value="UEFA">UEFA (Europa)</option>
                                                <option value="CONMEBOL">CONMEBOL (Sudamérica)</option>
                                                <option value="CONCACAF">CONCACAF (Norte/Centroamérica)</option>
                                                <option value="CAF">CAF (África)</option>
                                                <option value="AFC">AFC (Asia)</option>
                                                <option value="OFC">OFC (Oceanía)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="teamGroup" class="form-label">
                                                Grupo <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="teamGroup" required>
                                                <option value="">Seleccionar grupo...</option>
                                                <option value="A">Grupo A</option>
                                                <option value="B">Grupo B</option>
                                                <option value="C">Grupo C</option>
                                                <option value="D">Grupo D</option>
                                                <option value="E">Grupo E</option>
                                                <option value="F">Grupo F</option>
                                                <option value="G">Grupo G</option>
                                                <option value="H">Grupo H</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="teamLogo" class="form-label">Logo del Equipo</label>
                                    <input type="file" class="form-control" id="teamLogo" 
                                           accept="image/*" onchange="previewLogo(this)">
                                    <div class="mt-3 text-center">
                                        <img id="logoPreview" src="" class="logo-preview" 
                                             style="display: none;" alt="Preview">
                                        <div id="logoPlaceholder" class="logo-placeholder">
                                            <i class="fas fa-image fa-2x text-muted"></i>
                                        </div>
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
                    <button type="button" class="btn btn-primary" onclick="saveTeam()">
                        <i class="fas fa-save me-2"></i>Guardar Equipo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación para Eliminar -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar el equipo <strong id="deleteTeamName"></strong>?</p>
                    <p class="text-muted mb-0"><small>Esta acción no se puede deshacer. El equipo no se puede eliminar si tiene partidos jugados.</small></p>
                    <input type="hidden" id="deleteTeamId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash me-2"></i>Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        const API_BASE = 'api';
        let teamsData = [];
        let currentFilter = 'all';

        // Cargar equipos al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadTeams();
            
            // Convertir código a mayúsculas automáticamente
            document.getElementById('teamNombreCorto').addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase();
            });
        });

        // Cargar lista de equipos
        async function loadTeams() {
            try {
                const response = await fetch(`${API_BASE}/equipos`);
                teamsData = await response.json();
                updateCounts();
                displayTeams(teamsData);
            } catch (error) {
                console.error('Error cargando equipos:', error);
                document.getElementById('teamsContainer').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error al cargar equipos
                    </div>
                `;
            }
        }

        // Actualizar contadores
        function updateCounts() {
            document.getElementById('count-all').textContent = teamsData.length;
            
            for (let grupo = 'A'.charCodeAt(0); grupo <= 'H'.charCodeAt(0); grupo++) {
                const letter = String.fromCharCode(grupo);
                const count = teamsData.filter(t => t.grupo === letter).length;
                document.getElementById(`count-${letter}`).textContent = count;
            }
        }

        // Mostrar equipos
        function displayTeams(equipos) {
            const container = document.getElementById('teamsContainer');
            
            if (!equipos || equipos.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fas fa-shield-alt"></i>
                            <h5>No hay equipos registrados</h5>
                            <p class="text-muted">Comienza agregando un nuevo equipo</p>
                            <button class="btn btn-primary mt-3" onclick="showTeamModal()">
                                <i class="fas fa-plus me-2"></i>Agregar Primer Equipo
                            </button>
                        </div>
                    </div>
                `;
                return;
            }

            container.innerHTML = '';
            
            equipos.forEach(equipo => {
                const card = createTeamCard(equipo);
                container.appendChild(card);
            });
        }

        // Crear tarjeta de equipo
        function createTeamCard(equipo) {
            const col = document.createElement('div');
            col.className = 'col-md-6 col-lg-4 col-xl-3';
            
            col.innerHTML = `
                <div class="team-card">
                    <div class="team-header">
                        ${equipo.logo ? 
                            `<img src="${equipo.logo}" class="team-logo-card" alt="${equipo.nombre_oficial}">` :
                            `<div class="team-logo-card d-flex align-items-center justify-content-center">
                                <i class="fas fa-shield-alt fa-2x text-muted"></i>
                            </div>`
                        }
                        <div class="flex-grow-1" style="min-width: 0;">
                            <h5 class="team-name">${escapeHtml(equipo.nombre_oficial)}</h5>
                            <div>
                                <span class="team-code">${escapeHtml(equipo.nombre_corto)}</span>
                                <span class="confederacion-badge ${equipo.confederacion} ms-1">${equipo.confederacion}</span>
                            </div>
                        </div>
                        <div class="group-badge">
                            ${equipo.grupo}
                        </div>
                    </div>
                    
                    <div class="team-info">
                        <i class="fas fa-flag"></i>
                        <span>${escapeHtml(equipo.pais)}</span>
                    </div>
                    
                    <div class="mt-2 d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary flex-fill" 
                                onclick="editTeam(${equipo.id})">
                            <i class="fas fa-edit me-1"></i>Editar
                        </button>
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="deleteTeam(${equipo.id}, '${escapeHtml(equipo.nombre_oficial)}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            return col;
        }

        // Filtrar por grupo
        function filterByGroup(grupo) {
            currentFilter = grupo;
            
            // Actualizar tabs activos
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            filterTeams();
        }

        // Filtrar equipos
        function filterTeams() {
            const searchTerm = document.getElementById('searchTeam').value.toLowerCase();
            const confederation = document.getElementById('filterConfederation').value;
            
            let filtered = teamsData;
            
            // Filtrar por grupo
            if (currentFilter !== 'all') {
                filtered = filtered.filter(equipo => equipo.grupo === currentFilter);
            }
            
            // Filtrar por búsqueda
            if (searchTerm) {
                filtered = filtered.filter(equipo => 
                    equipo.nombre_oficial.toLowerCase().includes(searchTerm) ||
                    equipo.nombre_corto.toLowerCase().includes(searchTerm) ||
                    equipo.pais.toLowerCase().includes(searchTerm)
                );
            }
            
            // Filtrar por confederación
            if (confederation) {
                filtered = filtered.filter(equipo => equipo.confederacion === confederation);
            }
            
            displayTeams(filtered);
        }

        // Mostrar modal para agregar
        function showTeamModal() {
            document.getElementById('modalTitle').textContent = 'Agregar Equipo';
            document.getElementById('teamForm').reset();
            document.getElementById('teamId').value = '';
            document.getElementById('logoPreview').style.display = 'none';
            document.getElementById('logoPlaceholder').style.display = 'flex';
            new bootstrap.Modal(document.getElementById('teamModal')).show();
        }

        // Editar equipo
        async function editTeam(id) {
            try {
                const response = await fetch(`${API_BASE}/equipos/${id}`);
                const equipo = await response.json();

                document.getElementById('modalTitle').textContent = 'Editar Equipo';
                document.getElementById('teamId').value = equipo.id;
                document.getElementById('teamNombreOficial').value = equipo.nombre_oficial;
                document.getElementById('teamNombreCorto').value = equipo.nombre_corto;
                document.getElementById('teamPais').value = equipo.pais;
                document.getElementById('teamConfederacion').value = equipo.confederacion;
                document.getElementById('teamGroup').value = equipo.grupo;

                if (equipo.logo) {
                    document.getElementById('logoPreview').src = equipo.logo;
                    document.getElementById('logoPreview').style.display = 'block';
                    document.getElementById('logoPlaceholder').style.display = 'none';
                } else {
                    document.getElementById('logoPreview').style.display = 'none';
                    document.getElementById('logoPlaceholder').style.display = 'flex';
                }

                new bootstrap.Modal(document.getElementById('teamModal')).show();
            } catch (error) {
                showAlert('Error al cargar datos del equipo', 'danger');
            }
        }

        // Guardar equipo
        async function saveTeam() {
            const form = document.getElementById('teamForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const teamId = document.getElementById('teamId').value;
            const formData = new FormData();
            
            formData.append('nombre_oficial', document.getElementById('teamNombreOficial').value);
            formData.append('nombre_corto', document.getElementById('teamNombreCorto').value.toUpperCase());
            formData.append('pais', document.getElementById('teamPais').value);
            formData.append('confederacion', document.getElementById('teamConfederacion').value);
            formData.append('grupo', document.getElementById('teamGroup').value);
            
            const logoFile = document.getElementById('teamLogo').files[0];
            if (logoFile) {
                formData.append('logo', logoFile);
            }

            try {
                const url = teamId ? `${API_BASE}/equipos/${teamId}` : `${API_BASE}/equipos`;
                // Usar POST para ambos casos, FormData no funciona bien con PUT en PHP
                const method = 'POST';
                
                // Si es edición, agregar campo para indicarlo
                if (teamId) {
                    formData.append('_method', 'PUT');
                }
                
                const response = await fetch(url, {
                    method: method,
                    body: formData
                });

                const result = await response.json();
                
                if (response.ok) {
                    showAlert(teamId ? 'Equipo actualizado correctamente' : 'Equipo guardado correctamente', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('teamModal')).hide();
                    loadTeams();
                } else {
                    showAlert(result.error || 'Error al guardar equipo', 'danger');
                }
            } catch (error) {
                showAlert('Error de conexión: ' + error.message, 'danger');
            }
        }

        // Eliminar equipo
        function deleteTeam(id, nombre) {
            document.getElementById('deleteTeamId').value = id;
            document.getElementById('deleteTeamName').textContent = nombre;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Confirmar eliminación
        async function confirmDelete() {
            const id = document.getElementById('deleteTeamId').value;
            
            try {
                const response = await fetch(`${API_BASE}/equipos/${id}`, {
                    method: 'DELETE'
                });

                const result = await response.json();
                
                if (response.ok) {
                    showAlert('Equipo eliminado correctamente', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                    loadTeams();
                } else {
                    showAlert(result.error || 'Error al eliminar equipo', 'danger');
                }
            } catch (error) {
                showAlert('Error de conexión: ' + error.message, 'danger');
            }
        }

        // Preview de logo
        function previewLogo(input) {
            const preview = document.getElementById('logoPreview');
            const placeholder = document.getElementById('logoPlaceholder');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Utilidades
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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
