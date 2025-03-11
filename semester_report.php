<?php
$host = '127.0.0.1';
$db = 'smkn1panji';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Mendapatkan tahun saat ini
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$semester = isset($_GET['semester']) ? $_GET['semester'] : 
    (date('n') >= 7 ? '1' : '2'); // Semester 1 (Jul-Des), Semester 2 (Jan-Jun)

// Menentukan rentang tanggal berdasarkan semester
if ($semester == '1') {
    $start_date = $tahun . '-07-01';
    $end_date = $tahun . '-12-31';
} else {
    $start_date = $tahun . '-01-01';
    $end_date = $tahun . '-06-30';
}

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
        AND DAYOFWEEK(DATE_ADD(start_date, INTERVAL n DAY)) NOT IN (1) -- Mengecualikan hari Minggu
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rekap Absensi Semester</title>
    <link rel="stylesheet" href="src/css/page_index.css">
</head>
<body>
    <h2>Rekap Absensi Semester</h2>
    
    <div style="margin-bottom: 20px;">
        <form method="GET" action="semester_report.php" style="display: inline-block;">
            <select name="tahun">
                <?php
                $current_year = date('Y');
                for($i = $current_year - 1; $i <= $current_year + 1; $i++) {
                    $selected = ($i == $tahun) ? 'selected' : '';
                    echo "<option value='$i' $selected>$i</option>";
                }
                ?>
            </select>
            <select name="semester">
                <option value="1" <?php echo ($semester == '1') ? 'selected' : ''; ?>>Semester 1 (Jul-Des)</option>
                <option value="2" <?php echo ($semester == '2') ? 'selected' : ''; ?>>Semester 2 (Jan-Jun)</option>
            </select>
            <button type="submit">Tampilkan</button>
        </form>
        
        <form method="POST" action="export_excel.php" style="display: inline-block; margin-left: 10px;">
            <input type="hidden" name="tahun" value="<?php echo $tahun; ?>">
            <input type="hidden" name="semester" value="<?php echo $semester; ?>">
            <button type="submit">Export to Excel</button>
        </form>
        
        <button onclick="window.location.href='index.php'" style="margin-left: 10px;">Kembali</button>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th>NISN</th>
                <th>Nama Siswa</th>
                <th>Hadir</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Alpha</th>
                <th>Total Hari</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $total = $row['Hadir'] + $row['Sakit'] + $row['Izin'] + $row['Alpha'];
                    echo "<tr>
                        <td>{$row['NISN']}</td>
                        <td>{$row['Nama']}</td>
                        <td>{$row['Hadir']}</td>
                        <td>{$row['Sakit']}</td>
                        <td>{$row['Izin']}</td>
                        <td>{$row['Alpha']}</td>
                        <td>{$total}</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='7'>Tidak ada data</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>

<?php $conn->close(); ?> 