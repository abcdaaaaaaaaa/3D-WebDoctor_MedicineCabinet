// https://wokwi.com/projects/466087474037114881

#include <WiFi.h>
#include <HTTPClient.h>
#include <ESP32Servo.h>
#include <SPI.h>
#include <MFRC522.h>

const char* ssid = "Wokwi-GUEST";
const char* password = "";

const char* serverUrl = "https://doctor.uzay.info/sendesp32.php";
const char* cupboardCode = "Medikal2026";

#define SS_PIN 5
#define RST_PIN 22
MFRC522 rfid(SS_PIN, RST_PIN);

Servo servos[4];
const int servoPins[4] = {32, 33, 25, 26};

String lastData = "";
bool waitingForPayment = false;
String pendingIllsId = "";
int pendingServoNum = -1;
unsigned long lastCheckTime = 0;
unsigned long paymentStartTime = 0;

void setup() {
  Serial.begin(115200);
  SPI.begin();
  rfid.PCD_Init();
  
  for (int i = 0; i < 4; i++) {
    servos[i].attach(servoPins[i]);
    servos[i].write(90);
  }
  
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
  }
}

void loop() {
  if (!waitingForPayment) {
    if (millis() - lastCheckTime > 10000 || lastCheckTime == 0) {
      if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;
        String url = String(serverUrl) + "?cupboard_code=" + cupboardCode;
        http.begin(url);
        int httpCode = http.GET();
        if (httpCode == HTTP_CODE_OK) {
          String payload = http.getString();
          if (payload.length() > 5 && payload != lastData) {
            lastData = payload;
            parseData(payload);
          }
        }
        http.end();
      }
      lastCheckTime = millis();
    }
  } else {
    if (millis() - paymentStartTime > 30000) {
      Serial.println("Süre doldu, kart okutulmadı. İşlem iptal edildi.");
      if (WiFi.status() == WL_CONNECTED) {
        HTTPClient httpTime;
        String urlTime = String(serverUrl) + "?cupboard_code=" + cupboardCode + "&action=status&state=alınmadı&ills_id=" + pendingIllsId;
        httpTime.begin(urlTime);
        httpTime.GET();
        httpTime.end();
      }
      waitingForPayment = false;
      lastData = "";
      lastCheckTime = millis();
    } else if (rfid.PICC_IsNewCardPresent() && rfid.PICC_ReadCardSerial()) {
      Serial.println("Kredi kartı okundu, işlem yapılıyor...");
      
      if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;
        String url = String(serverUrl) + "?cupboard_code=" + cupboardCode + "&action=pay&ills_id=" + pendingIllsId;
        http.begin(url);
        int httpCode = http.GET();
        
        if (httpCode == HTTP_CODE_OK) {
          String resp = http.getString();
          if (resp == "OK") {
            Serial.println("Ödeme başarılı, ilaç veriliyor...");
            if (pendingServoNum >= 1 && pendingServoNum <= 4) {
              servos[pendingServoNum - 1].write(180);
              delay(1000);
              servos[pendingServoNum - 1].write(90);
              delay(1000);
            }
          } else {
            Serial.println("Ödeme başarısız veya sistem hatası!");
            HTTPClient httpFail;
            String urlFail = String(serverUrl) + "?cupboard_code=" + cupboardCode + "&action=status&state=alınamadı&ills_id=" + pendingIllsId;
            httpFail.begin(urlFail);
            httpFail.GET();
            httpFail.end();
          }
        } else {
          Serial.println("Ödeme başarısız veya sistem hatası!");
          HTTPClient httpFail;
          String urlFail = String(serverUrl) + "?cupboard_code=" + cupboardCode + "&action=status&state=alınamadı&ills_id=" + pendingIllsId;
          httpFail.begin(urlFail);
          httpFail.GET();
          httpFail.end();
        }
        http.end();
      }
      
      rfid.PICC_HaltA();
      rfid.PCD_StopCrypto1();
      delay(2000);
      waitingForPayment = false;
      lastData = "";
      lastCheckTime = millis();
    }
  }
}

void parseData(String data) {
  int start = 0;
  int end = data.indexOf(';', start);
  while (end != -1) {
    String record = data.substring(start, end);
    printRecord(record);
    start = end + 1;
    end = data.indexOf(';', start);
  }
  String lastRecord = data.substring(start);
  if (lastRecord.length() > 0) {
    printRecord(lastRecord);
  }
}

void printRecord(String record) {
  String fields[8];
  int start = 0;
  int end = -1;
  for (int i = 0; i < 8; i++) {
    end = record.indexOf(',', start);
    if (end == -1 && i < 7) {
      fields[i] = record.substring(start);
      for (int j = i + 1; j < 8; j++) fields[j] = "";
      break;
    } else if (end == -1 && i == 7) {
      fields[i] = record.substring(start);
    } else {
      fields[i] = record.substring(start, end);
      start = end + 1;
    }
  }

  Serial.println("Kayıt:");
  Serial.print("Ad: "); Serial.println(fields[0]);
  Serial.print("Soyad: "); Serial.println(fields[1]);
  Serial.print("Marka: "); Serial.println(fields[2]);
  Serial.print("Doz: "); Serial.println(fields[3]);
  Serial.print("Günlük Miktar: "); Serial.println(fields[4]);
  
  String urgencyTr = "";
  if (fields[5] == "1") urgencyTr = "Düşük";
  else if (fields[5] == "2") urgencyTr = "Orta";
  else if (fields[5] == "3") urgencyTr = "Yüksek";
  else if (fields[5] == "4") urgencyTr = "Acil";
  
  Serial.print("Aciliyet: "); Serial.println(urgencyTr);
  Serial.print("NO: "); Serial.println(fields[6]);
  Serial.print("ID: "); Serial.println(fields[7]);
  Serial.println("\nLütfen ödeme için kartınızı okutun...");
  
  pendingServoNum = fields[6].toInt();
  pendingIllsId = fields[7];
  waitingForPayment = true;
  paymentStartTime = millis();
}
