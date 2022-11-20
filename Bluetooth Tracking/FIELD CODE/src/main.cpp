#include <Arduino.h>
#include "LowEnergyBluetooth.h"
#include "LocationAndTime.h"
#include "ClassicEnergyBluetooth.h"
#include "JSONING.h"
#include "Internet.h"
#include <ArduinoJson.h>

volatile bool gps_function_on_start_flagg;
extern volatile bool low_energy_initialize_flag;
int sniffed_macs_global_counter;

String Sniffed_Mac_Addresses[150];

unsigned long currentMillis;
unsigned long previousMillis;

unsigned long current_gpsMillis;
unsigned long prev_gpsMillis;
const unsigned long time_to_rx_gpsMillis=1000*5;

const unsigned long time_to_sniff_low_Energy = 1000 * 5;
const unsigned long time_to_sniff_Classic_Energy = ((time_to_sniff_low_Energy) + (1000 * 21));
const unsigned long time_to_reset = 1000 * 60 * 20; //After 10 minutes I want to reset everything
unsigned long reset_previousMillis;

volatile bool lowenergy_duplicate_printed_flagg;
volatile bool classic_energy_duplicate_printed_flag;
extern int TotalClassicMacs;
extern int TotalLowEnergyMACS;

extern String Tym;

extern bool Internet_setup_flag;
extern bool internet_sending_flagg;

volatile bool done_jsondocumenting_flagg;
volatile bool check_location_change_flagg;
extern volatile bool low_energy_initialize_flag;
extern volatile bool ble_end_flag;
extern bool first_time_posting_flagg;

uint8_t number_b4_upload = 0;
int number_of_trips; //this increases everytime I press the button
extern volatile bool start_inq_flag;

bool allocate_flagg;

extern volatile bool scan_is_on;

extern volatile bool end_classic_scan_flagg;

void free_ram();
void receive_gps();

String incomingstring="";

void setup()
{
  pinPeripheral(2, PIO_SERCOM_ALT);
  pinPeripheral(3, PIO_SERCOM_ALT);

  pinPeripheral(5, PIO_SERCOM_ALT); //gsm sim 800l
  pinPeripheral(6, PIO_SERCOM_ALT); //gsm sim 800l

  Serial.begin(9600);
  Serial1.begin(9600);
  BTserial.begin(38400);
  gsmSerial.begin(9600);

  gps_function_on_start_flagg = true;
  low_energy_initialize_flag = true;

  lowenergy_duplicate_printed_flagg = false;
  classic_energy_duplicate_printed_flag = false;
  done_jsondocumenting_flagg = false;
  Internet_setup_flag = false;
  check_location_change_flagg = false;
  internet_sending_flagg = false;
  low_energy_initialize_flag = true;
  ble_end_flag = true;
  first_time_posting_flagg = true;
  allocate_flagg = true;
  scan_is_on=false;

  sniffed_macs_global_counter = 0;
  number_of_trips = 0;

  hc_init();
  Internet_initialization();
  Internet_setup_flag=true;
}

