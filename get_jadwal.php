<?php
// JANGAN UBAH BAGIAN INI KECUALI PASSWORD GANTI
$servername = "localhost";
$username   = "panda";
$password   = "FmFaCk";
$dbname     = "panda";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Koneksi Gagal"); }

// Ambil data dari tabel pengaturan
$sql = "SELECT * FROM pengaturan_jadwal WHERE id=1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    // Kirim data format: JAM1;MENIT1;JAM2;MENIT2
    echo $row['jam1'] . ";" . $row['menit1'] . ";" . $row['jam2'] . ";" . $row['menit2'];
} else {
    echo "ERROR";
}
$conn->close();
?>