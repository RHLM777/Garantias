<?php
// Establecer la conexión con la base de datos
require_once(__DIR__ . '/../db/config.php');

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar que se haya enviado el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Obtener los datos generales del formulario
    $nombre_cliente = trim($_POST['nombre_cliente']);
    $factura_id = trim($_POST['factura_id']);
    $fecha_vencimiento = trim($_POST['fecha_vencimiento']);
    $estado = trim($_POST['estado']);

    // Verificar si hay productos en la solicitud
    if (!isset($_POST['codigo_producto']) || !is_array($_POST['codigo_producto'])) {
        die("Error: No se enviaron productos válidos.");
    }

    $codigo_productos = $_POST['codigo_producto']; // Array de códigos de productos
    $cantidades_garantias = $_POST['cantidad_garantias']; // Array de cantidades de garantías

    foreach ($codigo_productos as $index => $codigo_producto) {
        $codigo_producto = trim($codigo_producto);
        $cantidad_garantias = isset($cantidades_garantias[$index]) ? intval($cantidades_garantias[$index]) : 1;

        if (empty($codigo_producto)) {
            continue; // Saltar productos vacíos
        }

        // Insertar cada producto en la tabla de garantías
        $sql = $conn->prepare("INSERT INTO garantias (nombre_cliente, factura_id, fecha, codigo_producto, cantidad_garantias, estado) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $sql->bind_param("ssssds", $nombre_cliente, $factura_id, $fecha_vencimiento, $codigo_producto, $cantidad_garantias, $estado);
        $sql->execute();
        $garantia_id = $conn->insert_id; // Obtener el ID de la garantía insertada

        // Insertar los números de serie de cada producto si existen
        if (isset($_POST['producto_id'][$index]) && is_array($_POST['producto_id'][$index])) {
            $stmt = $conn->prepare("INSERT INTO numero_serie_garantias (garantia_id, numero_serie) VALUES (?, ?)");

            foreach ($_POST['producto_id'][$index] as $numero_serie) {
                $numero_serie = trim($numero_serie);
                if (!empty($numero_serie)) {
                    $stmt->bind_param("is", $garantia_id, $numero_serie);
                    $stmt->execute();
                }
            }
            $stmt->close();
        }
    }

    $conn->close();
    echo "success";
} else {
    echo "No se ha enviado el formulario.";
}
?>
