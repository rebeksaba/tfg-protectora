<?php
// 1. Incluimos la conexión a la Base de Datos
require_once 'conexion.php';

// 2. Consultamos solo los animales que están 'Disponibles'
try {
    $stmt = $pdo->query("SELECT * FROM animales WHERE estado = 'Disponible' ORDER BY fecha_ingreso DESC");
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

    <div class="row g-4">
        <?php if (count($animales) > 0): ?>
            <?php foreach ($animales as $animal): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0">
                        <!-- Foto genérica según especie si no hay foto real subida -->
                        <img src="https://placehold.co/600x400/e9ecef/212529?text=<?php echo $animal['especie']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($animal['nombre']); ?>">
                        
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0 fw-bold"><?php echo htmlspecialchars($animal['nombre']); ?></h5>
                                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($animal['especie']); ?></span>
                            </div>
                            <p class="card-text text-muted small mb-1"><strong>Raza:</strong> <?php echo htmlspecialchars($animal['raza']); ?></p>
                            <p class="card-text text-muted small mb-3"><strong>Edad:</strong> <?php echo $animal['edad']; ?> años</p>
                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars($animal['descripcion']); ?></p>
                            
                            <a href="#contacto" class="btn btn-outline-primary w-100 mt-3">Solicitar Adopción</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">Actualmente no hay animales registrados en adopción.</div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php 
// 4. Incluimos el pie de página
include 'includes/footer.php'; 
?>