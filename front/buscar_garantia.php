<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once(__DIR__ . '/../db/config.php');

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $buscar_por = isset($_POST['buscar_por']) ? trim($_POST['buscar_por']) : "";
    $sql = "SELECT id, nombre_cliente, factura_id, fecha, codigo_producto, estado FROM garantias WHERE ";
    $params = [];
    $types = "";

    switch ($buscar_por) {
        case "nombre_cliente":
            if (!empty($_POST['nombre_cliente_busqueda'])) {
                $nombre_cliente = trim($_POST['nombre_cliente_busqueda']);
                $sql .= "nombre_cliente LIKE ?";
                $params[] = "%$nombre_cliente%";
                $types = "s";
            }
            break;
        case "factura_id":
            if (!empty($_POST['factura_id_busqueda'])) {
                $factura_id = trim($_POST['factura_id_busqueda']);
                $sql .= "factura_id = ?";
                $params[] = $factura_id;
                $types = "s";
            }
            break;
        case "fecha":
            if (!empty($_POST['fecha_busqueda'])) {
                $fecha = trim($_POST['fecha_busqueda']);
                $sql .= "DATE(fecha) = ?";
                $params[] = $fecha;
                $types = "s";
            }
            break;
    }

    if (!empty($sql)) {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Error en la consulta: " . $conn->error);
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
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

                <?php if ($result->num_rows > 0) { ?>
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
                            <?php while ($row = $result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['nombre_cliente']) ?></td>
                                    <td><?= htmlspecialchars($row['factura_id']) ?></td>
                                    <td><?= htmlspecialchars($row['fecha']) ?></td>
                                    <td><?= htmlspecialchars($row['codigo_producto'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['estado']) ?></td>
                                    <td>
                                        <?php
                                        $garantia_id = $row['id'];
                                        $serie_sql = "SELECT numero_serie FROM numero_serie_garantias WHERE garantia_id = ?";
                                        $serie_stmt = $conn->prepare($serie_sql);
                                        $serie_stmt->bind_param("i", $garantia_id);
                                        $serie_stmt->execute();
                                        $serie_result = $serie_stmt->get_result();

                                        if ($serie_result->num_rows > 0) {
                                            echo '<ul class="list-unstyled">';
                                            while ($serie_row = $serie_result->fetch_assoc()) {
                                                echo '<li>' . htmlspecialchars($serie_row['numero_serie']) . '</li>';
                                            }
                                            echo '</ul>';
                                        } else {
                                            echo 'No hay números de serie.';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>

                    <!-- Botones de acción -->
                    <div class="btn-group">
                        <a href="buscar.html" class="btn btn-primary">🔍 Realizar Otra Búsqueda</a>
                        <a href="registrar_garantia.html" class="btn btn-secondary">↩ Regresar</a>
                        <a href="exportar_excel.php?buscar_por=<?= $buscar_por ?>&fecha_busqueda=<?= $_POST['fecha_busqueda'] ?? '' ?>&nombre_cliente_busqueda=<?= $_POST['nombre_cliente_busqueda'] ?? '' ?>&factura_id_busqueda=<?= $_POST['factura_id_busqueda'] ?? '' ?>" class="btn btn-success">📥 Exportar a Excel</a>
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
    }

    $conn->close();
}
?>
