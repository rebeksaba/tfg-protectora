<?php
// Configuración de la conexión a la base de datos en MAMP
$host     = "localhost";
$db       = "protectora_animales";
$user     = "root";
$password = "root"; // En MAMP de Mac, la contraseña por defecto es "root"
$charset  = "utf8mb4";

// Configuración de opciones de PDO
// PDO es la forma moderna y segura de conectar PHP con bases de datos
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Activa el reporte de errores
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve los datos en arrays asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Desactiva la emulación para mejorar la seguridad
];

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
     // Intentamos conectar
     $pdo = new PDO($dsn, $user, $password, $options);
     // Descomenta la línea de abajo solo para probar; luego la borraremos para que no moleste
     // echo "¡Conexión establecida con éxito!"; 
} catch (\PDOException $e) {
     // Si hay un error, detenemos la web y mostramos qué ha fallado
     die("Error crítico en la base de datos: " . $e->getMessage());
}