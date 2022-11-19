#include <ArduinoBLE.h>
#include <SPI.h>
#include <SD.h>
#include <ArduinoJson.h>
#include <WiFiNINA.h>
#include <WiFiUdp.h>
#include <NTPClient.h>
#include <TimeLib.h>

const char* ssid     = "Naspers";
const char* pass = "googleDevelop";
int status = WL_IDLE_STATUS;             // the Wi-Fi radio's status

unsigned int localPort = 2390;      // local port to listen for UDP packets
IPAddress timeServer(162, 159, 200, 123); // pool.ntp.org NTP server
//const int NTP_PACKET_SIZE = 48; // NTP timestamp is in the first 48 bytes of the message
byte packetBuffer[48]; //buffer to hold incoming and outgoing packets
WiFiUDP Udp; // A UDP instance to let us send and receive packets over UDP

//int scanTime = 30; //In seconds
//BLEScan* pBLEScan;
int sniffed_macs_global_counter;
String Sniffed_Mac_Addresses[150];
int rssi[150]; //collect the rssi data on each peripheral
String Internet_String="";
String incoming="";
String Tym;
String formattedTime="";

volatile bool low_energy_initialize_flag;

unsigned long currentMillis;
unsigned long previousMillis;
unsigned long  scanTime = 1000*30;

//bool wifiModeFlag = false;
bool recorded;
bool first_flag;

BLEDevice peripheral;
void LowEnergyCheckForDuplicates();
void AlldomumentingJson();
void Sniff_BLE();
void SaveToSDCard(String Data_To_Save);
unsigned long sendNTPpacket(IPAddress& address);
void timefromNTP();

void setup()
{
  Serial.begin(115200);
  sniffed_macs_global_counter = 0;

   // attempt to connect to Wi-Fi network:
  while (status != WL_CONNECTED) {
    Serial.print("Attempting to connect to network: ");
    Serial.println(ssid);
    // Connect to WPA/WPA2 network:
    status = WiFi.begin(ssid, pass);

    // wait 10 seconds for connection:
    delay(10000);
  }

  
  // you're connected now, so print out the data:
  Serial.println("You're connected to the network");
  Serial.println("---------------------------------------");

  Serial.println("\nStarting connection to server...");
  Udp.begin(localPort);
  
  recorded = false;
  low_energy_initialize_flag = true;
  first_flag=true;

}

void loop()
{
  currentMillis = millis();

  //Serial.println(timeClient.getFormattedTime());

  if(currentMillis - previousMillis < scanTime)
  {
    recorded=false;
    //we scan BLE
    //Serial.print("sniff");
    Sniff_BLE();
  }

  else if((currentMillis - previousMillis > scanTime) && !recorded){
    Serial.println("ending ble.");
    BLE.stopScan();
    BLE.end();
    LowEnergyCheckForDuplicates();
    timefromNTP();
    AlldomumentingJson();
    sniffed_macs_global_counter=0;
    recorded=true;
    first_flag=true;
    low_energy_initialize_flag = true;
    Serial.println("restart assigning.");
    previousMillis=millis();
  }
 
}

void Sniff_BLE()
{
  if(first_flag)
  {
    first_flag=false;
    Serial.println("Not yet faild");
  }

  if(low_energy_initialize_flag)
  {
    low_energy_initialize_flag=false;
    Serial.println("START BLE!");
    //BLE.begin();
    if(!BLE.begin())
    {
      Serial.println("BLE start failed!!");
      while (1);
    }

    //Serial.println("BLE Central Scan");
    BLE.scan();

  }

  peripheral = BLE.available();

  if(sniffed_macs_global_counter<150)
  {
    if ((peripheral)) {
        //Serial.println(F("Discovered a peripheral"));
        //Serial.println(F("----------------------------------------"));

        //Serial.print(F("Address: "));
        //Serial.println(peripheral.address());
        Sniffed_Mac_Addresses[sniffed_macs_global_counter] = peripheral.address();
        rssi[sniffed_macs_global_counter] = peripheral.rssi();

        // if (peripheral.hasLocalName()) {
        //   //Serial.print(F("Local Name: "));
        //   //Serial.println(peripheral.localName());
        //   //continue;
        // }

        sniffed_macs_global_counter = sniffed_macs_global_counter + 1;
        //Serial.print(sniffed_macs_global_counter);
        //Serial.println(" Devices from low energy bluetooth");
        //Serial.println();
        //Serial.println(F("========================================"));
    }
  }

}

