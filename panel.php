<?php
session_start();

// Control de acceso: si no hay sesión activa, redirigir al login
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
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
    <nav class="navbar navbar-dark bg-dark px-4">
        <span class="navbar-brand fw-bold">🐾 Animal Hope | Administración</span>
        <div class="d-flex align-items-center text-white">
            <span class="me-3">Hola, <strong><?= htmlspecialchars($_SESSION['admin']) ?></strong></span>
            <a href="index.php" class="btn btn-outline-light btn-sm me-2">Ver Web</a>
            <a href="logout.php" class="btn btn-danger btn-sm">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="alert alert-success">
            Acceso correcto al panel de administración. Aquí construiremos el CRUD de animales y la bandeja de solicitudes.
        </div>
    </div>
</body>
</html>