#include <TinyGPSPlus.h>
#include <SoftwareSerial.h>
#include "FS.h"
#include <SPI.h>
#include <SD.h>
#include <ArduinoJson.h>
#include <HardwareSerial.h>
#include <BluetoothSerial.h>

#include <WiFi.h>
#include "time.h"

//const char* ssid     = "Redmi 9T";
//const char* password = "kudzaitenderere";

const char* ssid     = "Naspers";
const char* password = "googleDevelop";

const char* ntpServer = "pool.ntp.org";
const long  gmtOffset_sec = 0;
const int   daylightOffset_sec = 7200;

#if !defined(CONFIG_BT_ENABLED) || !defined(CONFIG_BLUEDROID_ENABLED)
#error Bluetooth is not enabled! Please run `make menuconfig` to and enable it
#endif

#if !defined(CONFIG_BT_SPP_ENABLED)
#error Serial Bluetooth not available or not enabled. It is only available for the ESP32 chip.
#endif

BluetoothSerial SerialBT;

#define BT_DISCOVER_TIME  10000


static bool btScanAsync = true;
static bool btScanSync = true;

#define RXPin 16
#define TXPin 17

int scanTime = 5; //In seconds
int sniffed_macs_global_counter;
String Sniffed_Mac_Addresses[150];
int rssi[150]; //collect the rssi data on each peripheral
String Internet_String = "";
String incoming = "";
volatile bool finshed_scan;

float latitude; //code was used for location before
float longitude;
String Tym;

void Scan_classic_BT();
volatile bool start_inq_flag;
int ok_times = 0;
char _c_ = ' ';
String _mac_;
char RxBuffer[50];
uint8_t Rx_cntr;
volatile bool internet_sending_flagg;
int TotalClassicMacs = 0;
volatile bool scan_low_energy;

int first_comma = 0;
int second_comma = 0;
char cbt_strength[5];
String HC_incoming_String;

unsigned long currentMillis;
unsigned long previousMillis;
const unsigned long time_to_rx_gps = 1000 * 5; //5 seconds before every sniff round
volatile bool Begin_sniff_flagg;

unsigned long previousMillis_one;
const unsigned long time_to_CBT = 20 * 1000;

volatile bool end_classic_scan_flagg;

// The TinyGPSPlus object
TinyGPSPlus gps;

void ExtractBTDevices(BTAdvertisedDevice* pDevice) {
  Serial.printf("Found a device: %s\n", pDevice->toString().c_str());
  if (sniffed_macs_global_counter < 150)
  {
    BTAddress mac = pDevice->getAddress();
    String mac1 = mac.toString().c_str();
    Sniffed_Mac_Addresses[sniffed_macs_global_counter] = mac.toString().c_str();
    rssi[sniffed_macs_global_counter] = pDevice->getRSSI();
    sniffed_macs_global_counter++;
  }
}

void writeFile(fs::FS &fs, const char * path, const String message) {
  Serial.printf("Writing file: %s\n", path);

  File file = fs.open(path, FILE_WRITE);
  if (!file) {
    Serial.println("Failed to open file for writing");
    return;
  }
  if (file.print(message)) {
    Serial.println("File written");
  } else {
    Serial.println("Write failed");
  }
  file.close();
}

void appendFile(fs::FS &fs, const char * path, const String message){
    Serial.printf("Appending to file: %s\n", path);

    File file = fs.open(path, FILE_APPEND);
    if(!file){
        Serial.println("Failed to open file for appending");
        return;
    }
    if(file.println(message)){
        Serial.println("Message appended");
    } else {
        Serial.println("Append failed");
    }
    file.close();
}
void printLocalTime(){
  struct tm time;
  if(!getLocalTime(&time)){
    Serial.println("Failed to obtain time");
    return;
  }
  Serial.println("\n---------TIME----------");
  String stamp="";

  stamp += time.tm_mday;
  stamp += '/';
  stamp += time.tm_mon;
  stamp += '/';
  stamp += "2022";
  stamp += " ";

  int ho_ur = time.tm_hour;
  if(ho_ur<10)
  {
     stamp += "0";
  }
  stamp += ho_ur;
  stamp += ':';
  
  int minutes = time.tm_min;
  if(minutes<10)
  {
     stamp += "0";
  }
  stamp += minutes;
  stamp += ':';

  int second_s = time.tm_sec;
  if(second_s<10)
  {
    stamp += "0";
  }
  stamp += second_s;
  
  Serial.println(stamp);
  Tym = stamp;
  stamp = "";

  return;
}

