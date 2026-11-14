<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require_once 'conexion.php';

// 1. Obtener todos los animales
$stmtAnimales = $pdo->query("SELECT * FROM animales ORDER BY fecha_ingreso DESC");
$animales = $stmtAnimales->fetchAll();

// 2. Obtener las solicitudes de adopción junto con el animal
$sqlSolicitudes = "SELECT s.*, a.nombre AS nombre_animal, a.especie 
                   FROM solicitudes s 
                   INNER JOIN animales a ON s.animal_id = a.id 
                   ORDER BY s.fecha_solicitud DESC";
$stmtSolicitudes = $pdo->query($sqlSolicitudes);
$solicitudes = $stmtSolicitudes->fetchAll();

// Mensajes informativos
$mensajes = [
    'creado'    => 'Nuevo animal registrado correctamente en el catálogo.',
    'editado'   => 'Ficha del animal actualizada con éxito.',
    'estado'    => 'Estado de adopción actualizado correctamente.',
    'eliminado' => 'Registro del animal eliminado de la base de datos.'
];

$errores = [
    'campos_invalidos' => 'Por favor, revisa que todos los campos obligatorios estén rellenos.',
    'falta_imagen'     => 'Es obligatorio subir una fotografía para registrar un animal.',
    'imagen_invalida'  => 'La imagen debe ser JPG, PNG o WEBP y pesar menos de 5 MB.',
    'bd'               => 'Ha ocurrido un error al procesar la operación en la base de datos.'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Animal Hope</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Barra de navegación -->
    <nav class="navbar navbar-dark bg-dark px-4">
        <span class="navbar-brand fw-bold">🐾 Animal Hope | Panel de Administración</span>
        <div class="d-flex align-items-center text-white">
            <span class="me-3">Hola, <strong><?= htmlspecialchars($_SESSION['admin']) ?></strong></span>
            <a href="index.php" class="btn btn-outline-light btn-sm me-2" target="_blank">Ver Web Pública</a>
            <a href="logout.php" class="btn btn-danger btn-sm">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="container my-5">

        <!-- Alertas de feedback -->
        <?php if (isset($_GET['msg']) && isset($mensajes[$_GET['msg']])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                ✅ <?= htmlspecialchars($mensajes[$_GET['msg']]) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['err']) && isset($errores[$_GET['err']])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                ⚠️ <?= htmlspecialchars($errores[$_GET['err']]) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- SECCIÓN 1: GESTIÓN DE ANIMALES (CRUD) -->
        <div class="card shadow-sm mb-5">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">🐶 Catálogo de Animales Registrados (<?= count($animales) ?>)</h5>
                <button class="btn btn-light btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalCrear">
                    + Nuevo Animal
                </button>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Foto</th>
                            <th>Nombre</th>
                            <th>Especie / Raza</th>
                            <th>Edad</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($animales) > 0): ?>
                            <?php foreach ($animales as $animal): ?>
                                <tr>
                                    <td>
                                        <img src="img/<?= htmlspecialchars($animal['imagen']) ?>" 
                                             alt="<?= htmlspecialchars($animal['nombre']) ?>" 
                                             style="width: 55px; height: 55px; object-fit: cover; border-radius: 8px;">
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($animal['nombre']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($animal['especie']) ?> 
                                        <small class="text-muted d-block"><?= htmlspecialchars($animal['raza']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($animal['edad']) ?></td>
                                    <td>
                                        <?php if ($animal['estado'] === 'Disponible'): ?>
                                            <span class="badge bg-success">Disponible</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Adoptado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <!-- Botón cambio rápido de estado -->
                                            <form action="procesar_animal.php" method="POST" class="d-inline">
                                                <input type="hidden" name="accion" value="cambiar_estado">
                                                <input type="hidden" name="id" value="<?= $animal['id'] ?>">
                                                <?php if ($animal['estado'] === 'Disponible'): ?>
                                                    <input type="hidden" name="nuevo_estado" value="Adoptado">
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Marcar como Adoptado">
                                                        Marcar Adoptado
                                                    </button>
                                                <?php else: ?>
                                                    <input type="hidden" name="nuevo_estado" value="Disponible">
                                                    <button type="submit" class="btn btn-outline-success btn-sm" title="Volver a Disponible">
                                                        Poner Disponible
                                                    </button>
                                                <?php endif; ?>
                                            </form>

                                            <!-- Botón Editar (abre modal con datos del animal) -->
                                            <button type="button" class="btn btn-warning btn-sm"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEditar"
                                                    data-id="<?= $animal['id'] ?>"
                                                    data-nombre="<?= htmlspecialchars($animal['nombre']) ?>"
                                                    data-especie="<?= htmlspecialchars($animal['especie']) ?>"
                                                    data-raza="<?= htmlspecialchars($animal['raza']) ?>"
                                                    data-edad="<?= htmlspecialchars($animal['edad']) ?>"
                                                    data-descripcion="<?= htmlspecialchars($animal['descripcion']) ?>"
                                                    data-estado="<?= htmlspecialchars($animal['estado']) ?>">
                                                Editar
                                            </button>

                                            <!-- Botón Eliminar -->
                                            <form action="procesar_animal.php" method="POST" class="d-inline"
                                                  onsubmit="return confirm('¿Seguro que deseas eliminar a <?= htmlspecialchars($animal['nombre']) ?>? Se borrarán también sus solicitudes asociadas.');">
                                                <input type="hidden" name="accion" value="eliminar">
                                                <input type="hidden" name="id" value="<?= $animal['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">No hay animales registrados en la base de datos.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SECCIÓN 2: BANDEJA DE SOLICITUDES DE ADOPCIÓN -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">📩 Bandeja de Solicitudes de Adopción (<?= count($solicitudes) ?>)</h5>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Animal Solicitado</th>
                            <th>Solicitante</th>
                            <th>Contacto</th>
                            <th>Motivación / Mensaje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($solicitudes) > 0): ?>
                            <?php foreach ($solicitudes as $sol): ?>
                                <tr>
                                    <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($sol['fecha_solicitud'])) ?></td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars($sol['nombre_animal']) ?></span>
                                        <small class="text-muted d-block"><?= htmlspecialchars($sol['especie']) ?></small>
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($sol['nombre_solicitante']) ?></td>
                                    <td class="small">
                                        📧 <a href="mailto:<?= htmlspecialchars($sol['email']) ?>"><?= htmlspecialchars($sol['email']) ?></a><br>
                                        📞 <?= htmlspecialchars($sol['telefono']) ?>
                                    </td>
                                    <td class="small"><?= nl2br(htmlspecialchars($sol['mensaje'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Todavía no se han recibido solicitudes de adopción.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODAL 1: CREAR NUEVO ANIMAL -->
    <div class="modal fade" id="modalCrear" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="procesar_animal.php" method="POST" enctype="multipart/form-data" class="modal-content">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Registrar Nuevo Animal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Especie *</label>
                            <select name="especie" class="form-select" required>
                                <option value="Perro">Perro</option>
                                <option value="Gato">Gato</option>
                                <option value="Otros">Otros</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado *</label>
                            <select name="estado" class="form-select" required>
                                <option value="Disponible">Disponible</option>
                                <option value="Adoptado">Adoptado</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Raza *</label>
                            <input type="text" name="raza" class="form-control" placeholder="Ej: Mestizo" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Edad *</label>
                            <input type="text" name="edad" class="form-control" placeholder="Ej: 2 años" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción *</label>
                        <textarea name="descripcion" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fotografía (JPG, PNG o WEBP) *</label>
                        <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Animal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: EDITAR ANIMAL -->
    <div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="procesar_animal.php" method="POST" enctype="multipart/form-data" class="modal-content">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Editar Ficha del Animal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" id="edit-nombre" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Especie *</label>
                            <select name="especie" id="edit-especie" class="form-select" required>
                                <option value="Perro">Perro</option>
                                <option value="Gato">Gato</option>
                                <option value="Otros">Otros</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado *</label>
                            <select name="estado" id="edit-estado" class="form-select" required>
                                <option value="Disponible">Disponible</option>
                                <option value="Adoptado">Adoptado</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Raza *</label>
                            <input type="text" name="raza" id="edit-raza" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Edad *</label>
                            <input type="text" name="edad" id="edit-edad" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción *</label>
                        <textarea name="descripcion" id="edit-descripcion" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cambiar fotografía (opcional)</label>
                        <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <small class="text-muted">Déjalo vacío si deseas mantener la fotografía actual.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Actualizar Ficha</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Rellenar automáticamente los campos del modal Editar con los datos del botón pulsado
        const modalEditar = document.getElementById('modalEditar');
        modalEditar.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('edit-id').value          = button.getAttribute('data-id');
            document.getElementById('edit-nombre').value      = button.getAttribute('data-nombre');
            document.getElementById('edit-especie').value     = button.getAttribute('data-especie');
            document.getElementById('edit-raza').value        = button.getAttribute('data-raza');
            document.getElementById('edit-edad').value        = button.getAttribute('data-edad');
            document.getElementById('edit-descripcion').value = button.getAttribute('data-descripcion');
            document.getElementById('edit-estado').value      = button.getAttribute('data-estado');
        });
    </script>
</body>
</html>