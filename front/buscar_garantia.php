<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once(__DIR__ . '/../db/config.php');

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $buscar_por = $_POST['buscar_por'] ?? "";
    $params = [];
    $types = "";
    $sql = "SELECT g.id, g.nombre_cliente, g.factura_id, g.fecha, g.codigo_producto, g.estado, 
                   ns.numero_serie 
            FROM garantias g 
            LEFT JOIN numero_serie_garantias ns ON g.id = ns.garantia_id WHERE 1=1";

    switch ($buscar_por) {
        case "nombre_cliente":
            if (!empty($_POST['nombre_cliente_busqueda'])) {
                $sql .= " AND g.nombre_cliente LIKE ?";
                $params[] = "%" . trim($_POST['nombre_cliente_busqueda']) . "%";
                $types .= "s";
            }
            break;
        case "factura_id":
            if (!empty($_POST['factura_id_busqueda'])) {
                $sql .= " AND g.factura_id = ?";
                $params[] = trim($_POST['factura_id_busqueda']);
                $types .= "s";
            }
            break;
        case "fecha":
            if (!empty($_POST['fecha_busqueda'])) {
                $sql .= " AND DATE(g.fecha) = ?";
                $params[] = trim($_POST['fecha_busqueda']);
                $types .= "s";
            }
            break;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error en la consulta: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $datos = [];

    while ($row = $result->fetch_assoc()) {
        $garantia_id = $row['id'];
        if (!isset($datos[$garantia_id])) {
            $datos[$garantia_id] = [
                'nombre_cliente' => $row['nombre_cliente'],
                'factura_id' => $row['factura_id'],
                'fecha' => $row['fecha'],
                'codigo_producto' => $row['codigo_producto'] ?? 'N/A',
                'estado' => $row['estado'],
                'numeros_serie' => []
            ];
        }
        if (!empty($row['numero_serie'])) {
            $datos[$garantia_id]['numeros_serie'][] = $row['numero_serie'];
        }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Búsqueda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .container {
            margin-top: 50px;
        }
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }
        .table th {
            background-color: #007bff;
            color: white;
        }
        .btn-group {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="table-container">
        <h2 class="text-center mb-4">Resultados de la Búsqueda</h2>

        <?php if (!empty($datos)) { ?>
            <table class="table table-bordered table-striped text-center">
                <thead>
                    <tr>
                        <th>Nombre Cliente</th>
                        <th>Número Factura</th>
                        <th>Fecha Garantía</th>
                        <th>Código Producto</th>
                        <th>Estado</th>
                        <th>Números de Serie</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos as $garantia_id => $info) { ?>
                        <tr>
                            <td><?= htmlspecialchars($info['nombre_cliente']) ?></td>
                            <td><?= htmlspecialchars($info['factura_id']) ?></td>
                            <td><?= htmlspecialchars($info['fecha']) ?></td>
                            <td><?= htmlspecialchars($info['codigo_producto']) ?></td>
                            <td><?= htmlspecialchars($info['estado']) ?></td>
                            <td>
                                <?php if (!empty($info['numeros_serie'])) { ?>
                                    <ul class="list-unstyled">
                                        <?php foreach ($info['numeros_serie'] as $serie) { ?>
                                            <li><?= htmlspecialchars($serie) ?></li>
                                        <?php } ?>
                                    </ul>
                                <?php } else {
                                    echo 'No hay números de serie.';
                                } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- Botones de acción -->
            <div class="btn-group">
                <a href="buscar.html" class="btn btn-primary">🔍 Realizar Otra Búsqueda</a>
                <a href="registrar_garantia.html" class="btn btn-secondary">↩ Regresar</a>
                <a href="exportar_excel.php?buscar_por=<?= urlencode($buscar_por) ?>&valor=<?= isset($params[0]) ? urlencode($params[0]) : '' ?>" 
                   class="btn btn-success">📥 Exportar a Excel</a>
            </div>

        <?php } else { ?>
            <div class="alert alert-warning text-center">No se encontraron resultados.</div>
        <?php } ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
    $conn->close();
}
?>
