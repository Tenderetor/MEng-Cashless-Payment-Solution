#include "FS.h"
#include <SPI.h>
#include <SD.h>
#include <ArduinoJson.h>
//#include <HardwareSerial.h>
#include <BluetoothSerial.h>
#include <WiFi.h>
#include "time.h"
#include <BLEDevice.h>
#include <BLEUtils.h>
#include <BLEScan.h>
#include <BLEAdvertisedDevice.h>

int scanTime = 30; //In seconds
BLEScan* pBLEScan;
int sniffed_macs_global_counter;
String Sniffed_Mac_Addresses[100];
int rssi[100]; //collect the rssi data on each peripheral
String Internet_String="";
String incoming="";
String Tym;

const char* ssid     = "Naspers";
const char* password = "googleDevelop";

const char* ntpServer = "pool.ntp.org";
const long  gmtOffset_sec = 0;
const int   daylightOffset_sec = 7200;

class MyAdvertisedDeviceCallbacks: public BLEAdvertisedDeviceCallbacks {
    void onResult(BLEAdvertisedDevice advertisedDevice) {
        if(sniffed_macs_global_counter<150)
        {
          //Serial.printf("Advertised Device: %s \n", advertisedDevice.toString().c_str());
          Sniffed_Mac_Addresses[sniffed_macs_global_counter]=advertisedDevice.getAddress().toString().c_str();
          //Serial.print(" RSSI: ");
          //Serial.println(advertisedDevice.getRSSI());
          rssi[sniffed_macs_global_counter] = advertisedDevice.getRSSI();
          sniffed_macs_global_counter++;
        }
    }
};

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

void SaveToSDCard(String Data_To_Save)
{
  Serial.print("Initializing SD card...");

  Serial.println("initialization of SD CARD done.");

  File file = SD.open("ESP_BLE_SNIFF.txt", FILE_WRITE);
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
  sniffed_macs_global_counter = 0;
  if (!SD.begin()) {
    Serial.println("Card Mount Failed");
    return;
  }
  BLEDevice::init("");
  pBLEScan = BLEDevice::getScan(); //create new scan
  pBLEScan->setAdvertisedDeviceCallbacks(new MyAdvertisedDeviceCallbacks());
  pBLEScan->setActiveScan(true); //active scan uses more power, but get results faster
  pBLEScan->setInterval(100);
  pBLEScan->setWindow(99);  // less or equal setInterval value

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
  //put your main code here, to run repeatedly:
  BLEScanResults foundDevices = pBLEScan->start(scanTime, true);
  Serial.print("Devices found: ");
  //Serial.println(foundDevices.getCount());
  Serial.println(sniffed_macs_global_counter);
  Serial.println("Scan done!");
  pBLEScan->clearResults();   // delete results fromBLEScan buffer to release memory
  Serial.println("===========================================================================");
  //Serial.println("Now scan for duplicates");
  LowEnergyCheckForDuplicates();
  //Serial.println("===========================================================================");
  //Serial.println("===========================================================================");
  printLocalTime();
  AlldomumentingJson();
  sniffed_macs_global_counter=0;
 
}

void LowEnergyCheckForDuplicates()
{
    int i, j, k;
    int old_number_of_macs = sniffed_macs_global_counter;

    //lets sort them and remove the duplicates

    for( i=0; i<sniffed_macs_global_counter; i++)
    {
        for( j=i+1; j<sniffed_macs_global_counter; j++)
        {
            /* If any duplicate found */
            if ((strcmp(((Sniffed_Mac_Addresses[i].c_str())), ((Sniffed_Mac_Addresses[j].c_str()))) == 0))
            {
                /* Delete the current duplicate element */
                for(k=j; k<sniffed_macs_global_counter-1; k++)
                {
                    Sniffed_Mac_Addresses[k] = Sniffed_Mac_Addresses[k+1];
                    rssi[k]= rssi[k+1];
                }
                /* Decrement size after removing duplicate element */
                sniffed_macs_global_counter--;

                /* If shifting of elements occur then don't increment j */
                j--;
            }
        }
    }

    //Serial.println("Function: LowEnergyCheckForDuplicates");
    //Serial.print("Old Unsorted Array had: "); Serial.print(old_number_of_macs); Serial.println(" Elements");
    //Serial.print("New Sorted Array has: "); Serial.print(sniffed_macs_global_counter); Serial.println(" Elements");
    //Serial.println(F("========================================"));
    
//    for(int m = 0; m< sniffed_macs_global_counter; m++)
//    {
//        Serial.print(Sniffed_Mac_Addresses[m]);
//        Serial.print(" RSSI=");
//        Serial.println(rssi[m]);
//    }
//    Serial.println(F("========================================"));
//    Serial.println(F("========================================"));
    //delay(100);
    return;
}

void AlldomumentingJson()
{
  //Serial.println("======================START==========================");
  //Serial.println("=====================================================");
  //Serial.print("ALL DOCUMENTING FUNCTION");
 
  //calculate the capacity of json document
  uint16_t capa = 128*sniffed_macs_global_counter;
  if(capa<=1024)
  {
    capa=1024;
  }
  Serial.print("The global count is:");
  Serial.println(sniffed_macs_global_counter);
  //Serial.print("The created capacity is:");
  //Serial.println(capa);
  //Serial.println("=====================================================");
 
  DynamicJsonDocument doc(capa); //this was the original before trying to fix the output issue

  //Serial.println("The documentingJson function");

  //String Tym = "zero";

    doc["time"].set(Tym);
    doc["lat"] = 0;
    doc["lon"] = 0;
    JsonArray macs = doc.createNestedArray("macs");

    for(int i =0; i<sniffed_macs_global_counter; i++)
    {
      JsonObject rooot = macs.createNestedObject();
      rooot["addr"].set(Sniffed_Mac_Addresses[i]);
      rooot["rssi"].set(rssi[i]);
    }

    //serializeJson(doc, Serial);
    //Serial.println();
    //Serial.println("==============================NEW DATA STRUCTURE======================================");

    //show the serialized json string
    //format 2 fails to print when there is too many passengers data
    //Serial.println("The following is the serialized output string for POST");
    serializeJson(doc, Internet_String);
    Serial.println(Internet_String);
   
    //clear document for reuse
    //Serial.println("======================domumentingJson Finished==========================");
    doc.clear();

    for (int e = 0; e < 100; e++)
    {
      (Sniffed_Mac_Addresses[e]) = "";
      (rssi[e]) = '\0';
    }        
    appendFile(SD, "ESP_BLE_SNIFF.txt", Internet_String);
    //SaveToSDCard(Internet_String);
    capa=0;
    Internet_String="";
    Tym="";
   
    return;
 
}
