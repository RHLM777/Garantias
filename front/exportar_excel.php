<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once(__DIR__ . '/../db/config.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// Validar los parámetros
$filtro = $_GET['buscar_por'] ?? null;
$fecha = $_GET['fecha_busqueda'] ?? null;
$cliente = $_GET['nombre_cliente_busqueda'] ?? null;
$factura = $_GET['factura_id_busqueda'] ?? null;

// Construir la consulta SQL
$sql = "SELECT nombre_cliente, factura_id, fecha, producto_id, estado FROM garantias";
$conditions = [];

if ($filtro === 'fecha' && $fecha) {
    $conditions[] = "fecha = '$fecha'";
} elseif ($filtro === 'cliente' && $cliente) {
    $conditions[] = "nombre_cliente LIKE '%$cliente%'";
} elseif ($filtro === 'factura' && $factura) {
    $conditions[] = "factura_id = '$factura'";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$result = $conn->query($sql);

// Verificar si hay resultados antes de proceder
if ($result && $result->num_rows > 0) {
    // Crear una nueva hoja de cálculo
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Establecer el título de la hoja
    $sheet->setTitle('Garantías Exportadas');

    // Insertar el logo
    $drawing = new Drawing();
    $drawing->setName('Logo');
    $drawing->setDescription('Logo de la Empresa');
    $drawing->setPath(__DIR__ . '/assets/logo.png'); // Ruta ajustada
    $drawing->setHeight(60); // Tamaño del logo
    $drawing->setCoordinates('A1'); // Posición del logo
    $drawing->setWorksheet($sheet);

    // Fusionar celdas para el título
    $sheet->mergeCells('B1:E2');
    $sheet->setCellValue('B1', 'Reporte de Garantías');
    $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Establecer los encabezados de la tabla
    $headers = ['Nombre del Cliente', 'Número de Factura', 'Fecha de Garantía', 'Código del Producto', 'Estado'];
    $sheet->fromArray($headers, null, 'A4');
    $sheet->getStyle('A4:E4')->applyFromArray([
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
        $sheet->setCellValue("D{$rowIndex}", $row_data['producto_id']);
        $sheet->setCellValue("E{$rowIndex}", $row_data['estado']);
        $rowIndex++;
    }

    // Crear bordes para la tabla de datos
    $sheet->getStyle("A4:E" . ($rowIndex - 1))->applyFromArray([
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
        ],
    ]);

    // Ajustar el tamaño de las columnas
    foreach (range('A', 'E') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Limpiar el búfer de salida antes de generar el archivo
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Crear el escritor de Excel y guardar el archivo
    $writer = new Xlsx($spreadsheet);
    $filename = 'garantias_exportadas_' . date('Y-m-d') . '.xlsx';

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
