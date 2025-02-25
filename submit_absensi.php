<?php

// submit_absensi.php

$host = '127.0.0.1';
$db = 'smkn1panji';
$user = 'root';
$pass = '';
$conn = new mysqli(hostname: $host, username: $user, password: $pass, database: $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_POST['status'])) {
    echo "<script>alert('Harap pilih status absensi!')</script>";
} else {
    $nisn = $_POST['nisn'];
    $status = $_POST['status'];
    $keterangan = $_POST['keterangan'];
    $date = date('Y-m-d'); //tanggal saat ini
    $sql = "INSERT INTO absensi (nisn, id_absen, keterangan, tanggal_absensi) VALUES ('$nisn', $status, '$keterangan', '$date')";
    
    if ($conn->query($sql) === TRUE) {
        echo "
        <div class='success-message'>
            <h1>Absensi berhasil disimpan!</h1>
            <a href='index.php' class='button'>Kembali ke Daftar Siswa</a>
        </div>";
    } else {
        echo "<div class='error-message'>Error: " . $sql . "<br>" . $conn->error . "</div>";
    }
    
    $conn->close();
}

?>
