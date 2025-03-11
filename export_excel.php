<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$host = '127.0.0.1';
$db = 'smkn1panji';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Mendapatkan parameter
$tahun = isset($_POST['tahun']) ? $_POST['tahun'] : date('Y');
$semester = isset($_POST['semester']) ? $_POST['semester'] : 
    (date('n') >= 7 ? '1' : '2');

// Menentukan rentang tanggal
if ($semester == '1') {
    $start_date = $tahun . '-07-01';
    $end_date = $tahun . '-12-31';
    $semester_text = "Semester 1 (Juli-Desember)";
} else {
    $start_date = $tahun . '-01-01';
    $end_date = $tahun . '-06-30';
    $semester_text = "Semester 2 (Januari-Juni)";
}

// Query yang sama seperti di semester_report.php
$sql = "WITH DateRange AS (
    SELECT DATE(?) as start_date, DATE(?) as end_date
),
TotalDays AS (
    SELECT COUNT(*) as total_school_days 
    FROM (
        SELECT DATE_ADD(start_date, INTERVAL n DAY) as date
        FROM DateRange
        JOIN (
            SELECT a.N + b.N * 10 + c.N * 100 as n
            FROM (SELECT 0 as N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a,
                 (SELECT 0 as N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b,
                 (SELECT 0 as N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) c
        ) numbers
        WHERE DATE_ADD(start_date, INTERVAL n DAY) <= end_date
        AND DAYOFWEEK(DATE_ADD(start_date, INTERVAL n DAY)) NOT IN (1)
    ) as school_dates
)
SELECT 
    s.nisn AS NISN,
    s.nama_siswa AS Nama,
    (SELECT total_school_days FROM TotalDays) - 
        COUNT(DISTINCT a.tanggal_absensi) as Hadir,
    SUM(CASE WHEN ta.id_absen = 2 THEN 1 ELSE 0 END) as Sakit,
    SUM(CASE WHEN ta.id_absen = 3 THEN 1 ELSE 0 END) as Izin,
    SUM(CASE WHEN ta.id_absen = 4 THEN 1 ELSE 0 END) as Alpha
FROM siswa s
CROSS JOIN DateRange d
LEFT JOIN absensi a ON s.nisn = a.nisn 
    AND a.tanggal_absensi BETWEEN d.start_date AND d.end_date
LEFT JOIN tipe_absen ta ON a.id_absen = ta.id_absen
GROUP BY s.nisn, s.nama_siswa";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Membuat spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set judul
$sheet->setCellValue('A1', 'REKAP ABSENSI SISWA XI PPLG');
$sheet->setCellValue('A2', 'Tahun ' . $tahun . ' - ' . $semester_text);
$sheet->mergeCells('A1:G1');
$sheet->mergeCells('A2:G2');

// Set header kolom
$sheet->setCellValue('A4', 'NISN');
$sheet->setCellValue('B4', 'Nama Siswa');
$sheet->setCellValue('C4', 'Hadir');
$sheet->setCellValue('D4', 'Sakit');
$sheet->setCellValue('E4', 'Izin');
$sheet->setCellValue('F4', 'Alpha');
$sheet->setCellValue('G4', 'Total Hari');

// Isi data
$row = 5;
while ($data = $result->fetch_assoc()) {
    $total = $data['Hadir'] + $data['Sakit'] + $data['Izin'] + $data['Alpha'];
    
    $sheet->setCellValue('A' . $row, $data['NISN']);
    $sheet->setCellValue('B' . $row, $data['Nama']);
    $sheet->setCellValue('C' . $row, $data['Hadir']);
    $sheet->setCellValue('D' . $row, $data['Sakit']);
    $sheet->setCellValue('E' . $row, $data['Izin']);
    $sheet->setCellValue('F' . $row, $data['Alpha']);
    $sheet->setCellValue('G' . $row, $total);
    
    $row++;
}

// Styling
$sheet->getStyle('A1:G2')->getAlignment()->setHorizontal('center');
$sheet->getStyle('A1:G2')->getFont()->setBold(true);
$sheet->getStyle('A4:G4')->getFont()->setBold(true);
$sheet->getStyle('A4:G4')->getAlignment()->setHorizontal('center');

// Auto-size columns
foreach(range('A','G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Set nama file
$filename = "Rekap_Absensi_" . $tahun . "_Semester_" . $semester . ".xlsx";

// Header untuk download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Tulis ke output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

$conn->close(); 