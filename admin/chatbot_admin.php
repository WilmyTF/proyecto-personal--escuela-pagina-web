<?php
// Verificar si el usuario está autenticado y tiene permisos de administrador
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Incluir el archivo de conexión a la base de datos
require_once '../includes/conexion.php';

// Procesar el formulario de agregar/editar pregunta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // Agregar nueva pregunta y respuesta
            $pregunta = $_POST['pregunta'];
            $respuesta = $_POST['respuesta'];
            $categoria = $_POST['categoria'];
            
            $sql = "INSERT INTO respuestas_chatbot (pregunta, respuesta, categoria) VALUES (:pregunta, :respuesta, :categoria)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':pregunta', $pregunta, PDO::PARAM_STR);
            $stmt->bindParam(':respuesta', $respuesta, PDO::PARAM_STR);
            $stmt->bindParam(':categoria', $categoria, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                $mensaje = "Pregunta y respuesta agregadas correctamente.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al agregar la pregunta y respuesta.";
                $tipo_mensaje = "danger";
            }
        } elseif ($_POST['action'] === 'edit' && isset($_POST['id'])) {
            // Editar pregunta y respuesta existente
            $id = $_POST['id'];
            $pregunta = $_POST['pregunta'];
            $respuesta = $_POST['respuesta'];
            $categoria = $_POST['categoria'];
            
            $sql = "UPDATE respuestas_chatbot SET pregunta = :pregunta, respuesta = :respuesta, categoria = :categoria, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':pregunta', $pregunta, PDO::PARAM_STR);
            $stmt->bindParam(':respuesta', $respuesta, PDO::PARAM_STR);
            $stmt->bindParam(':categoria', $categoria, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                $mensaje = "Pregunta y respuesta actualizadas correctamente.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar la pregunta y respuesta.";
                $tipo_mensaje = "danger";
            }
        } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            // Eliminar pregunta y respuesta
            $id = $_POST['id'];
            
            $sql = "DELETE FROM respuestas_chatbot WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $mensaje = "Pregunta y respuesta eliminadas correctamente.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al eliminar la pregunta y respuesta.";
                $tipo_mensaje = "danger";
            }
        }
    }
}

// Obtener todas las preguntas y respuestas
$sql = "SELECT * FROM respuestas_chatbot ORDER BY categoria, pregunta";
$stmt = $conn->prepare($sql);
$stmt->execute();
$preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías únicas
$categorias = [];
foreach ($preguntas as $pregunta) {
    if (!in_array($pregunta['categoria'], $categorias)) {
        $categorias[] = $pregunta['categoria'];
    }
}
sort($categorias);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración del Chatbot</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
        .table-responsive {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Administración del Chatbot</h1>
        
        <?php if (isset($mensaje)): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Agregar Nueva Pregunta</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <input type="hidden" name="action" value="add">
                            
                            <div class="mb-3">
                                <label for="pregunta" class="form-label">Pregunta</label>
                                <input type="text" class="form-control" id="pregunta" name="pregunta" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="respuesta" class="form-label">Respuesta</label>
                                <textarea class="form-control" id="respuesta" name="respuesta" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="categoria" class="form-label">Categoría</label>
                                <input type="text" class="form-control" id="categoria" name="categoria" list="categorias" required>
                                <datalist id="categorias">
                                    <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo htmlspecialchars($categoria); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Agregar</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Preguntas y Respuestas Existentes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Pregunta</th>
                                        <th>Respuesta</th>
                                        <th>Categoría</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($preguntas as $pregunta): ?>
                                    <tr>
                                        <td><?php echo $pregunta['id']; ?></td>
                                        <td><?php echo htmlspecialchars($pregunta['pregunta']); ?></td>
                                        <td><?php echo htmlspecialchars($pregunta['respuesta']); ?></td>
                                        <td><?php echo htmlspecialchars($pregunta['categoria']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $pregunta['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $pregunta['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            
                                            <!-- Modal de edición -->
                                            <div class="modal fade" id="editModal<?php echo $pregunta['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $pregunta['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel<?php echo $pregunta['id']; ?>">Editar Pregunta y Respuesta</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="post" action="">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="edit">
                                                                <input type="hidden" name="id" value="<?php echo $pregunta['id']; ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label for="edit_pregunta<?php echo $pregunta['id']; ?>" class="form-label">Pregunta</label>
                                                                    <input type="text" class="form-control" id="edit_pregunta<?php echo $pregunta['id']; ?>" name="pregunta" value="<?php echo htmlspecialchars($pregunta['pregunta']); ?>" required>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="edit_respuesta<?php echo $pregunta['id']; ?>" class="form-label">Respuesta</label>
                                                                    <textarea class="form-control" id="edit_respuesta<?php echo $pregunta['id']; ?>" name="respuesta" rows="3" required><?php echo htmlspecialchars($pregunta['respuesta']); ?></textarea>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="edit_categoria<?php echo $pregunta['id']; ?>" class="form-label">Categoría</label>
                                                                    <input type="text" class="form-control" id="edit_categoria<?php echo $pregunta['id']; ?>" name="categoria" value="<?php echo htmlspecialchars($pregunta['categoria']); ?>" list="categorias" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Modal de eliminación -->
                                            <div class="modal fade" id="deleteModal<?php echo $pregunta['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $pregunta['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $pregunta['id']; ?>">Confirmar Eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>¿Estás seguro de que deseas eliminar esta pregunta y respuesta?</p>
                                                            <p><strong>Pregunta:</strong> <?php echo htmlspecialchars($pregunta['pregunta']); ?></p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <form method="post" action="">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="id" value="<?php echo $pregunta['id']; ?>">
                                                                <button type="submit" class="btn btn-danger">Eliminar</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 