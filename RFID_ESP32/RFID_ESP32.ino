#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// ---------- WiFi Configuration ----------
const char* ssid = "Proton";
const char* password = "Tasko009";

// ---------- API Configuration ----------
const char* serverUrl = "http://192.168.1.103/rfid/api/attendance.php";

const char* deviceUid = "ESP32_RFID_001";
const char* apiKey = "sk_test_1234567890abcdef";

// ---------- OLED ----------
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
#define OLED_RESET    -1
#define OLED_ADDR     0x3C

Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// ---------- RFID ----------
#define SS_PIN 5
#define RST_PIN  27

// ---------- Buzzer ----------
#define BuzzerPin 33

// // ---------- LED Pins (Optional) ----------
// #define LED_SUCCESS 25  // Green LED for success
// #define LED_ERROR 26    // Red LED for error
#define LED_WIFI 2      // Built-in LED for WiFi status

MFRC522 mfrc522(SS_PIN, RST_PIN);

// Variables
bool wifiConnected = false;
unsigned long lastCardTime = 0;
const unsigned long cardCooldown = 1000; // 3 seconds between scans

// WiFi connection status
void connectToWiFi() {
  Serial.println("Connecting to WiFi...");
  display.clearDisplay();
  display.setCursor(0, 0);
  display.println("Connecting WiFi");
  display.display();
  
  WiFi.begin(ssid, password);
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    digitalWrite(LED_WIFI, !digitalRead(LED_WIFI)); // Blink LED
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    wifiConnected = true;
    digitalWrite(LED_WIFI, HIGH);
    Serial.println("\nWiFi connected!");
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());
    
    display.clearDisplay();
    display.setCursor(0, 0);
    display.println("WiFi Connected!");
    display.print("IP: ");
    display.println(WiFi.localIP());
    display.display();
    delay(2000);
  } else {
    wifiConnected = false;
    digitalWrite(LED_WIFI, LOW);
    Serial.println("\nWiFi connection failed!");
    
    display.clearDisplay();
    display.setCursor(0, 0);
    display.println("WiFi Failed!");
    display.println("Check credentials");
    display.display();
  }
}

// Sound functions
void playSuccessSound() {
  // tii tiith pattern
  tone(BuzzerPin, 1000, 200); // First tii
  delay(250);
  tone(BuzzerPin, 1200, 300); // Second tiith (higher and longer)
  delay(350);
  noTone(BuzzerPin);
}

void playErrorSound() {
  // taaaaah pattern (long single tone)
  tone(BuzzerPin, 300, 1000); // Low, long tone
  delay(1100);
  noTone(BuzzerPin);
}

void playScanSound() {
  // Short beep when card is detected
  tone(BuzzerPin, 800, 100);
  delay(150);
  noTone(BuzzerPin);
}

void playConnectingSound() {
  // Two short beeps for connection
  tone(BuzzerPin, 600, 100);
  delay(150);
  tone(BuzzerPin, 600, 100);
  delay(150);
  noTone(BuzzerPin);
}

// Send RFID data to API
void sendRFIDToAPI(String rfidUid) {
  if (!wifiConnected) {
    Serial.println("WiFi not connected!");
    display.clearDisplay();
    display.setCursor(0, 0);
    display.println("No WiFi!");
    display.println("Reconnecting...");
    display.display();
    
    playErrorSound();
    connectToWiFi();
    return;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    
    // Prepare POST data
    String postData = "rfid_uid=" + rfidUid + 
                      "&device_uid=" + String(deviceUid) + 
                      "&api_key=" + String(apiKey);
    
    // For debugging, also print to Serial
    Serial.println("Sending to API:");
    Serial.println("RFID: " + rfidUid);
    
    // Update display
    display.clearDisplay();
    display.setCursor(0, 0);
    display.println("Sending to server");
    display.println("RFID: " + rfidUid);
    display.println("Please wait...");
    display.display();
    
    // Start HTTP connection
    http.begin(serverUrl);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    
    // Send POST request
    int httpResponseCode = http.POST(postData);
    
    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.print("HTTP Response code: ");
      Serial.println(httpResponseCode);
      Serial.print("Response: ");
      Serial.println(response);
      
      // Parse JSON response
      DynamicJsonDocument doc(1024);
      DeserializationError error = deserializeJson(doc, response);
      
      if (!error) {
        bool success = doc["success"];
        String message = doc["message"].as<String>();
        
        display.clearDisplay();
        display.setCursor(0, 0);
        
        if (success) {
          // Success response
          String studentName = doc["data"]["student_name"].as<String>();
          String time = doc["data"]["time"].as<String>();
          
          Serial.println("Success! Student: " + studentName);
          
          display.println("SUCCESS!");
          display.println("Student: " + studentName);
          display.println("Time: " + time);
          display.println("Attendance Recorded");
          
          // Visual feedback
          // digitalWrite(LED_SUCCESS, HIGH);
          // digitalWrite(LED_ERROR, LOW);
          
          // Sound feedback
          playSuccessSound();
          
          delay(1000);
          // digitalWrite(LED_SUCCESS, LOW);
        } else {
          // Error response
          Serial.println("Error: " + message);
          
          display.println("ERROR!");
          display.println(message);
          display.println("Try again");
          
          // // Visual feedback
          // digitalWrite(LED_SUCCESS, LOW);
          // digitalWrite(LED_ERROR, HIGH);
          
          // Sound feedback
          playErrorSound();
          
          delay(1000);
          // digitalWrite(LED_ERROR, LOW);
        }
        display.display();
      } else {
        // JSON parse error
        Serial.println("JSON parse failed");
        
        display.clearDisplay();
        display.setCursor(0, 0);
        display.println("Server Error");
        display.println("Invalid response");
        display.display();
        
        playErrorSound();
      }
    } else {
      // HTTP error
      Serial.print("HTTP Error: ");
      Serial.println(httpResponseCode);
      
      display.clearDisplay();
      display.setCursor(0, 0);
      display.println("Connection Failed");
      display.println("Error: " + String(httpResponseCode));
      display.println("Check server URL");
      display.display();
      
      playErrorSound();
    }
    
    http.end();
  } else {
    Serial.println("WiFi disconnected");
    wifiConnected = false;
    
    display.clearDisplay();
    display.setCursor(0, 0);
    display.println("WiFi Lost!");
    display.println("Reconnecting...");
    display.display();
    
    playErrorSound();
  }
}

