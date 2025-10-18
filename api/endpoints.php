<?php
// api/endpoints.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../models/Equipo.php';
require_once '../models/Partido.php';
require_once '../models/TorneoManager.php';

$database = new Database();
$db = $database->getConnection();

$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Parsear la URL para obtener el endpoint
$path_parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));
$endpoint = isset($path_parts[1]) ? $path_parts[1] : '';
$action = isset($path_parts[2]) ? $path_parts[2] : '';

try {
    switch ($endpoint) {
        case 'equipos':
            handleEquiposEndpoint($db, $request_method, $action);
            break;
        case 'estadios':
            handleEstadiosEndpoint($db, $request_method, $action);
            break;
        case 'partidos':
            handlePartidosEndpoint($db, $request_method, $action);
            break;
        case 'torneo':
            handleTorneoEndpoint($db, $request_method, $action);
            break;
        case 'estadisticas':
            handleEstadisticasEndpoint($db, $request_method, $action);
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint no encontrado']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Función auxiliar para subir archivos
function uploadFile($file, $folder) {
    $upload_dir = __DIR__ . '/../uploads/' . $folder . '/';
    
    // Crear directorio si no existe
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Tipo de archivo no permitido');
    }
    
    // Limitar tamaño a 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('El archivo es demasiado grande (máx 5MB)');
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'uploads/' . $folder . '/' . $filename;
    }
    
    throw new Exception('Error al subir archivo');
}

function handleEquiposEndpoint($db, $method, $action) {
    // Detectar método override para FormData
    if ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
        $method = 'PUT';
    }
    
    $equipo = new Equipo($db);
    
    switch ($method) {
        case 'GET':
            if ($action === 'grupo') {
                $grupo = $_GET['grupo'] ?? '';
                if ($grupo) {
                    $stmt = $equipo->leerPorGrupo($grupo);
                    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($equipos);
                } else {
                    echo json_encode(['error' => 'Grupo requerido']);
                }
            } elseif ($action && is_numeric($action)) {
                // Obtener equipo específico por ID
                $query = "SELECT * FROM equipos WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$action]);
                $equipo_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($equipo_data) {
                    echo json_encode($equipo_data);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Equipo no encontrado']);
                }
            } else {
                $stmt = $equipo->leerTodos();
                $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($equipos);
            }
            break;
            
        case 'POST':
            // Manejar upload de archivos
            $logo_path = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logo_path = uploadFile($_FILES['logo'], 'logos');
            }
            
            $data = $_POST; // Usar $_POST para form-data con archivos
            
            if (!$data || !isset($data['nombre_oficial'], $data['nombre_corto'], $data['pais'], $data['confederacion'], $data['grupo'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos incompletos']);
                return;
            }
            
            // Verificar que el grupo no tenga más de 4 equipos
            $stmt = $equipo->leerPorGrupo($data['grupo']);
            if ($stmt->rowCount() >= 4) {
                http_response_code(400);
                echo json_encode(['error' => 'El grupo ya tiene 4 equipos']);
                return;
            }
            
            // Verificar que el código FIFA no exista
            $query = "SELECT id FROM equipos WHERE nombre_corto = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([strtoupper($data['nombre_corto'])]);
            if ($stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'El código FIFA ya está registrado']);
                return;
            }
            
            $equipo->nombre_oficial = $data['nombre_oficial'];
            $equipo->nombre_corto = strtoupper($data['nombre_corto']);
            $equipo->pais = $data['pais'];
            $equipo->confederacion = $data['confederacion'];
            $equipo->grupo = $data['grupo'];
            $equipo->logo = $logo_path;
            
            if ($equipo->crear()) {
                echo json_encode(['message' => 'Equipo creado exitosamente', 'logo' => $logo_path]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear equipo']);
            }
            break;
            
        case 'PUT':
            if ($action && is_numeric($action)) {
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                $logo_path = null;
                
                // Cuando viene de FormData con _method=PUT, los datos están en $_POST
                if (isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
                    // Datos vienen de FormData via POST
                    $data = $_POST;
                    
                    // Procesar logo si existe
                    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                        try {
                            $logo_path = uploadFile($_FILES['logo'], 'logos');
                        } catch (Exception $e) {
                            http_response_code(400);
                            echo json_encode(['error' => $e->getMessage()]);
                            return;
                        }
                    }
                } else {
                    // Datos vienen como JSON
                    $data = json_decode(file_get_contents('php://input'), true);
                }
                
                if (!$data || !isset($data['nombre_oficial'], $data['nombre_corto'], $data['pais'], $data['confederacion'], $data['grupo'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Datos incompletos']);
                    return;
                }
                
                // Verificar que el código FIFA no exista en otro equipo
                $query = "SELECT id FROM equipos WHERE nombre_corto = ? AND id != ?";
                $stmt = $db->prepare($query);
                $stmt->execute([strtoupper($data['nombre_corto']), $action]);
                if ($stmt->rowCount() > 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'El código FIFA ya está registrado en otro equipo']);
                    return;
                }
                
                // Construir query de actualización
                if ($logo_path) {
                    $query = "UPDATE equipos SET nombre_oficial=?, nombre_corto=?, pais=?, 
                              confederacion=?, grupo=?, logo=? WHERE id=?";
                    $params = [
                        $data['nombre_oficial'],
                        strtoupper($data['nombre_corto']),
                        $data['pais'],
                        $data['confederacion'],
                        $data['grupo'],
                        $logo_path,
                        $action
                    ];
                } else {
                    $query = "UPDATE equipos SET nombre_oficial=?, nombre_corto=?, pais=?, 
                              confederacion=?, grupo=? WHERE id=?";
                    $params = [
                        $data['nombre_oficial'],
                        strtoupper($data['nombre_corto']),
                        $data['pais'],
                        $data['confederacion'],
                        $data['grupo'],
                        $action
                    ];
                }
                
                $stmt = $db->prepare($query);
                if ($stmt->execute($params)) {
                    echo json_encode(['message' => 'Equipo actualizado exitosamente', 'logo' => $logo_path]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Error al actualizar equipo']);
                }
            }
            break;
            
        case 'DELETE':
            if ($action && is_numeric($action)) {
                // Verificar si el equipo tiene partidos jugados
                $query = "SELECT COUNT(*) as count FROM partidos 
                          WHERE (equipo_local_id = ? OR equipo_visitante_id = ?) 
                          AND partido_jugado = 1";
                $stmt = $db->prepare($query);
                $stmt->execute([$action, $action]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'No se puede eliminar el equipo porque tiene partidos jugados']);
                    return;
                }
                
                // Eliminar el equipo
                $query = "DELETE FROM equipos WHERE id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$action])) {
                    echo json_encode(['message' => 'Equipo eliminado exitosamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Error al eliminar equipo']);
                }
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
}

