<?php
// Establecer la conexión con la base de datos
require_once(__DIR__ . '/../db/config.php');

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar que se haya enviado el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Obtener los datos del formulario
    $nombre_cliente = trim($_POST['nombre_cliente']);
    $factura_id = trim($_POST['factura_id']);
    $fecha_vencimiento = trim($_POST['fecha_vencimiento']);
    $estado = trim($_POST['estado']);
    $codigo_producto = trim($_POST['codigo_producto']);

    // Validación básica
    if (empty($codigo_producto)) {
        die("Error: El código de producto no puede estar vacío.");
    }

    // Insertar la garantía directamente en la base de datos con codigo_producto
    $sql = $conn->prepare("INSERT INTO garantias (nombre_cliente, factura_id, fecha, codigo_producto, estado) 
                           VALUES (?, ?, ?, ?, ?)");
    $sql->bind_param("sssss", $nombre_cliente, $factura_id, $fecha_vencimiento, $codigo_producto, $estado);
    $sql->execute();
    $garantia_id = $conn->insert_id;

    // Obtener los números de serie desde el formulario
    $numero_serie = isset($_POST['producto_id']) ? $_POST['producto_id'] : [];

    if (count($numero_serie) > 0) {
        $stmt = $conn->prepare("INSERT INTO numero_serie_garantias (garantia_id, numero_serie) VALUES (?, ?)");

        foreach ($numero_serie as $serie) {
            if (!empty($serie)) {  
                $stmt->bind_param("is", $garantia_id, $serie);
                $stmt->execute();
            }
        }
    }

    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();

    echo "success";
} else {
    echo "No se ha enviado el formulario.";
}
?>
