<?php
session_start();

// Control de acceso: si no hay sesión activa, redirigir al login
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require_once 'conexion.php';

// 1. Obtener todos los animales (ordenados por fecha de ingreso más reciente)
$stmtAnimales = $pdo->query("SELECT * FROM animales ORDER BY fecha_ingreso DESC");
$animales = $stmtAnimales->fetchAll();

// 2. Obtener las solicitudes de adopción junto con el nombre del animal
$sqlSolicitudes = "SELECT s.*, a.nombre AS nombre_animal, a.especie 
                   FROM solicitudes s 
                   INNER JOIN animales a ON s.animal_id = a.id 
                   ORDER BY s.fecha_solicitud DESC";
$stmtSolicitudes = $pdo->query($sqlSolicitudes);
$solicitudes = $stmtSolicitudes->fetchAll();
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

        <!-- Mensajes de alerta (los usaremos en el siguiente paso para el CRUD) -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Operación realizada con éxito.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- SECCIÓN 1: GESTIÓN DE ANIMALES -->
        <div class="card shadow-sm mb-5">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">🐶 Catálogo de Animales Registrados (<?= count($animales) ?>)</h5>
                <button class="btn btn-light btn-sm fw-bold" disabled>+ Nuevo Animal (Próximo paso)</button>
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
                                        <span class="text-muted small">Acciones CRUD en siguiente paso</span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>