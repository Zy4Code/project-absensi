<?php

$host = '127.0.0.1';
$db = 'smkn1panji';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT * FROM siswa WHERE nama_siswa LIKE '%$search%'";
$result = $conn->query($sql);


$tanggal_hari_ini = date('Y-m-d');


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Absensi Siswa XI PPLG</title>
    <link rel="stylesheet" href="src/css/page_index.css">
</head>

<body>
    <h2>Daftar Siswa</h2>

    <div>
        <form method="GET" action="index.php" style="display: inline-block;">
            <input type="text" name="search" placeholder="Cari Nama Siswa...">
            <button type="submit" style="margin-right: 10px;">Cari</button>
        </form>

        <button onclick="window.location.href='daily_attendance.php'" style="margin-left: 10px;">Lihat Absensi Harian</button>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th>NISN</th>
                <th>Nama Siswa</th>
                <th>Jurusan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $nisn = $row['nisn'];

                    $absensi_sql = "SELECT id_absen, keterangan FROM absensi WHERE nisn = '$nisn' AND tanggal_absensi = '$tanggal_hari_ini'";
                    $absensi_result = $conn->query($absensi_sql);

                    if ($absensi_result->num_rows > 0) {
                        $absensi_row = $absensi_result->fetch_assoc();

                        $button_text = "Sudah Absen";
                        $button_class = "disabled";
                    } else {
                        $button_text = "Absen Hari Ini";
                        $button_class = "";
                    }

                    echo "<tr>
                        <td>{$row['nisn']}</td>
                        <td>{$row['nama_siswa']}</td>
                        <td>{$row['jurusan']}</td>
                        <td>
                            <button onclick=\"processSiswa('{$row['nisn']}')\" 
                                    class='$button_class'
                                    " . ($button_class === 'disabled' ? 'disabled' : '') . ">
                                $button_text
                            </button>
                        </td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No records found</td></tr>";
            }
            ?>
        </tbody>

    </table>

    <script>
        function processSiswa(nisn) {
            window.location.href = 'process_siswa.php?nisn=' + nisn;
        }
    </script>
</body>

</html>

<?php $conn->close(); ?>