<?php
session_start();

// 1. Seguridad: verificar que el administrador tiene sesión activa
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require_once 'conexion.php';

// Función auxiliar para validar y subir imágenes de forma segura
function subirImagen($archivo) {
    $permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $maxTamano = 5 * 1024 * 1024; // 5 MB máximo

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Error al subir la imagen.'];
    }

    // Validar tipo MIME real del fichero en el servidor
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $permitidos)) {
        return ['error' => 'Formato de imagen no válido. Usa JPG, PNG o WEBP.'];
    }

    if ($archivo['size'] > $maxTamano) {
        return ['error' => 'La imagen supera el tamaño máximo permitido (5 MB).'];
    }

    // Generar nombre único para evitar sobrescrituras
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombreUnico = uniqid('animal_') . '.' . strtolower($extension);
    $rutaDestino = __DIR__ . '/img/' . $nombreUnico;

    if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        return ['nombre' => $nombreUnico];
    }

    return ['error' => 'No se pudo guardar la imagen en la carpeta /img.'];
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

try {
    // ==========================================
    // ACCIÓN 1: CREAR NUEVO ANIMAL (CREATE)
    // ==========================================
    if ($accion === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre      = trim($_POST['nombre'] ?? '');
        $especie     = $_POST['especie'] ?? '';
        $raza        = trim($_POST['raza'] ?? '');
        $edad        = trim($_POST['edad'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado      = $_POST['estado'] ?? 'Disponible';

        // Validación en servidor
        $especiesValidas = ['Perro', 'Gato', 'Otros'];
        $estadosValidos  = ['Disponible', 'Adoptado'];

        if (empty($nombre) || empty($raza) || empty($edad) || empty($descripcion) ||
            !in_array($especie, $especiesValidas) || !in_array($estado, $estadosValidos)) {
            header("Location: panel.php?err=campos_invalidos");
            exit;
        }

        // Validar y subir fotografía obligatoria en el alta
        if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
            header("Location: panel.php?err=falta_imagen");
            exit;
        }

        $resultadoImg = subirImagen($_FILES['imagen']);
        if (isset($resultadoImg['error'])) {
            header("Location: panel.php?err=imagen_invalida");
            exit;
        }

        $sql = "INSERT INTO animales (nombre, especie, raza, edad, descripcion, imagen, estado) 
                VALUES (:nombre, :especie, :raza, :edad, :descripcion, :imagen, :estado)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'      => $nombre,
            ':especie'     => $especie,
            ':raza'        => $raza,
            ':edad'        => $edad,
            ':descripcion' => $descripcion,
            ':imagen'      => $resultadoImg['nombre'],
            ':estado'      => $estado
        ]);

        header("Location: panel.php?msg=creado");
        exit;
    }

    // ==========================================
    // ACCIÓN 2: EDITAR ANIMAL EXISTENTE (UPDATE)
    // ==========================================
    if ($accion === 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id          = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nombre      = trim($_POST['nombre'] ?? '');
        $especie     = $_POST['especie'] ?? '';
        $raza        = trim($_POST['raza'] ?? '');
        $edad        = trim($_POST['edad'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado      = $_POST['estado'] ?? 'Disponible';

        if (!$id || empty($nombre) || empty($raza) || empty($edad) || empty($descripcion)) {
            header("Location: panel.php?err=campos_invalidos");
            exit;
        }

        // Obtener imagen actual por si no se sube una nueva
        $stmtActual = $pdo->prepare("SELECT imagen FROM animales WHERE id = :id");
        $stmtActual->execute([':id' => $id]);
        $animalActual = $stmtActual->fetch();

        if (!$animalActual) {
            header("Location: panel.php?err=no_encontrado");
            exit;
        }

        $nombreImagen = $animalActual['imagen'];

        // Si el administrador subió una foto nueva, reemplazarla
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
            $resultadoImg = subirImagen($_FILES['imagen']);
            if (isset($resultadoImg['error'])) {
                header("Location: panel.php?err=imagen_invalida");
                exit;
            }
            $nombreImagen = $resultadoImg['nombre'];
        }

        $sql = "UPDATE animales 
                SET nombre = :nombre, especie = :especie, raza = :raza, edad = :edad, 
                    descripcion = :descripcion, imagen = :imagen, estado = :estado 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'      => $nombre,
            ':especie'     => $especie,
            ':raza'        => $raza,
            ':edad'        => $edad,
            ':descripcion' => $descripcion,
            ':imagen'      => $nombreImagen,
            ':estado'      => $estado,
            ':id'          => $id
        ]);

        header("Location: panel.php?msg=editado");
        exit;
    }

    // ==========================================
    // ACCIÓN 3: CAMBIO RÁPIDO DE ESTADO (Disponible / Adoptado)
    // ==========================================
    if ($accion === 'cambiar_estado' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $estado = $_POST['nuevo_estado'] ?? '';

        if ($id && in_array($estado, ['Disponible', 'Adoptado'])) {
            $stmt = $pdo->prepare("UPDATE animales SET estado = :estado WHERE id = :id");
            $stmt->execute([':estado' => $estado, ':id' => $id]);
            header("Location: panel.php?msg=estado");
            exit;
        }
    }

    // ==========================================
    // ACCIÓN 4: ELIMINAR ANIMAL (DELETE)
    // ==========================================
    if ($accion === 'eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if ($id) {
            // Eliminar registro (las solicitudes se borran automáticamente por ON DELETE CASCADE)
            $stmt = $pdo->prepare("DELETE FROM animales WHERE id = :id");
            $stmt->execute([':id' => $id]);
            header("Location: panel.php?msg=eliminado");
            exit;
        }
    }

} catch (PDOException $e) {
    header("Location: panel.php?err=bd");
    exit;
}

header("Location: panel.php");
exit;