// Endpoint para estadios
function handleEstadiosEndpoint($db, $method, $action) {
    // Detectar método override para FormData
    if ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
        $method = 'PUT';
    }
    
    switch ($method) {
        case 'GET':
            if ($action && is_numeric($action)) {
                // Obtener estadio específico
                $query = "SELECT * FROM estadios WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$action]);
                $estadio = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($estadio) {
                    echo json_encode($estadio);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Estadio no encontrado']);
                }
            } else {
                $query = "SELECT * FROM estadios ORDER BY nombre";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $estadios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($estadios);
            }
            break;
            
        case 'POST':
            // Manejar upload de imagen
            $imagen_path = null;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $imagen_path = uploadFile($_FILES['imagen'], 'estadios');
            }
            
            $data = $_POST;
            
            if (!$data || !isset($data['nombre'], $data['ciudad'], $data['pais'], $data['capacidad'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos incompletos']);
                return;
            }
            
            $query = "INSERT INTO estadios SET nombre=?, ciudad=?, pais=?, capacidad=?, imagen=?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([
                $data['nombre'],
                $data['ciudad'],
                $data['pais'],
                $data['capacidad'],
                $imagen_path
            ])) {
                echo json_encode(['message' => 'Estadio creado exitosamente', 'imagen' => $imagen_path]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear estadio']);
            }
            break;
            
        case 'PUT':
            if ($action && is_numeric($action)) {
                // Cuando viene de FormData con _method=PUT, los datos están en $_POST
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                $imagen_path = null;
                
                if (isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
                    // Datos vienen de FormData via POST
                    $data = $_POST;
                    
                    // Procesar imagen si existe
                    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                        try {
                            $imagen_path = uploadFile($_FILES['imagen'], 'estadios');
                        } catch (Exception $e) {
                            http_response_code(400);
                            echo json_encode(['error' => $e->getMessage()]);
                            return;
                        }
                    }
                } else {
                    // Datos vienen como JSON
                    $data = json_decode(file_get_contents('php://input'), true);
                }
                
                // Validar datos requeridos
                if (!$data || !isset($data['nombre']) || !isset($data['ciudad']) || !isset($data['pais']) || !isset($data['capacidad'])) {
                    http_response_code(400);
                    echo json_encode([
                        'error' => 'Datos incompletos',
                        'received' => $data,
                        'content_type' => $contentType
                    ]);
                    return;
                }
                
                // Construir query según si hay imagen nueva o no
                if ($imagen_path) {
                    $query = "UPDATE estadios SET nombre=?, ciudad=?, pais=?, capacidad=?, imagen=? WHERE id=?";
                    $params = [
                        $data['nombre'], 
                        $data['ciudad'], 
                        $data['pais'], 
                        $data['capacidad'], 
                        $imagen_path, 
                        $action
                    ];
                } else {
                    $query = "UPDATE estadios SET nombre=?, ciudad=?, pais=?, capacidad=? WHERE id=?";
                    $params = [
                        $data['nombre'], 
                        $data['ciudad'], 
                        $data['pais'], 
                        $data['capacidad'], 
                        $action
                    ];
                }
                
                $stmt = $db->prepare($query);
                
                if ($stmt->execute($params)) {
                    echo json_encode([
                        'message' => 'Estadio actualizado exitosamente', 
                        'imagen' => $imagen_path
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Error al actualizar estadio']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID de estadio no válido']);
            }
            break;
            
        case 'DELETE':
            if ($action && is_numeric($action)) {
                // Verificar si el estadio está en uso
                $query = "SELECT COUNT(*) as count FROM partidos WHERE estadio_id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$action]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'No se puede eliminar el estadio porque tiene partidos asignados']);
                    return;
                }
                
                $query = "DELETE FROM estadios WHERE id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$action])) {
                    echo json_encode(['message' => 'Estadio eliminado exitosamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Error al eliminar estadio']);
                }
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
}

