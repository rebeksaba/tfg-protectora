<?php
// 1. Incluimos la conexión a la Base de Datos
require_once 'conexion.php';

// 2. Captura y saneamiento de filtros
$filtro_especie = trim($_GET['especie'] ?? '');
$filtro_busqueda = trim($_GET['buscar'] ?? '');

$sql = "SELECT * FROM animales WHERE estado = 'Disponible'";
$params = [];

if (!empty($filtro_especie)) {
    $sql .= " AND especie = :especie";
    $params[':especie'] = $filtro_especie;
}

if (!empty($filtro_busqueda)) {
    $sql .= " AND nombre LIKE :buscar";
    $params[':buscar'] = '%' . $filtro_busqueda . '%';
}

$sql .= " ORDER BY fecha_ingreso DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $animales = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("Error al consultar animales: " . $e->getMessage());
}

// 3. Incluimos la cabecera
include 'includes/header.php';
?>

<!-- Banner Principal -->
<header class="bg-light text-center py-5 shadow-sm">
    <div class="container py-4">
        <h1 class="display-4 fw-bold text-dark">Adopta un Amigo, Salva una Vida</h1>
        <p class="lead text-muted">Dale una segunda oportunidad a quienes más lo necesitan.</p>
        <a href="#animales" class="btn btn-primary btn-lg mt-2">Ver Animales Disponibles</a>
    </div>
</header>

<!-- Sección Catálogo de Animales -->
<section id="animales" class="container my-5">
    <h2 class="text-center mb-4 font-weight-bold">Nuestros Peluditos en Adopción</h2>

    <!-- Formulario de Búsqueda y Filtros -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-8">
            <form action="index.php#animales" method="GET" class="row g-2 bg-white p-3 rounded shadow-sm border">
                <div class="col-md-5">
                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre..." value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                </div>
                <div class="col-md-4">
                    <select name="especie" class="form-select">
                        <option value="">Todas las especies</option>
                        <option value="Perro" <?php echo ($filtro_especie === 'Perro') ? 'selected' : ''; ?>>Perros</option>
                        <option value="Gato" <?php echo ($filtro_especie === 'Gato') ? 'selected' : ''; ?>>Gatos</option>
                        <option value="Otros" <?php echo ($filtro_especie === 'Otros') ? 'selected' : ''; ?>>Otros</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    <?php if (!empty($filtro_especie) || !empty($filtro_busqueda)): ?>
                        <a href="index.php#animales" class="btn btn-outline-secondary">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <?php if (count($animales) > 0): ?>
            <?php foreach ($animales as $animal): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0">
                        <?php 
                            $ruta_foto = !empty($animal['imagen']) && file_exists('img/' . $animal['imagen']) 
                                         ? 'img/' . $animal['imagen'] 
                                         : 'https://placehold.co/600x400/e9ecef/212529?text=' . urlencode($animal['especie']);
                        ?>
                        <img src="<?php echo $ruta_foto; ?>" class="card-img-top animal-img" alt="<?php echo htmlspecialchars($animal['nombre']); ?>">
                        
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0 fw-bold"><?php echo htmlspecialchars($animal['nombre']); ?></h5>
                                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($animal['especie']); ?></span>
                            </div>
                            <p class="card-text text-muted small mb-1"><strong>Raza:</strong> <?php echo htmlspecialchars($animal['raza']); ?></p>
                            <p class="card-text text-muted small mb-3"><strong>Edad:</strong> <?php echo (int)$animal['edad']; ?> años</p>
                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars($animal['descripcion']); ?></p>
                            
                            <a href="#contacto" class="btn btn-outline-primary w-100 mt-3">Solicitar Adopción</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">No se han encontrado animales con los criterios seleccionados.</div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Sección Formulario de Contacto / Adopción -->
<section id="contacto" class="bg-light py-5 border-top">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <h2 class="text-center mb-3">Solicitud de Adopción</h2>
                <p class="text-center text-muted mb-4">¿Te has enamorado de alguno de nuestros peluditos? Déjanos tus datos y nos pondremos en contacto contigo.</p>

                <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                    <div class="alert alert-success text-center">¡Solicitud enviada con éxito! Nos pondremos en contacto pronto.</div>
                <?php elseif (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
                    <div class="alert alert-danger text-center">Hubo un error al enviar tu solicitud. Inténtalo de nuevo.</div>
                <?php endif; ?>

                <form action="procesar_adopcion.php" method="POST" class="bg-white p-4 rounded shadow-sm">
                    <div class="mb-3">
                        <label for="animal_id" class="form-label fw-bold">Animal en el que estás interesado</label>
                        <select name="animal_id" id="animal_id" class="form-select" required>
                            <option value="" selected disabled>-- Selecciona un animal --</option>
                            <?php foreach ($animales as $animal): ?>
                                <option value="<?php echo (int)$animal['id']; ?>">
                                    <?php echo htmlspecialchars($animal['nombre']) . " (" . htmlspecialchars($animal['especie']) . ")"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-bold">Tu Nombre Completo</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Ej. María García" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">Correo Electrónico</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="tu@email.com" required>
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label fw-bold">Teléfono de Contacto</label>
                        <input type="tel" name="telefono" id="telefono" class="form-control" placeholder="Ej. 612345678">
                    </div>

                    <div class="mb-3">
                        <label for="mensaje" class="form-label fw-bold">¿Por qué te gustaría adoptar?</label>
                        <textarea name="mensaje" id="mensaje" rows="3" class="form-control" placeholder="Cuéntanos un poco sobre tu hogar o experiencia previa..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-lg">Enviar Solicitud</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php 
// 4. Incluimos el pie de página
include 'includes/footer.php'; 
?>