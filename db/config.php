<?php
$host = "localhost:3307"; // Asegúrate de que el puerto es 3307
$username = "root";
$password = ""; // Contraseña vacía por defecto en XAMPP
$dbname = "garantias";

// Crear conexión
$conn = new mysqli($host, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// La conexión está lista. No es necesario imprimir nada.
?>

