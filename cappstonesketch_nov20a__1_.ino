#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <ESP32Servo.h>
#include <Wire.h>
#include <RTClib.h>
#include "time.h"

// --- KONFIGURASI WIFI ---
const char* ssid = "FAISAL";
const char* password = "12345678";

// --- KONFIGURASI WAKTU (NTP) ---
const char* ntpServer = "pool.ntp.org";
const long gmtOffset_sec = 25200; 
const int daylightOffset_sec = 0;

// --- ALAMAT SERVER ---
const char* serverUrl_Lapor = "https://panda.ftiunwaha.my.id/pakan/api_pakan.php";
const char* serverUrl_Jadwal = "https://panda.ftiunwaha.my.id/pakan/get_jadwal.php";

// --- KONFIGURASI PIN ---
#define PIN_SERVO 4
#define PIN_TRIG 26
#define PIN_ECHO 27

// --- BATAS KONVERSI JARAK KE PERSEN ---
const float JARAK_PENUH_CM = 3.5;
const float JARAK_KOSONG_CM = 30.5;

// --- OBJEK ---
Servo myServo;
RTC_DS3231 rtc;

// --- VARIABEL JADWAL ---
int jam1 = 7; int menit1 = 0;
int jam2 = 16; int menit2 = 30;

bool sudahMakan = false;
unsigned long lastCheckJadwal = 0;

// --- DEKLARASI FUNGSI ---
float konversiJarakKePersen(float jarak_cm);
float bacaUltrasonik();
void kirimDataKeServer(float jarak, float persen, String status);
void berikanPakan();
void sinkronisasiWaktuInternet();
void ambilJadwalServer();

void setup() {
    Serial.begin(115200);

    // 1. Setup Pin Sensor
    pinMode(PIN_TRIG, OUTPUT);
    pinMode(PIN_ECHO, INPUT);

    // 2. Inisialisasi Servo
    myServo.setPeriodHertz(50);
    myServo.attach(PIN_SERVO);
    myServo.write(5); // Posisi aman sedikit di atas 0
    delay(500);
    myServo.detach(); // Matikan sinyal saat idle agar tidak bergetar

    // 3. Setup RTC
    if (!rtc.begin()) {
        Serial.println("RTC Error!");
        while (1);
    }

    // 4. Konek WiFi
    WiFi.begin(ssid, password);
    Serial.print("Menghubungkan WiFi");
    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }
    Serial.println("\nWiFi OK!");

    sinkronisasiWaktuInternet();
    ambilJadwalServer();
}

void loop() {
    DateTime now = rtc.now();

    // --- CEK JADWAL DI WEB SETIAP 1 MENIT (60000 ms) ---
    if (millis() - lastCheckJadwal > 60000) { 
        ambilJadwalServer();
        lastCheckJadwal = millis();
    }

    float jarakSekarang = bacaUltrasonik();
    float persenSisa = konversiJarakKePersen(jarakSekarang);

    Serial.printf("Jam: %02d:%02d:%02d | Jadwal: %02d:%02d & %02d:%02d | Sisa: %.2f cm (%.2f%%)\n",
                  now.hour(), now.minute(), now.second(), jam1, menit1, jam2, menit2, jarakSekarang, persenSisa);

    bool waktuMakan1 = (now.hour() == jam1 && now.minute() == menit1);
    bool waktuMakan2 = (now.hour() == jam2 && now.minute() == menit2);

    if (waktuMakan1 || waktuMakan2) {
        if (!sudahMakan) {
            Serial.println(">>> WAKTUNYA MAKAN! <<<");
            berikanPakan();
            kirimDataKeServer(jarakSekarang, persenSisa, "BERHASIL_MAKAN");
            sudahMakan = true;
        }
    } else {
        sudahMakan = false;
    }

    delay(1000);
}

void berikanPakan() {
    myServo.attach(PIN_SERVO); 
    delay(100); 

    for (int pos = 0; pos <= 40; pos += 1) {
        myServo.write(pos);
        delay(15);
    }

    delay(35000); 

    for (int pos = 40; pos >= 0; pos -= 1) {
        myServo.write(pos);
        delay(15);
    }
    
    delay(200); 
    myServo.detach(); 
    Serial.println("Servo Detached (Idle Mode)");
}

float bacaUltrasonik() {
    digitalWrite(PIN_TRIG, LOW); delayMicroseconds(2);
    digitalWrite(PIN_TRIG, HIGH); delayMicroseconds(10);
    digitalWrite(PIN_TRIG, LOW);
    long dur = pulseIn(PIN_ECHO, HIGH, 30000); 
    if (dur == 0) return 30.5; 
    return dur * 0.034 / 2;
}

float konversiJarakKePersen(float jarak_cm) {
    float range = JARAK_KOSONG_CM - JARAK_PENUH_CM;
    float jarak_relatif = jarak_cm - JARAK_PENUH_CM;
    if (jarak_relatif < 0) jarak_relatif = 0;
    if (jarak_relatif > range) jarak_relatif = range;
    return 100.0 - ((jarak_relatif / range) * 100.0);
}

void sinkronisasiWaktuInternet() {
    if(WiFi.status() == WL_CONNECTED) {
        configTime(gmtOffset_sec, daylightOffset_sec, ntpServer);
        struct tm timeinfo;
        if (getLocalTime(&timeinfo)) {
            rtc.adjust(DateTime(timeinfo.tm_year + 1900, timeinfo.tm_mon + 1, timeinfo.tm_mday,
                                timeinfo.tm_hour, timeinfo.tm_min, timeinfo.tm_sec));
            Serial.println("✅ Jam RTC diperbarui!");
        }
    }
}

void ambilJadwalServer() {
    if(WiFi.status() == WL_CONNECTED){
        WiFiClientSecure client;
        client.setInsecure();
        HTTPClient http;
        if (http.begin(client, serverUrl_Jadwal)) {
            int code = http.GET();
            if (code > 0) {
                String pl = http.getString();
                int j1, m1, j2, m2;
                if (sscanf(pl.c_str(), "%d;%d;%d;%d", &j1, &m1, &j2, &m2) == 4) {
                    jam1 = j1; menit1 = m1; jam2 = j2; menit2 = m2;
                    Serial.println("[SERVER] Jadwal diperbarui ke 1 menit sekali.");
                }
            }
            http.end();
        }
    }
}

void kirimDataKeServer(float jarak, float persen, String status) {
    if(WiFi.status() == WL_CONNECTED){
        WiFiClientSecure c; c.setInsecure();
        HTTPClient h;
        if (h.begin(c, serverUrl_Lapor)) {
            h.addHeader("Content-Type", "application/x-www-form-urlencoded");
            String postData = "sensor_jarak=" + String(jarak, 2) +
                              "&sisa_pakan=" + String(persen, 2) +
                              "&status_pakan=" + status;
            h.POST(postData);
            h.end();
        }
    }
}