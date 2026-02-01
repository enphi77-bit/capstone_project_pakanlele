# capstone_project_pakanlele

🐟 Smart Pakan Lele (IoT-Based Automatic Catfish Feeder)Sistem pemberi pakan lele otomatis berbasis IoT yang menggunakan ESP32 sebagai pengendali utama. Sistem ini memungkinkan penjadwalan pemberian pakan secara real-time melalui dashboard web dan pemantauan sisa pakan menggunakan sensor ultrasonik.

🌟 Fitur UtamaPenjadwalan Otomatis: Pemberian pakan dilakukan dua kali sehari (Pagi & Sore) berdasarkan jam yang tersimpan di server.Monitoring Sisa Pakan: Menggunakan sensor ultrasonik untuk menghitung persentase sisa pakan dalam wadah.Sinkronisasi RTC & NTP: Waktu pada alat selalu akurat karena disinkronkan dengan server waktu internet (NTP) dan disimpan pada modul RTC DS3231.Dashboard Web: Antarmuka web untuk mengatur jam makan dan melihat riwayat pemberian pakan secara langsung.Mode Hemat Daya/Idle: Servo akan dimatikan (detach) saat tidak digunakan untuk mencegah getaran dan menghemat daya.

🛠️ Kebutuhan Perangkat KerasESP32 (Microcontroller).Sensor Ultrasonik HC-SR04 (Cek level pakan).Servo Motor (Mekanisme pembuka pakan).RTC DS3231 (Modul waktu eksternal).

💻 Tech StackFirmware: C++ (Arduino IDE).Backend: PHP.Database: MySQL/MariaDB.Frontend: HTML, Bootstrap 5, JavaScript.

⚙️ Konfigurasi Pin (ESP32)KomponenPin ESP32Servo SignalGPIO 4 Ultrasonic TRIGGPIO 26 Ultrasonic ECHOGPIO 27 RTC (I2C)SDA & SCL default ESP32

🚀 Cara Instalasi1. Persiapan DatabaseBuat database baru bernama panda di server kamu.Import file backup.sql ke dalam database tersebut untuk membuat tabel pengaturan_jadwal dan riwayat_pakan.2. Setup Server (PHP)Upload file api_pakan.php, get_jadwal.php, dan monitoring.php ke direktori web server kamu.Pastikan konfigurasi database di setiap file PHP sudah sesuai dengan credentials database kamu (localhost, username, password).3. Setup ESP32Buka file cappstonesketch_nov20a__1_.ino menggunakan Arduino IDE.Sesuaikan ssid dan password WiFi kamu.Ubah serverUrl_Lapor dan serverUrl_Jadwal menjadi URL domain tempat kamu menyimpan file PHP tadi.Unggah kode ke ESP32.

📊 Batas Ukuran WadahSistem dikonfigurasi dengan parameter jarak berikut:Jarak Penuh: $3.5$ cm (Pakan menyentuh batas atas).Jarak Kosong: $30.5$ cm (Dasar wadah).