function handlePartidosEndpoint($db, $method, $action) {
    $partido = new Partido($db);
    
    switch ($method) {
        case 'GET':
            if ($action === 'grupo') {
                $grupo = $_GET['grupo'] ?? '';
                if ($grupo) {
                    $stmt = $partido->leerPorGrupo($grupo);
                    $partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($partidos);
                } else {
                    echo json_encode(['error' => 'Grupo requerido']);
                }
            } elseif ($action === 'fase') {
                $fase_id = $_GET['fase_id'] ?? '';
                if ($fase_id) {
                    $stmt = $partido->leerPorFase($fase_id);
                    $partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($partidos);
                } else {
                    echo json_encode(['error' => 'Fase requerida']);
                }
            } elseif ($action && is_numeric($action)) {
                // Obtener partido específico por ID con información completa
                $query = "SELECT p.*, 
                                 el.nombre_oficial as equipo_local, el.nombre_corto as codigo_local, 
                                 el.pais as pais_local, el.logo as logo_local,
                                 ev.nombre_oficial as equipo_visitante, ev.nombre_corto as codigo_visitante,
                                 ev.pais as pais_visitante, ev.logo as logo_visitante,
                                 e.nombre as estadio_nombre, e.ciudad as estadio_ciudad
                          FROM partidos p
                          LEFT JOIN equipos el ON p.equipo_local_id = el.id
                          LEFT JOIN equipos ev ON p.equipo_visitante_id = ev.id
                          LEFT JOIN estadios e ON p.estadio_id = e.id
                          WHERE p.id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$action]);
                $partido_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($partido_data) {
                    echo json_encode($partido_data);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Partido no encontrado']);
                }
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['fase_id'], $data['equipo_local_id'], $data['equipo_visitante_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos incompletos']);
                return;
            }
            
            $partido->fase_id = $data['fase_id'];
            $partido->equipo_local_id = $data['equipo_local_id'];
            $partido->equipo_visitante_id = $data['equipo_visitante_id'];
            $partido->fecha_partido = $data['fecha_partido'] ?? null;
            $partido->hora_partido = $data['hora_partido'] ?? null;
            $partido->estadio_id = $data['estadio_id'] ?? null;
            $partido->grupo = $data['grupo'] ?? null;
            $partido->jornada = $data['jornada'] ?? null;
            
            if ($partido->crear()) {
                echo json_encode(['message' => 'Partido creado exitosamente']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear partido']);
            }
            break;
            
        case 'PUT':
            if ($action && is_numeric($action)) {
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!$data) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Datos requeridos']);
                    return;
                }
                
                $partido->id = $action;
                $partido->goles_local_regular = $data['goles_local_regular'] ?? 0;
                $partido->goles_visitante_regular = $data['goles_visitante_regular'] ?? 0;
                $partido->goles_local_prorroga = $data['goles_local_prorroga'] ?? 0;
                $partido->goles_visitante_prorroga = $data['goles_visitante_prorroga'] ?? 0;
                $partido->hubo_prorroga = $data['hubo_prorroga'] ?? false;
                $partido->penales_local = $data['penales_local'] ?? 0;
                $partido->penales_visitante = $data['penales_visitante'] ?? 0;
                $partido->hubo_penales = $data['hubo_penales'] ?? false;
                $partido->deportividad_local = $data['deportividad_local'] ?? 0;
                $partido->deportividad_visitante = $data['deportividad_visitante'] ?? 0;
                $partido->fecha_partido = $data['fecha_partido'] ?? null;
                $partido->hora_partido = $data['hora_partido'] ?? null;
                $partido->estadio_id = $data['estadio_id'] ?? null;
                
                // Obtener datos del partido para determinar ganador
                $query = "SELECT equipo_local_id, equipo_visitante_id, fase_id FROM partidos WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$action]);
                $partido_info = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($partido_info) {
                    $partido->equipo_local_id = $partido_info['equipo_local_id'];
                    $partido->equipo_visitante_id = $partido_info['equipo_visitante_id'];
                    
                    if ($partido->actualizarResultado()) {
                        // Actualizar estadísticas de grupos si es fase de grupos
                        if ($partido_info['fase_id'] == 1) {
                            $torneo = new TorneoManager($db);
                            $torneo->actualizarEstadisticasGrupos();
                        }
                        
                        echo json_encode(['message' => 'Resultado actualizado exitosamente']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Error al actualizar resultado']);
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Partido no encontrado']);
                }
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
}

function handleTorneoEndpoint($db, $method, $action) {
    $torneo = new TorneoManager($db);
    
    switch ($method) {
        case 'POST':
            switch ($action) {
                case 'generar-grupos':
                    try {
                        $torneo->generarPartidosFaseGrupos();
                        echo json_encode(['message' => 'Partidos de fase de grupos generados exitosamente']);
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(['error' => 'Error al generar partidos: ' . $e->getMessage()]);
                    }
                    break;
                    
                case 'generar-octavos':
                    try {
                        $torneo->generarOctavosFinal();
                        echo json_encode(['message' => 'Partidos de octavos de final generados exitosamente']);
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(['error' => 'Error al generar octavos: ' . $e->getMessage()]);
                    }
                    break;
                    
                case 'actualizar-estadisticas':
                    try {
                        $torneo->actualizarEstadisticasGrupos();
                        echo json_encode(['message' => 'Estadísticas actualizadas exitosamente']);
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(['error' => 'Error al actualizar estadísticas: ' . $e->getMessage()]);
                    }
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Acción no encontrada']);
                    break;
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
}

function handleEstadisticasEndpoint($db, $method, $action) {
    switch ($method) {
        case 'GET':
            if ($action === 'grupos') {
                $grupo = $_GET['grupo'] ?? '';
                if ($grupo) {
                    // Obtener estadísticas de un grupo específico
                    $query = "SELECT eg.*, e.nombre_oficial, e.nombre_corto, e.pais, e.logo, e.confederacion
                              FROM estadisticas_grupos eg 
                              JOIN equipos e ON eg.equipo_id = e.id 
                              WHERE eg.grupo = ? 
                              ORDER BY eg.posicion_grupo";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$grupo]);
                    $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($estadisticas);
                } else {
                    // Obtener estadísticas de todos los grupos
                    $query = "SELECT eg.*, e.nombre_oficial, e.nombre_corto, e.pais, e.logo, e.confederacion
                              FROM estadisticas_grupos eg 
                              JOIN equipos e ON eg.equipo_id = e.id 
                              ORDER BY eg.grupo, eg.posicion_grupo";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Agrupar por grupo
                    $grupos = [];
                    foreach ($estadisticas as $stat) {
                        $grupos[$stat['grupo']][] = $stat;
                    }
                    
                    echo json_encode($grupos);
                }
            } elseif ($action === 'clasificados') {
                // Obtener equipos clasificados a octavos
                $query = "SELECT eg.*, e.nombre_oficial, e.nombre_corto, e.pais, e.logo, e.confederacion
                          FROM estadisticas_grupos eg 
                          JOIN equipos e ON eg.equipo_id = e.id 
                          WHERE eg.clasificado = 1 
                          ORDER BY eg.grupo, eg.posicion_grupo";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $clasificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($clasificados);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Acción no válida']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
}
