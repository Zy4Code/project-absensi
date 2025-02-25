<?php

// process siswa.php

$host = '127.0.0.1';
$db = 'smkn1panji';
$user = 'root';
$pass = '';
$conn = new mysqli(hostname: $host, username: $user, password: $pass, database: $db);

if ($conn->connect_error) {
    die("Connection failed:" . $conn->connect_error);
}

$nisn = $_GET['nisn'];
// var_dump($conn);
$sql = "SELECT * FROM siswa WHERE nisn = '$nisn'";
$result = $conn->query(query: $sql);
// var_dump($result);
$student = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Proses Absensi Siswa</title>
    <link rel="stylesheet" href="src/css/page_proses.css">
</head>

<body>
    <h2>Absensi untuk <?php echo $student['nama_siswa']; ?></h2>
    
    <form method="POST" action="submit_absensi.php">
        <p>Tanggal: <?php echo date('d-m-Y'); ?></p>
        
        <input type="hidden" name="nisn" value="<?php echo $student['nisn']; ?>">

        <div class="base-point">
            <div>
                <label>
                    <input type="radio" name="status" value="1">
                    <span>Sakit</span>
                </label>
            </div>

            <div>
                <label>
                    <input type="radio" name="status" value="2">
                    <span>Izin</span>
                </label>
            </div>

            <div>
                <label>
                    <input type="radio" name="status" value="3">
                    <span>Alpa</span>
                </label>
            </div>

            <p>Isi Keterangan Absen</p>
            <input type="text" name="keterangan" placeholder="Isi keterangan">
        </div>

        <button type="submit">Submit</button>
        <button type="button" onclick="window.location.href='index.php'">Kembali</button>
    </form> 
</body>
</html>

<?php $conn->close(); ?>