void setup()
{
  Serial.begin(115200);
  SerialBT.begin("ESP32test"); //Bluetooth device name
  Serial2.begin(9600, SERIAL_8N1, RXPin, TXPin);
  pinMode(23, INPUT_PULLUP);
  if (!SD.begin()) {
    Serial.println("Card Mount Failed");
    return;
  }
  sniffed_macs_global_counter = 0;
  Serial.println("Scanning...");
  finshed_scan = false;
  start_inq_flag = true;
  scan_low_energy = true;
  Begin_sniff_flagg = false;

  end_classic_scan_flagg = true;
  //BTserial.println("AT+INQM=1,9,4"); //RSSI, Max 10 devices, ~30s
  //delay(1000);

  Serial.print("Connecting to ");
  Serial.println(ssid);
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("");
  Serial.println("WiFi connected.");
  
  // Init and get the time
  configTime(gmtOffset_sec, daylightOffset_sec, ntpServer);
  printLocalTime();

  //disconnect WiFi as it's no longer needed
  WiFi.disconnect(true);
  WiFi.mode(WIFI_OFF);

}

void loop()
{
  currentMillis = millis();

  if ((currentMillis - previousMillis) <= time_to_rx_gps)
  {
    while (Serial2.available() > 0)
    {
      gps.encode(Serial2.read());
      char b = Serial2.read();
      Serial.print(b);
    }
  }
  else {
    //Serial.println("Begin scan");
    Begin_sniff_flagg = true;
    previousMillis_one = currentMillis;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////////

  if (Begin_sniff_flagg == true)
  {
    //put your main code here, to run repeatedly:
    if (scan_low_energy == true)
    {
    Serial.println("Starting discover...");
    BTScanResults *pResults = SerialBT.discover(time_to_CBT);
    if (pResults)
    {
      //pResults->dump(&Serial);
      int total_devices = pResults->getCount();
      Serial.println(total_devices);
      Serial.println(total_devices);
      Serial.println(total_devices);

      for(int i =0; i<total_devices; i++)
      {
        BTAdvertisedDevice* pDevice = pResults->getDevice(i);
        ExtractBTDevices(pDevice);
      }

    }

        //Serial.println("sTOPPING MACHINE");
        //delay(10000);
        Serial.print("Stopping discoverAsync... ");
        SerialBT.discoverAsyncStop();
        Serial.println("stopped");

        Serial.print("Devices found: ");
        //Serial.println(foundDevices.getCount());
        Serial.println(sniffed_macs_global_counter);
        Serial.println("Scan done!");
        Serial.println("===========================================================================");
        Serial.println("Now scan for duplicates");
        LowEnergyCheckForDuplicates();
        Serial.println("===========================================================================");
        Serial.println("===========================================================================");
        Serial.println("Now change the low energy flag to false");
        delay(2000);
        scan_low_energy = false;
        end_classic_scan_flagg=false;
     
    }

    //now we scan classic
    if (end_classic_scan_flagg == false)
    {
      finshed_scan = true;
    }

    if (finshed_scan == true)
    {
      Serial.println("Now scan for CLASSIC duplicates");
      LowEnergyCheckForDuplicates();
      Serial.println("===========================================================================");
      Serial.println("===========================================================================");

      displayInfo();
      AlldomumentingJson();
      //delay(500);
      sniffed_macs_global_counter = 0;
      end_classic_scan_flagg = true;
      finshed_scan = false;
      start_inq_flag = true;
      Begin_sniff_flagg = false;
      TotalClassicMacs = 0;
      scan_low_energy = true;
      previousMillis = millis();
    }

  }

}

void displayInfo()
{
  Serial.println("===========================================================================");
  Serial.println("===========================================================================");
  Serial.print(F("Location: "));
  if (gps.location.isValid())
  {

    latitude = gps.location.lat();
    longitude = gps.location.lng();
    Serial.print(F("Latitude: "));
    Serial.print(latitude, 6);
    Serial.print(F("Longitude: "));
    Serial.println(longitude, 6);
  }
  else
  {
    Serial.print(F("INVALID"));
  }

  //current_time();
  printLocalTime();

  Serial.println("===========================================================================");
  Serial.println("===========================================================================");
  Serial.println();
}

void current_time()
{
  String _tym_;

  //Serial.print(F("Time: "));
  if (gps.date.isValid())
  {
    _tym_ += gps.date.month();
    _tym_ += "/";
    _tym_ += gps.date.day();
    _tym_ += "/";
    _tym_ += gps.date.year();
  }
  else
  {
    _tym_ = "DATE INVALID";
  }

  _tym_ += " ";
  if (gps.time.isValid())
  {
    if (gps.time.hour() < 10) {
      _tym_ += "0";
    }
    _tym_ += gps.time.hour();
    _tym_ += ":";
    if (gps.time.minute() < 10) {
      _tym_ += "0";
    }
    _tym_ += gps.time.minute();
    _tym_ += ":";
    if (gps.time.second() < 10) {
      _tym_ += "0";
    }
    _tym_ += gps.time.second();
  }
  else
  {
    _tym_ = "TIME INVALID";
  }

  // Serial.print("Inside gps functon the returned tym ->");
  //Serial.println(_tym_);
  Tym = _tym_;
  _tym_ = "";
  //Serial.println();

  return;
}

void LowEnergyCheckForDuplicates()
{
  int i, j, k;
  int old_number_of_macs = sniffed_macs_global_counter;

  //lets sort them and remove the duplicates

  for ( i = 0; i < sniffed_macs_global_counter; i++)
  {
    for ( j = i + 1; j < sniffed_macs_global_counter; j++)
    {
      /* If any duplicate found */
      if ((strcmp(((Sniffed_Mac_Addresses[i].c_str())), ((Sniffed_Mac_Addresses[j].c_str()))) == 0))
      {
        /* Delete the current duplicate element */
        for (k = j; k < sniffed_macs_global_counter - 1; k++)
        {
          Sniffed_Mac_Addresses[k] = Sniffed_Mac_Addresses[k + 1];
          rssi[k] = rssi[k + 1];
        }
        /* Decrement size after removing duplicate element */
        sniffed_macs_global_counter--;

        /* If shifting of elements occur then don't increment j */
        j--;
      }
    }
  }

  Serial.println("Function: duplicates");
  Serial.print("Old Unsorted Array had: "); Serial.print(old_number_of_macs); Serial.println(" Elements");
  Serial.print("New Sorted Array has: "); Serial.print(sniffed_macs_global_counter); Serial.println(" Elements");
  Serial.println(F("========================================"));

  for (int m = 0; m < sniffed_macs_global_counter; m++)
  {
    Serial.print(Sniffed_Mac_Addresses[m]);
    Serial.print(" RSSI=");
    Serial.println(rssi[m]);
  }
  Serial.println(F("========================================"));
  Serial.println(F("========================================"));
  //delay(100);
  return;
}

void AlldomumentingJson()
{
  Serial.println("======================START==========================");
  Serial.println("=====================================================");
  Serial.print("ALL DOCUMENTING FUNCTION");

  //calculate the capacity of json document
  uint16_t capa = 128 * sniffed_macs_global_counter;
  if (capa <= 1024)
  {
    capa = 1024;
  }
  Serial.print("The global count is:");
  Serial.println(sniffed_macs_global_counter);
  Serial.print("The created capacity is:");
  Serial.println(capa);
  Serial.println("=====================================================");

  DynamicJsonDocument doc(capa); //this was the original before trying to fix the output issue

  Serial.println("The documentingJson function");


  doc["time"].set(Tym);
  doc["lat"] = latitude;
  doc["lon"] = longitude;
  JsonArray macs = doc.createNestedArray("macs");

  for (int i = 0; i < sniffed_macs_global_counter; i++)
  {
    JsonObject rooot = macs.createNestedObject();
    rooot["addr"].set(Sniffed_Mac_Addresses[i]);
    rooot["rssi"].set(rssi[i]);
  }

  serializeJson(doc, Serial);
  Serial.println();
  Serial.println("==============================NEW DATA STRUCTURE======================================");

  //show the serialized json string
  //format 2 fails to print when there is too many passengers data
  Serial.println("The following is the serialized output string for POST");
  serializeJson(doc, Internet_String);
  Serial.println(Internet_String);

  //clear document for reuse
  Serial.println("======================domumentingJson Finished==========================");
  doc.clear();

  for (int e = 0; e < 150; e++)
  {
    (Sniffed_Mac_Addresses[e]) = "";
    (rssi[e]) = '\0';
  }

  Serial.println("======================START SD CARD==========================");
  //SaveToSDCard(Internet_String);

  //writeFile(SD, "/ESP_SNIFF.txt", Internet_String);
  appendFile(SD, "/ESP_SNIFF.txt", Internet_String);
  Serial.println("======================END SD CARD==========================");

  capa = 0;
  Internet_String = "";
  Tym = "";
  latitude = 0;
  longitude = 0;

  return;

}

void SaveToSDCard(String Data_To_Save)
{
  Serial.print("Initializing SD card...");

  if (!SD.begin())
  {
    Serial.println("initialization failed. Things to check:");
    Serial.println("1. is a card inserted?");
    Serial.println("2. is your wiring correct?");
    Serial.println("3. did you change the chipSelect pin to match your shield or module?");
    Serial.println("Note: press reset or reopen this Serial Monitor after fixing your issue!");
    while (true)
      ;
  }

  Serial.println("initialization of SD CARD done.");

  File file = SD.open("ESP_SNIFF.txt", FILE_WRITE);
  if (!file) {
    Serial.println("Failed to open file for writing");
    return;
  }
  if (file.println(Data_To_Save)) {
    Serial.println("File written");
  } else {
    Serial.println("Write failed");
  }

  file.close();
}