// Convert RFID bytes to hex string
String getRFIDString() {
  String rfidString = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    byte uidByte = mfrc522.uid.uidByte[i];
    
    // Convert to hex (without leading zeros)
    if (uidByte < 0x10) {
      rfidString += "0";
    }
    rfidString += String(uidByte, HEX);
  }
  rfidString.toUpperCase();
  return rfidString;
}

void setup() {
  Serial.begin(115200);
  
  // Initialize pins
  pinMode(BuzzerPin, OUTPUT);
  // pinMode(LED_SUCCESS, OUTPUT);
  // pinMode(LED_ERROR, OUTPUT);
  pinMode(LED_WIFI, OUTPUT);
  
  // All LEDs off initially
  // digitalWrite(LED_SUCCESS, LOW);
  // digitalWrite(LED_ERROR, LOW);
  digitalWrite(LED_WIFI, LOW);
  
  // SPI (RC522)
  SPI.begin(18, 19, 23, SS_PIN);
  mfrc522.PCD_Init();
  mfrc522.PCD_SetAntennaGain(mfrc522.RxGain_max);
  
  // OLED
  Wire.begin(21, 22);
  if (!display.begin(SSD1306_SWITCHCAPVCC, OLED_ADDR)) {
    Serial.println("OLED failed");
    while (1);
  }
  
  // Initial display
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println("RFID Attendance");
  display.println("System Ready");
  display.println("");
  display.println("Connecting WiFi...");
  display.display();
  
  // Play connecting sound
  playConnectingSound();
  
  // Connect to WiFi
  connectToWiFi();
  
  // Ready message
  display.clearDisplay();
  display.setCursor(0, 0);
  display.println("RFID Attendance");
  display.println("System Ready!");
  if (wifiConnected) {
    display.println("WiFi: Connected");
    display.print("Server: ");
    display.println(serverUrl);
  } else {
    display.println("WiFi: Disconnected");
    display.println("Working offline");
  }
  display.println("");
  display.println("Scan RFID Card...");
  display.display();
  
  Serial.println("System Ready. Scan RFID card...");
}

void loop() {
  // Check WiFi connection periodically
  static unsigned long lastWifiCheck = 0;
  if (millis() - lastWifiCheck > 10000) { // Check every 10 seconds
    lastWifiCheck = millis();
    if (WiFi.status() != WL_CONNECTED) {
      wifiConnected = false;
      digitalWrite(LED_WIFI, LOW);
      Serial.println("WiFi disconnected, reconnecting...");
      connectToWiFi();
    }
  }
  
  // Check for RFID card
  if (!mfrc522.PICC_IsNewCardPresent()) {
    return;
  }
  
  if (!mfrc522.PICC_ReadCardSerial()) {
    return;
  }
  
  // Card detected - check cooldown
  if (millis() - lastCardTime < cardCooldown) {
    Serial.println("Cooldown active, ignoring scan");
    mfrc522.PICC_HaltA();
    mfrc522.PCD_StopCrypto1();
    return;
  }
  
  lastCardTime = millis();
  
  // Play scan sound
  playScanSound();
  
  // Get RFID UID
  String rfidUid = getRFIDString();
  
  // Display on OLED
  display.clearDisplay();
  display.setCursor(0, 0);
  display.println("Card Detected!");
  display.println("UID: " + rfidUid);
  display.println("Processing...");
  display.display();
  
  // Print to Serial
  Serial.println("\n--- Card Detected ---");
  Serial.print("RFID UID: ");
  Serial.println(rfidUid);
  
  // Send to API
  sendRFIDToAPI(rfidUid);
  
  // Halt RFID
  mfrc522.PICC_HaltA();
  mfrc522.PCD_StopCrypto1();
  
  // Wait before next scan
  delay(1000);
  
  // Return to ready state
  display.clearDisplay();
  display.setCursor(0, 0);
  display.println("RFID Attendance");
  display.println("System Ready");
  if (wifiConnected) {
    display.println("WiFi: Connected");
  } else {
    display.println("WiFi: Disconnected");
  }
  display.println("");
  display.println("Scan RFID Card...");
  display.display();
}