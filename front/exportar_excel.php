<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once(__DIR__ . '/../db/config.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// Obtener los filtros desde la URL
$buscar_por = $_GET['buscar_por'] ?? '';
$valor = $_GET['valor'] ?? '';

// Validar si se recibió algún filtro
if (!$buscar_por || !$valor) {
    die("No se especificó el filtro de búsqueda.");
}

// Construir la consulta SQL con el filtro aplicado
$sql = "SELECT g.nombre_cliente, g.factura_id, g.fecha, g.codigo_producto, g.estado, 
               (SELECT GROUP_CONCAT(numero_serie SEPARATOR ', ') 
                FROM numero_serie_garantias ns 
                WHERE ns.garantia_id = g.id) AS numeros_serie
        FROM garantias g
        WHERE ";


// Aplicar el filtro según el parámetro 'buscar_por'
switch ($buscar_por) {
    case 'nombre_cliente':
        $sql .= "g.nombre_cliente LIKE ?";
        break;
    case 'factura_id':
        $sql .= "g.factura_id = ?";
        break;
    case 'fecha':
        $sql .= "DATE(g.fecha) = ?";
        break;
    default:
        die("Filtro de búsqueda no válido.");
}

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($sql);
if ($buscar_por == 'nombre_cliente') {
    $valor = "%" . trim($valor) . "%"; // Para LIKE
}
$stmt->bind_param('s', $valor); // Suponiendo que el valor es siempre de tipo string (ajustar según el tipo de filtro)
$stmt->execute();
$result = $stmt->get_result();

// Verificar si hay resultados antes de proceder
if ($result && $result->num_rows > 0) {
    // Crear una nueva hoja de cálculo
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Garantía Exportada');

    // Insertar el logo
    $drawing = new Drawing();
    $drawing->setName('Logo');
    $drawing->setDescription('Logo de la Empresa');
    $drawing->setPath(__DIR__ . '/assets/logo.png'); // Asegurar que la ruta del logo es correcta
    $drawing->setHeight(60);
    $drawing->setCoordinates('A1');
    $drawing->setWorksheet($sheet);

    // Fusionar celdas para el título
    $sheet->mergeCells('B1:F2');
    $sheet->setCellValue('B1', 'Reporte de Garantía');
    $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Establecer los encabezados de la tabla
    $headers = ['Nombre del Cliente', 'Número de Factura', 'Fecha de Garantía', 'Código del Producto', 'Estado', 'Números de Serie'];
    $sheet->fromArray($headers, null, 'A4');

    $sheet->getStyle('A4:F4')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '007bff']],
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
        ],
    ]);

    // Insertar los datos
    $rowIndex = 5; // Comenzar después de los encabezados
    while ($row_data = $result->fetch_assoc()) {
        $sheet->setCellValue("A{$rowIndex}", $row_data['nombre_cliente']);
        $sheet->setCellValue("B{$rowIndex}", $row_data['factura_id']);
        $sheet->setCellValue("C{$rowIndex}", $row_data['fecha']);
        $sheet->setCellValue("D{$rowIndex}", $row_data['codigo_producto']);
        $sheet->setCellValue("E{$rowIndex}", $row_data['estado']);
        $sheet->setCellValue("F{$rowIndex}", $row_data['numeros_serie'] ?? 'N/A');
        $rowIndex++;
    }

    // Crear bordes para la tabla de datos
    $sheet->getStyle("A4:F" . ($rowIndex - 1))->applyFromArray([
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
        ],
    ]);

    // Ajustar el tamaño de las columnas
    foreach (range('A', 'F') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Limpiar el búfer de salida antes de generar el archivo
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Crear el escritor de Excel y guardar el archivo
    $writer = new Xlsx($spreadsheet);
    $filename = 'garantia_exportada_' . date('Y-m-d') . '.xlsx';

    // Forzar descarga del archivo
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0'); 
    $writer->save('php://output');
    exit;
} else {
    echo "No se encontraron resultados para exportar.";
}

$conn->close();
?>