void timefromNTP()
{

  sendNTPpacket(timeServer); // send an NTP packet to a time server
  // wait to see if a reply is available
  delay(1000);
  if (Udp.parsePacket()) {
    Serial.println("packet received");
    // We've received a packet, read the data from it
    Udp.read(packetBuffer, NTP_PACKET_SIZE); // read the packet into the buffer

    //the timestamp starts at byte 40 of the received packet and is four bytes,
    // or two words, long. First, extract the two words:

    unsigned long highWord = word(packetBuffer[40], packetBuffer[41]);
    unsigned long lowWord = word(packetBuffer[42], packetBuffer[43]);
    // combine the four bytes (two words) into a long integer
    // this is NTP time (seconds since Jan 1 1900):
    unsigned long secsSince1900 = highWord << 16 | lowWord;
    Serial.print("Seconds since Jan 1 1900 = ");
    Serial.println(secsSince1900);

    // now convert NTP time into everyday time:
    Serial.print("Unix time = ");
    // Unix time starts on Jan 1 1970. In seconds, that's 2208988800:
    const unsigned long seventyYears = 2208988800UL;
    // subtract seventy years:
    unsigned long epoch = secsSince1900 - seventyYears;
    // print Unix time:
    Serial.println(epoch);


    char buff[32];
    sprintf(buff, "%02d/%02d/%02d %02d:%02d:%02d", day(epoch), month(epoch), year(epoch), hour(epoch), minute(epoch), second(epoch));
    Serial.println(buff);
    //memcpy(Tym, buff, 32); //shift buffer contents to string 
    Tym = String(buff);
    memset(buff, '\0', 32); //clear buffer
    
  }  
}

// send an NTP request to the time server at the given address
unsigned long sendNTPpacket(IPAddress& address) {
  //Serial.println("1");
  // set all bytes in the buffer to 0
  memset(packetBuffer, 0, NTP_PACKET_SIZE);
  // Initialize values needed to form NTP request
  // (see URL above for details on the packets)
  //Serial.println("2");
  packetBuffer[0] = 0b11100011;   // LI, Version, Mode
  packetBuffer[1] = 0;     // Stratum, or type of clock
  packetBuffer[2] = 6;     // Polling Interval
  packetBuffer[3] = 0xEC;  // Peer Clock Precision
  // 8 bytes of zero for Root Delay & Root Dispersion
  packetBuffer[12]  = 49;
  packetBuffer[13]  = 0x4E;
  packetBuffer[14]  = 49;
  packetBuffer[15]  = 52;

  //Serial.println("3");

  // all NTP fields have been given values, now
  // you can send a packet requesting a timestamp:
  Udp.beginPacket(address, 123); //NTP requests are to port 123
  //Serial.println("4");
  Udp.write(packetBuffer, NTP_PACKET_SIZE);
  //Serial.println("5");
  Udp.endPacket();
  //Serial.println("6");
}

void LowEnergyCheckForDuplicates()
{
    int i, j, k;
    //int old_number_of_macs = sniffed_macs_global_counter;

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

    for (int e = 0; e < 150; e++)
    {
      (Sniffed_Mac_Addresses[e]) = '\0';
      (rssi[e]) = '\0';
    }        
    //appendFile(SD, "ESP_BLE_SNIFF.txt", Internet_String);
    SaveToSDCard(Internet_String);
    capa=0;
    Internet_String="";
    Tym="";
    Serial.println("======================domumentingJson Finished==========================");

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
