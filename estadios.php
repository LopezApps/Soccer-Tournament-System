<?php
// estadios.php
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
    <title>Gestión de Estadios - Torneo de Fútbol</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .page-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stadium-card {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            background: white;
        }
        .stadium-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transform: translateY(-5px);
        }
        .stadium-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stadium-image.no-image {
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .stadium-body {
            padding: 1.5rem;
        }
        .stadium-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2a5298;
            margin-bottom: 0.5rem;
        }
        .stadium-info {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            color: #6c757d;
        }
        .stadium-info i {
            width: 20px;
            margin-right: 0.5rem;
        }
        .capacity-badge {
            display: inline-block;
            background: #e9ecef;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #495057;
        }
        .capacity-badge i {
            color: #2a5298;
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
        .logo-preview {
            max-width: 100%;
            max-height: 150px;
            object-fit: cover;
            border-radius: 0.375rem;
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
                        <i class="fas fa-stadium me-3"></i>
                        Gestión de Estadios
                    </h1>
                    <p class="mb-0 mt-2 opacity-75">Administración de sedes del torneo</p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-light btn-lg me-2" onclick="window.location.href='index.php'">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Torneo
                    </button>
                    <button class="btn btn-success btn-lg" onclick="showStadiumModal()">
                        <i class="fas fa-plus me-2"></i>Agregar Estadio
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-8">
                <h4 class="mb-0">Lista de Estadios</h4>
                <p class="text-muted mb-0">Total: <span id="stadiumCount">0</span> estadios registrados</p>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" id="searchStadium" 
                       placeholder="Buscar estadio..." onkeyup="filterStadiums()">
            </div>
        </div>

        <div class="row" id="stadiumsContainer">
            <!-- Los estadios se cargarán aquí dinámicamente -->
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <h5>Cargando estadios...</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Agregar/Editar Estadio -->
    <div class="modal fade" id="stadiumModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-stadium me-2"></i><span id="modalTitle">Agregar Estadio</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="stadiumForm" enctype="multipart/form-data">
                        <input type="hidden" id="stadiumId">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="stadiumNombre" class="form-label">
                                        Nombre del Estadio <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="stadiumNombre" 
                                           placeholder="Ej: Estadio Maracanã" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="stadiumCiudad" class="form-label">
                                                Ciudad <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="stadiumCiudad" 
                                                   placeholder="Río de Janeiro" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="stadiumPais" class="form-label">
                                                País <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="stadiumPais" 
                                                   placeholder="Brasil" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="stadiumCapacidad" class="form-label">
                                        Capacidad <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="stadiumCapacidad" 
                                           placeholder="78838" min="1000" required>
                                    <small class="text-muted">Número de espectadores</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="stadiumImagen" class="form-label">Imagen del Estadio</label>
                                    <input type="file" class="form-control" id="stadiumImagen" 
                                           accept="image/*" onchange="previewStadiumImage(this)">
                                    <div class="mt-3 text-center">
                                        <img id="stadiumImagePreview" src="" class="logo-preview" 
                                             style="display: none;" alt="Preview">
                                        <div id="stadiumImagePlaceholder" 
                                             style="width: 100%; height: 150px; border: 2px dashed #dee2e6; border-radius: 0.375rem; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image fa-3x text-muted"></i>
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
                    <button type="button" class="btn btn-primary" onclick="saveStadium()">
                        <i class="fas fa-save me-2"></i>Guardar Estadio
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
                    <p>¿Está seguro que desea eliminar el estadio <strong id="deleteStadiumName"></strong>?</p>
                    <p class="text-muted mb-0"><small>Esta acción no se puede deshacer.</small></p>
                    <input type="hidden" id="deleteStadiumId">
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
        let stadiumsData = [];

        // Cargar estadios al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadStadiums();
        });

        // Cargar lista de estadios
        async function loadStadiums() {
            try {
                const response = await fetch(`${API_BASE}/estadios`);
                stadiumsData = await response.json();
                displayStadiums(stadiumsData);
                document.getElementById('stadiumCount').textContent = stadiumsData.length;
            } catch (error) {
                console.error('Error cargando estadios:', error);
                document.getElementById('stadiumsContainer').innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error al cargar estadios
                        </div>
                    </div>
                `;
            }
        }

        // Mostrar estadios
        function displayStadiums(estadios) {
            const container = document.getElementById('stadiumsContainer');
            
            if (!estadios || estadios.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fas fa-stadium"></i>
                            <h5>No hay estadios registrados</h5>
                            <p class="text-muted">Comienza agregando un nuevo estadio</p>
                            <button class="btn btn-primary mt-3" onclick="showStadiumModal()">
                                <i class="fas fa-plus me-2"></i>Agregar Primer Estadio
                            </button>
                        </div>
                    </div>
                `;
                return;
            }

            container.innerHTML = '';
            
            estadios.forEach(estadio => {
                const card = createStadiumCard(estadio);
                container.appendChild(card);
            });
        }

        // Crear tarjeta de estadio
        function createStadiumCard(estadio) {
            const col = document.createElement('div');
            col.className = 'col-md-6 col-lg-4';
            
            col.innerHTML = `
                <div class="stadium-card">
                    ${estadio.imagen ? 
                        `<img src="${estadio.imagen}" class="stadium-image" alt="${estadio.nombre}">` :
                        `<div class="stadium-image no-image">
                            <i class="fas fa-stadium fa-4x"></i>
                        </div>`
                    }
                    <div class="stadium-body">
                        <h5 class="stadium-title">${escapeHtml(estadio.nombre)}</h5>
                        
                        <div class="stadium-info">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${escapeHtml(estadio.ciudad)}, ${escapeHtml(estadio.pais)}</span>
                        </div>
                        
                        <div class="stadium-info">
                            <i class="fas fa-users"></i>
                            <span class="capacity-badge">
                                <i class="fas fa-chair"></i>
                                ${formatNumber(estadio.capacidad)} espectadores
                            </span>
                        </div>
                        
                        <div class="mt-3 d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary flex-fill" 
                                    onclick="editStadium(${estadio.id})">
                                <i class="fas fa-edit me-1"></i>Editar
                            </button>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="deleteStadium(${estadio.id}, '${escapeHtml(estadio.nombre)}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            return col;
        }

        // Mostrar modal para agregar
        function showStadiumModal() {
            document.getElementById('modalTitle').textContent = 'Agregar Estadio';
            document.getElementById('stadiumForm').reset();
            document.getElementById('stadiumId').value = '';
            document.getElementById('stadiumImagePreview').style.display = 'none';
            document.getElementById('stadiumImagePlaceholder').style.display = 'flex';
            new bootstrap.Modal(document.getElementById('stadiumModal')).show();
        }

        // Editar estadio
        async function editStadium(id) {
            try {
                // Obtener directamente de la API en lugar de confiar en stadiumsData
                const response = await fetch(`${API_BASE}/estadios/${id}`);
                const estadio = await response.json();

                document.getElementById('modalTitle').textContent = 'Editar Estadio';
                document.getElementById('stadiumId').value = estadio.id;
                document.getElementById('stadiumNombre').value = estadio.nombre;
                document.getElementById('stadiumCiudad').value = estadio.ciudad;
                document.getElementById('stadiumPais').value = estadio.pais;
                document.getElementById('stadiumCapacidad').value = estadio.capacidad;

                if (estadio.imagen) {
                    document.getElementById('stadiumImagePreview').src = estadio.imagen;
                    document.getElementById('stadiumImagePreview').style.display = 'block';
                    document.getElementById('stadiumImagePlaceholder').style.display = 'none';
                } else {
                    document.getElementById('stadiumImagePreview').style.display = 'none';
                    document.getElementById('stadiumImagePlaceholder').style.display = 'flex';
                }

                new bootstrap.Modal(document.getElementById('stadiumModal')).show();
            } catch (error) {
                showAlert('Error al cargar datos del estadio', 'danger');
            }
        }

        // Guardar estadio
        async function saveStadium() {
            const form = document.getElementById('stadiumForm');
            
            // Validación HTML5
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const stadiumId = document.getElementById('stadiumId').value;
            const formData = new FormData();
            
            formData.append('nombre', document.getElementById('stadiumNombre').value);
            formData.append('ciudad', document.getElementById('stadiumCiudad').value);
            formData.append('pais', document.getElementById('stadiumPais').value);
            formData.append('capacidad', document.getElementById('stadiumCapacidad').value);
            
            const imagenFile = document.getElementById('stadiumImagen').files[0];
            if (imagenFile) {
                formData.append('imagen', imagenFile);
            }

            try {
                const url = stadiumId ? `${API_BASE}/estadios/${stadiumId}` : `${API_BASE}/estadios`;
                // Usar POST para ambos casos, FormData no funciona bien con PUT en PHP
                const method = 'POST';
                
                // Si es edición, agregar campo para indicarlo
                if (stadiumId) {
                    formData.append('_method', 'PUT');
                }
                
                const response = await fetch(url, {
                    method: method,
                    body: formData
                });

                const result = await response.json();
                
                if (response.ok) {
                    showAlert(stadiumId ? 'Estadio actualizado correctamente' : 'Estadio guardado correctamente', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('stadiumModal')).hide();
                    loadStadiums();
                } else {
                    showAlert(result.error || 'Error al guardar estadio', 'danger');
                }
            } catch (error) {
                showAlert('Error de conexión: ' + error.message, 'danger');
            }
        }

        // Eliminar estadio
        function deleteStadium(id, nombre) {
            document.getElementById('deleteStadiumId').value = id;
            document.getElementById('deleteStadiumName').textContent = nombre;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Confirmar eliminación
        async function confirmDelete() {
            const id = document.getElementById('deleteStadiumId').value;
            
            try {
                const response = await fetch(`${API_BASE}/estadios/${id}`, {
                    method: 'DELETE'
                });

                const result = await response.json();
                
                if (response.ok) {
                    showAlert('Estadio eliminado correctamente', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                    loadStadiums();
                } else {
                    showAlert(result.error || 'Error al eliminar estadio', 'danger');
                }
            } catch (error) {
                showAlert('Error de conexión: ' + error.message, 'danger');
            }
        }

        // Filtrar estadios
        function filterStadiums() {
            const searchTerm = document.getElementById('searchStadium').value.toLowerCase();
            const filtered = stadiumsData.filter(estadio => 
                estadio.nombre.toLowerCase().includes(searchTerm) ||
                estadio.ciudad.toLowerCase().includes(searchTerm) ||
                estadio.pais.toLowerCase().includes(searchTerm)
            );
            displayStadiums(filtered);
        }

        // Preview de imagen
        function previewStadiumImage(input) {
            const preview = document.getElementById('stadiumImagePreview');
            const placeholder = document.getElementById('stadiumImagePlaceholder');
            
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

        function formatNumber(num) {
            return new Intl.NumberFormat('es-ES').format(num);
        }

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
    </script>
</body>
</html>
