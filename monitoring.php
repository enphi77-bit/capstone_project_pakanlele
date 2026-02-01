<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Pakan Lele</title>
    <meta http-equiv="refresh" content="30">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .header-lele {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .clock-container {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.5rem;
            color: #00ffea;
            text-shadow: 0 0 10px rgba(0, 255, 234, 0.7);
        }

        .card-settings {
            border-left: 5px solid #ffc107;
        }
    </style>
</head>

<body>

    <div class="container py-4">

        <div class="text-center header-lele">
            <h2>🐟 Dashboard Smart Pakan</h2>
            <div id="tanggalSekarang">Memuat Tanggal...</div>
            <div class="clock-container" id="jamDigital">00:00:00</div>
        </div>

        <?php
    $servername = "localhost";
    $username   = "panda";    // Username DB Kamu
    $password   = "FmFaCk";   // Password DB Kamu
    $dbname     = "panda";    // Nama DB Kamu

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("Koneksi Gagal: " . $conn->connect_error); }

    // 1. UPDATE JADWAL (Jika Tombol ditekan)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_jadwal'])) {
        $j1 = $_POST['jam1']; $m1 = $_POST['menit1'];
        $j2 = $_POST['jam2']; $m2 = $_POST['menit2'];
        
        $sql_update = "UPDATE pengaturan_jadwal SET jam1=$j1, menit1=$m1, jam2=$j2, menit2=$m2 WHERE id=1";
        if ($conn->query($sql_update) === TRUE) {
            echo "<div class='alert alert-success alert-dismissible fade show'>✅ Jadwal Berhasil Diperbarui! ESP32 akan sinkronisasi dalam 2 menit.</div>";
        }
    }

    // 2. AMBIL DATA JADWAL
    $result_get = $conn->query("SELECT * FROM pengaturan_jadwal WHERE id=1");
    $data_jadwal = $result_get->fetch_assoc();
    ?>

        <div class="row">
            <div class="col-md-5 mb-4">
                <div class="card card-settings shadow-sm">
                    <div class="card-header bg-warning text-dark fw-bold">⚙️ Atur Jadwal Makan</div>
                    <div class="card-body">
                        <form method="POST" action="">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Pagi (Jadwal 1)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Jam</span>
                                    <select name="jam1" class="form-select">
                                        <?php for($i=0; $i<=23; $i++) { 
                                        $val = sprintf("%02d", $i); 
                                        $sel = ($data_jadwal['jam1'] == $i) ? "selected" : "";
                                        echo "<option value='$i' $sel>$val</option>"; 
                                    } ?>
                                    </select>
                                    <span class="input-group-text">Menit</span>
                                    <select name="menit1" class="form-select">
                                        <?php for($i=0; $i<=59; $i++) { 
                                        $val = sprintf("%02d", $i); 
                                        $sel = ($data_jadwal['menit1'] == $i) ? "selected" : "";
                                        echo "<option value='$i' $sel>$val</option>"; 
                                    } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Sore (Jadwal 2)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Jam</span>
                                    <select name="jam2" class="form-select">
                                        <?php for($i=0; $i<=23; $i++) { 
                                        $val = sprintf("%02d", $i); 
                                        $sel = ($data_jadwal['jam2'] == $i) ? "selected" : "";
                                        echo "<option value='$i' $sel>$val</option>"; 
                                    } ?>
                                    </select>
                                    <span class="input-group-text">Menit</span>
                                    <select name="menit2" class="form-select">
                                        <?php for($i=0; $i<=59; $i++) { 
                                        $val = sprintf("%02d", $i); 
                                        $sel = ($data_jadwal['menit2'] == $i) ? "selected" : "";
                                        echo "<option value='$i' $sel>$val</option>"; 
                                    } ?>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" name="update_jadwal" class="btn btn-primary w-100 fw-bold">💾 Simpan
                                Jadwal Baru</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">📊 Riwayat Pakan Terakhir</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Sisa Pakan (%)</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                        // Konfigurasi ambang batas sesuai dengan kode .ino
                        $JARAK_PENUH_CM = 3.5;
                        $JARAK_KOSONG_CM = 30.5;
                        $RANGE = $JARAK_KOSONG_CM - $JARAK_PENUH_CM;

                        $result = $conn->query("SELECT * FROM riwayat_pakan ORDER BY waktu DESC LIMIT 5");
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                // Ambil data jarak dari database (asumsi kolom sisa_pakan menyimpan nilai cm)
                                $jarak_cm = floatval($row["sisa_pakan"]);
                                
                                // --- LOGIKA PERHITUNGAN PERSEN (Sama dengan .ino) ---
                                $jarak_relatif = $jarak_cm - $JARAK_PENUH_CM;
                                
                                // Batasi agar tidak meluap (clamping)
                                if ($jarak_relatif < 0) $jarak_relatif = 0;
                                if ($jarak_relatif > $RANGE) $jarak_relatif = $RANGE;
                                
                                // Hitung persentase
                                $persen = 100.0 - (($jarak_relatif / $RANGE) * 100.0);
                                
                                // Menentukan warna indikator (Merah jika di bawah 20%)
                                $warna = ($persen < 20) ? "text-danger fw-bold" : "text-success";
                                
                               // Bagian yang diubah: Format date('d/m/Y H:i:s') untuk menampilkan Tanggal dan Jam
								echo "<tr>";
								echo "<td>" . date('d/m/Y H:i:s', strtotime($row["waktu"])) . "</td>";

								// Menampilkan dalam format persen dengan 2 angka di belakang koma
								echo "<td class='$warna'>" . number_format($persen, 2) . " %</td>";

								echo "<td><span class='badge bg-success'>SUKSES</span></td>";
								echo "</tr>";
                            }
                        } else { 
                            echo "<tr><td colspan='3' class='text-center'>Belum ada data.</td></tr>"; 
                        }
                        $conn->close();
                        ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateClock() {
            var now = new Date();
            document.getElementById('jamDigital').innerText = now.toLocaleTimeString('id-ID', { hour12: false });
            document.getElementById('tanggalSekarang').innerText = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>

</body>

</html>