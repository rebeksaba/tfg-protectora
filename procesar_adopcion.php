<?php
require_once 'conexion.php';

// Comprobamos que los datos vengan por el método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $animal_id = filter_input(INPUT_POST, 'animal_id', FILTER_VALIDATE_INT);
    $nombre    = trim($_POST['nombre'] ?? '');
    $email     = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $telefono  = trim($_POST['telefono'] ?? '');
    $mensaje   = trim($_POST['mensaje'] ?? '');

    // Validación básica en servidor
    if (!$animal_id || !$nombre || !$email) {
        header('Location: index.php?status=error#contacto');
        exit;
    }

    try {
        // Sentencia preparada para evitar inyección SQL (Buenas prácticas DAW)
        $sql = "INSERT INTO solicitudes (nombre_solicitante, email, telefono, mensaje, animal_id) 
                VALUES (:nombre, :email, :telefono, :mensaje, :animal_id)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'    => $nombre,
            ':email'     => $email,
            ':telefono'  => $telefono,
            ':mensaje'   => $mensaje,
            ':animal_id' => $animal_id
        ]);

        header('Location: index.php?status=success#contacto');
        exit;

    } catch (\PDOException $e) {
        header('Location: index.php?status=error#contacto');
        exit;
    }

} else {
    header('Location: index.php');
    exit;
}