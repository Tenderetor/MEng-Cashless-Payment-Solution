#include <Arduino.h>
#include <SPI.h>
#include <SD.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include "wiring_private.h"
#include <WiFiNINA.h>
#include <WiFiUdp.h>
#include <NTPClient.h>
#include <TimeLib.h>

#define BTserial Serial1

const char* ssid     = "Naspers";
const char* pass = "googleDevelop";
int status = WL_IDLE_STATUS;             // the Wi-Fi radio's status

unsigned int localPort = 2390;      // local port to listen for UDP packets
IPAddress timeServer(162, 159, 200, 123); // pool.ntp.org NTP server
//const int NTP_PACKET_SIZE = 48; // NTP timestamp is in the first 48 bytes of the message
byte packetBuffer[48]; //buffer to hold incoming and outgoing packets
WiFiUDP Udp; // A UDP instance to let us send and receive packets over UDP

int scanTime = 5; //In seconds
int sniffed_macs_global_counter;
String Sniffed_Mac_Addresses[100];
int rssi[100]; //collect the rssi data on each peripheral
String Internet_String = "";
String incoming = "";
volatile bool finshed_scan;

float latitude; //code wasused to get time and location from gps
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

volatile bool end_classic_scan_flagg;
const int chipSelect = 10;
// The TinyGPSPlus object

void current_time();
void displayInfo();
void AlldomumentingJson();
void Scan_classic_BT();
void SaveToSDCard(String Data_To_Save);
void CheckForDuplicates();
unsigned long sendNTPpacket(IPAddress& address);
void timefromNTP();

void setup()
{
 
  Serial.begin(115200);
  BTserial.begin(38400);
  
  if (!SD.begin()) {
    Serial.println("Card Mount Failed");
    return;
  }

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
   
  sniffed_macs_global_counter = 0;
  Serial.println("Scanning...");
  finshed_scan = false;
  start_inq_flag = true;
  scan_low_energy = true;
  Begin_sniff_flagg = false;

  end_classic_scan_flagg = false;

}