void loop()
{
  if (Internet_setup_flag)
  {
    currentMillis = millis();

    //To do: Sniff Low Energy Bluetooth for about 10 seconds
    if (((currentMillis - previousMillis) < time_to_sniff_low_Energy))
    {
      Sniff_Low_Energy_bluetooth();
    }

    //To do: Sniff Classic Energy Bluetooth
    if (((currentMillis - previousMillis) >= time_to_sniff_low_Energy) && (!end_classic_scan_flagg))
    {
      //end_classic_scan_flagg=true; //since we are not scanning classic BT

      if (lowenergy_duplicate_printed_flagg == false)
      {
        lowenergy_duplicate_printed_flagg = true;
        LowEnergyCheckForDuplicates();
      }

      if (ble_end_flag)
      {
        Serial.println(F("ending bluetooth scanning first"));
        ble_end_flag = false;
        scan_is_on=false;
        free_ram();      
      }

      hc_sniff(); 
      internet_sending_flagg = true;
    }

    //Check for duplicates
    ClassicEnergyCheckForDuplicates();

    //To do: Send to database
    if((end_classic_scan_flagg==true)&&(internet_sending_flagg == true))
    {
      end_classic_scan_flagg=false;
      if (classic_energy_duplicate_printed_flag == false)
      {
        BLE.end();
        classic_energy_duplicate_printed_flag = true;
        ClassicEnergyCheckForDuplicates();
        prev_gpsMillis = millis();
      }

      free_ram();

      if (done_jsondocumenting_flagg == false)
      {

        current_gpsMillis = millis();
        if (current_gpsMillis - prev_gpsMillis < time_to_rx_gpsMillis)
        {
          gps_time();
        }

        else{
          Serial.print("The number of new passengers is ");
          Serial.println(sniffed_macs_global_counter - number_b4_upload);
          Serial.println();
          //if new pass is more than zero we go else we restart sniff
          if ((sniffed_macs_global_counter - number_b4_upload) > 0) //this condition wil always be true from now on
          {
            AlldomumentingJson();
            Send_To_The_Internet();          
          }

          else
          {
            internet_sending_flagg = false;
            Serial.println("No new passengers to upload");
            Serial.println("======================================================================================");

            //memset(Json_output_string, 0, sizeof(Json_output_string));
            doc_how_many_times = 0;
            doc_how_many_times_remainder = 0;
            counter = 0;
            done_jsondocumenting_flagg = false;
            internet_sending_flagg = false;
            start_inq_flag = true;

            //the following resets will remove continuity suc as finding the nw passengers etc
            //memset(Sniffed_Mac_Addresses, 0, sizeof(Sniffed_Mac_Addresses));   
            //for (int e = 0; e < 300; e++)
            //{
              //(Sniffed_Mac_Addresses[e]) = '\0';
            //}       
            //allocate_flagg = true;
            number_b4_upload = 0;
            sniffed_macs_global_counter = 0;
            output_string_counter = 0;
            
            previousMillis = millis();
          }
        }
        
      }

      number_b4_upload = sniffed_macs_global_counter;
      TotalClassicMacs = 0;
      //memset(Json_output_string, 0, sizeof(Json_output_string));
      gps_function_on_start_flagg = true;
      classic_energy_duplicate_printed_flag = false;
      lowenergy_duplicate_printed_flagg = false;
      TotalLowEnergyMACS = 0;
      ble_end_flag = true;
      low_energy_initialize_flag = true;
      start_inq_flag = true;

      number_b4_upload = 0;
      sniffed_macs_global_counter = 0;
      output_string_counter = 0; 
      //memset(Sniffed_Mac_Addresses, 0, sizeof(Sniffed_Mac_Addresses));  
      for (int e = 0; e < 300; e++)
      {
        (Sniffed_Mac_Addresses[e]) = '\0';
      }

      previousMillis = millis();
    }
  }
  
}

void receive_gps()
{
  while(Serial1.available()>0)
  {
    char b =Serial1.read();
    incomingstring+=b;
  }
  Serial.println(incomingstring);
}

void free_ram()
{
  //Serial.println("CHECKING RESIDUAL NONSENSE FOR RAM RELEASE");
  Serial.println("==========================RELEASE==============RAM====================================");
  //Serial.println("======================================================================================");
  //for(int d=0; d<400; d++)
  //{
  //Serial.println(Sniffed_Mac_Addresses[d]);
  //}
  //Serial.println("=======================================That's done====================================");
  //Serial.println("======================================================================================");

  //Serial.println("HAVE WE CLEARED ?");
  //Serial.println("======================================================================================");
  //Serial.println("======================================================================================");
  for (int e = sniffed_macs_global_counter; e < 150; e++)
  {
    (Sniffed_Mac_Addresses[e]) = '\0';
  }

  //for(int f=0; f<400; f++)
  //{
  //Serial.println(Sniffed_Mac_Addresses[f]);
  //}
  Serial.println("==================================DONE================================================");
  Serial.println("======================================================================================");
  return;
}
