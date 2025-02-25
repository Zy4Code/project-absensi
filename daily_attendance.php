<?php
$host = '127.0.0.1';
$db = 'smkn1panji';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get selected date or default to today
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$search_name = isset($_GET['search_name']) ? $_GET['search_name'] : '';
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';

// Query untuk mengambil data absensi dengan filter
$sql = "SELECT s.nisn, s.nama_siswa, s.jurusan, a.id_absen, a.keterangan, t.nama_absen as status_absen 
        FROM siswa s 
        LEFT JOIN absensi a ON s.nisn = a.nisn AND a.tanggal_absensi = '$selected_date'
        LEFT JOIN tipe_absen t ON a.id_absen = t.id_absen
        WHERE s.nama_siswa LIKE '%$search_name%'";

if ($filter_type !== '') {
    $sql .= " AND (a.id_absen = '$filter_type' OR (a.id_absen IS NULL AND '$filter_type' = '0'))";
}

$sql .= " ORDER BY s.nama_siswa";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Absensi Harian</title>
    <link rel="stylesheet" href="src/css/page_index.css">
    <style>
        .date-picker {
            margin: 20px 0;
            text-align: center;
        }
        
        .date-picker input[type="date"] {
            padding: 12px;
            width: 300px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .summary {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
        }
        
        .summary-item {
            background: white;
            padding: 15px 25px;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .summary-item span {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .nav-buttons {
            text-align: center;
            margin: 20px 0;
        }

        .filter-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .filter-container input {
            padding: 12px;
            width: 300px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .filter-container select {
            padding: 12px;
            width: 200px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .filter-container button {
            padding: 12px 20px;
            color: white;
            background-color: #3498db;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .filter-container button:hover {
            background-color: #2980b9;
        }
    </style>
</head>

<body>
    <h2>Absensi Harian Siswa</h2>
    
    <div class="nav-buttons">
        <button onclick="window.location.href='index.php'" class="back-btn">Kembali ke Halaman Utama</button>
    </div>

    <div class="controls-container">
        <div class="date-picker">
            <form method="GET">
                <input type="hidden" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>">
                <input type="hidden" name="filter_type" value="<?php echo $filter_type; ?>">
                <input type="date" 
                       name="date" 
                       value="<?php echo $selected_date; ?>" 
                       onchange="this.form.submit()"
                       class="date-input">
            </form>
        </div>

        <div class="filter-container">
            <form method="GET" class="filter-form">
                <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                <div class="search-group">
                    <input type="text" 
                           name="search_name" 
                           placeholder="Cari nama siswa..." 
                           value="<?php echo htmlspecialchars($search_name); ?>"
                           class="search-input">
                
                    <select name="filter_type" class="status-select">
                        <option value="">Semua Status</option>
                        <option value="0" <?php echo $filter_type === '0' ? 'selected' : ''; ?>>Hadir</option>
                        <option value="1" <?php echo $filter_type === '1' ? 'selected' : ''; ?>>Sakit</option>
                        <option value="2" <?php echo $filter_type === '2' ? 'selected' : ''; ?>>Izin</option>
                        <option value="3" <?php echo $filter_type === '3' ? 'selected' : ''; ?>>Alpa</option>
                    </select>

                    <button type="submit" class="filter-btn">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <?php
    // Hitung statistik
    $total = $result->num_rows;
    $hadir = 0;
    $sakit = 0;
    $izin = 0;
    $alpa = 0;

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row['id_absen'] == 1) $sakit++;
            else if ($row['id_absen'] == 2) $izin++;
            else if ($row['id_absen'] == 3) $alpa++;
            else $hadir++;
        }
        // Reset pointer
        mysqli_data_seek($result, 0);
    }
    ?>

    <!-- <div class="summary">
        <div class="summary-item">Total: <span><?php echo $total; ?></span></div>
        <div class="summary-item">Hadir: <span><?php echo $hadir; ?></span></div>
        <div class="summary-item">Sakit: <span><?php echo $sakit; ?></span></div>
        <div class="summary-item">Izin: <span><?php echo $izin; ?></span></div>
        <div class="summary-item">Alpa: <span><?php echo $alpa; ?></span></div>
    </div> -->

    <table>
        <thead>
            <tr>
                <th>NISN</th>
                <th>Nama Siswa</th>
                <th>Jurusan</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $status = $row['status_absen'] ?? 'Hadir';
                    $keterangan = $row['keterangan'] ?? '-';
                    
                    echo "<tr>
                        <td>{$row['nisn']}</td>
                        <td>{$row['nama_siswa']}</td>
                        <td>{$row['jurusan']}</td>
                        <td>{$status}</td>
                        <td>{$keterangan}</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='5'>Tidak ada data</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>

<?php $conn->close(); ?> 