void loop()
{
  currentMillis = millis();

  if ((currentMillis - previousMillis) <= time_to_rx_gps)
  {
    timefromNTP();
  }
  else {
    //Serial.println("Begin scan");
    Begin_sniff_flagg = true;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////////

  if (Begin_sniff_flagg == true)
  {
    //put your main code here, to run repeatedly:
    if (scan_low_energy == true)
    {
      end_classic_scan_flagg = false;
      //we used to scan BLE here when the system software was scanning both types of Bluetooth
      scan_low_energy = false;
    }

    //now we scan classic
    if (end_classic_scan_flagg == false)
    {
      Scan_classic_BT();
    }

    if (finshed_scan == true)
    {
      Serial.println("Now scan for CLASSIC duplicates");
      CheckForDuplicates();
      Serial.println("===========================================================================");
      Serial.println("===========================================================================");

      //displayInfo();
      AlldomumentingJson();
      //delay(500);
      sniffed_macs_global_counter = 0;
      end_classic_scan_flagg = false;
      finshed_scan = false;
      start_inq_flag = true;
      Begin_sniff_flagg = false;
      TotalClassicMacs = 0;
      previousMillis = millis();
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
void CheckForDuplicates()
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
  SaveToSDCard(Internet_String);

  //writeFile(SD, "/ESP_SNIFF.txt", Internet_String);
  Serial.println("======================END SD CARD==========================");

  capa = 0;
  Internet_String = "";
  Tym = "";
  latitude = 0;
  longitude = 0;

  return;

}

void Scan_classic_BT()
{

  if ((!end_classic_scan_flagg))
  {
    if (start_inq_flag == true)
    {
      end_classic_scan_flagg = false;
      ok_times = 0;
      Serial.println("Send Plain AT Command to HC-05"); //debug
      Serial.println(F("========================================"));
      BTserial.println("AT+INQM=1,9,6");
      start_inq_flag = false;
    }

    while (BTserial.available())
    {
      _c_ = BTserial.read();
      RxBuffer[Rx_cntr] = _c_;
      HC_incoming_String += _c_;
      Rx_cntr++;
      //Serial.println("Print the bytes received in RXBuffer");
      Serial.print(_c_);

      if (_c_ == '\n')
      {
        Serial.println("REACHED THE END OF THE LINE");
        Serial.println(F("----------------------------------------"));

        if ((RxBuffer[0] = 'E') && (RxBuffer[1] == 'R') && (RxBuffer[2] == 'R') && (RxBuffer[3] == 'O' && (RxBuffer[4] == 'R')))
        {
          Serial.println(F("ERROR Message Received"));
          //BTserial.println("AT+INQ");
        }

        if ((RxBuffer[0] = 'O') && (RxBuffer[1] == 'K'))
        {
          ok_times++;

          if (ok_times == 4)
          {
            scan_low_energy = true;
            finshed_scan = true;
            internet_sending_flagg = true;
            end_classic_scan_flagg = true;
            _mac_ = '\0';
            HC_incoming_String = "";
            //memset(RxBuffer, 0, sizeof(RxBuffer));
            for (int i = 0; i < 50; i++)
            {
              RxBuffer[i] = '\0';
            }
            Rx_cntr = 0;
            return;
          }
          else {
            Serial.println("we have received ok for controlled AT CMD");
            Serial.println("Send AT Command to HC-05"); //debug
            Serial.println(F("========================================"));
            BTserial.println("AT+INQ");
          }
        }

        if ((RxBuffer[0] = '+') && (RxBuffer[1] == 'I') && (RxBuffer[2] == 'N') && (RxBuffer[3] == 'Q'))
        {
          //extract _mac_ address
          Serial.println(F("Discovered a Classic Energy Peripheral"));
          Serial.println(F("----------------------------------------"));
          _mac_ += RxBuffer[5];
          _mac_ += RxBuffer[6];
          _mac_ += ":";
          _mac_ += RxBuffer[7];
          _mac_ += RxBuffer[8];
          _mac_ += RxBuffer[9];
          _mac_ += RxBuffer[10];
          _mac_ += RxBuffer[11];
          _mac_ += RxBuffer[12];
          _mac_ += RxBuffer[13];
          _mac_ += RxBuffer[14];
          _mac_ += ":";
          _mac_ += RxBuffer[15];
          _mac_ += RxBuffer[16];
          _mac_ += ":";
          _mac_ += RxBuffer[17];
          _mac_ += RxBuffer[18];
          //Serial.println("The mac extracted is ");
          Serial.println(_mac_);

          first_comma = HC_incoming_String.indexOf(',');
          second_comma = HC_incoming_String.indexOf(',', first_comma + 1);

          cbt_strength[0] = RxBuffer[second_comma + 1];
          cbt_strength[1] = RxBuffer[second_comma + 2];
          cbt_strength[2] = RxBuffer[second_comma + 3];
          cbt_strength[3] = RxBuffer[second_comma + 4];

          first_comma = 0;
          second_comma = 0;

          int16_t signed_val;
          signed_val = strtoul(cbt_strength, NULL, 16 );
          Serial.print("The CBT RSSI is: ");
          Serial.println(signed_val);//,DEC);

          //Sniffed_Mac_Addresses[sniffed_macs_global_counter] = (char *)malloc(sizeof(char) * size_of_addres);
          Sniffed_Mac_Addresses[sniffed_macs_global_counter] = _mac_;
          rssi[sniffed_macs_global_counter] = signed_val;
          signed_val = 0;
          //strcpy(Sniffed_Mac_Addresses[sniffed_macs_global_counter], _mac_.c_str());
          sniffed_macs_global_counter = sniffed_macs_global_counter + 1;
          TotalClassicMacs = TotalClassicMacs + 1;
          _mac_ = '\0';
          for (byte i_ = 0; i_ < 5; i_++)
          {
            cbt_strength[0] = '\0';
          }
          Serial.println("Now a Sniffed and Stored Periphery!!");
          Serial.print(TotalClassicMacs);
          Serial.println(" Classic energy bluetooth devices");
          Serial.print(sniffed_macs_global_counter);
          Serial.println(" Total devices");
          Serial.println(F("========================================"));
        }

        _mac_ = '\0';
        //memset(_mac_,0,strlen(_mac_));
        //memset(RxBuffer, 0, sizeof(RxBuffer));
        for (int i = 0; i < 50; i++)
        {
          RxBuffer[i] = '\0';
        }
        Rx_cntr = 0;
        //start_inq_flag=true;
      }
    }
  }

}

void SaveToSDCard(String Data_To_Save)
{
    Serial.print("Initializing SD card...");

    if (!SD.begin(chipSelect))
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
    
    int number_of_trips = 2;
    char filename[sizeof "trip100.txt"];
    sprintf(filename,"trip%03d.txt", number_of_trips);

    File dataFile = SD.open(filename, FILE_WRITE);

    //if the file is available, write to it:

    if (dataFile)
    {
        dataFile.println(Data_To_Save);
        dataFile.close();
        //print to the serial port too:
        Serial.println("The SD saved data is as follows");
        //Serial.println(PostOutputLater[i_]);
    }
    // if the file isn't open, pop up an error:
    else
    {
        Serial.println("error opening file");
    }
       
}
