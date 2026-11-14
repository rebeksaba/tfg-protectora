<?php
session_start();

// Si ya tiene sesión iniciada, redirigir directamente al panel
if (isset($_SESSION['admin'])) {
    header("Location: panel.php");
    exit;
}

require_once 'conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Por favor, completa todos los campos.";
    } else {
        // Consulta preparada para prevenir inyección SQL
        $stmt = $pdo->prepare("SELECT id, username, password FROM usuarios WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {
            // Regenerar ID de sesión por seguridad
            session_regenerate_id(true);
            $_SESSION['admin'] = $usuario['username'];
            $_SESSION['admin_id'] = $usuario['id'];
            header("Location: panel.php");
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Administración - Animal Hope</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

    <div class="card shadow-sm p-4" style="max-width: 400px; width: 100%;">
        <div class="text-center mb-4">
            <h3 class="fw-bold">🐾 Animal Hope</h3>
            <p class="text-muted">Acceso al Panel de Administración</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Usuario</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
        </form>

        <div class="text-center mt-3">
            <a href="index.php" class="text-decoration-none text-secondary small">← Volver al catálogo público</a>
        </div>
    </div>

</body>
</html>