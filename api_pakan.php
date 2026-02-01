<?php
// --- PENGATURAN DATABASE (GANTI INI) ---
$servername = "localhost";              // Biasanya tetap "localhost"
$username   = "panda";         // Ganti dengan USERNAME database kamu
$password   = "FmFaCk";       // Ganti dengan PASSWORD database kamu
$dbname     = "panda";      // Ganti dengan NAMA database kamu

// --- KONEKSI KE DATABASE ---
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}

// --- MENERIMA DATA DARI ESP32 ---
// Pastikan request yang masuk adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Ambil data yang dikirim ESP32
    // "sensor_jarak" dan "status_pakan" harus sama persis dengan di kodingan ESP32
    $sisa_pakan = isset($_POST['sensor_jarak']) ? $_POST['sensor_jarak'] : 0;
    $status     = isset($_POST['status_pakan']) ? $_POST['status_pakan'] : "Unknown";

    // --- SIMPAN KE TABEL ---
    $sql = "INSERT INTO riwayat_pakan (sisa_pakan, status) VALUES ('$sisa_pakan', '$status')";

    if ($conn->query($sql) === TRUE) {
        echo "Berhasil disimpan ke DB";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

} else {
    echo "Akses ditolak. Gunakan metode POST dari ESP32.";
}

$conn->close();
?>