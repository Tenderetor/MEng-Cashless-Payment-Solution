#ifndef BLUETOOTHCLASSIC_H
#define BLUETOOTHCLASSIC_H

#include <Arduino.h>
#include <Wire.h>
#include "wiring_private.h"

//hot key cmd k plus f
extern unsigned long currentMillis;
extern unsigned long previousMillis;
extern const unsigned long time_to_sniff_Classic_Energy;
const unsigned long time_to_sniff_Classic_Energy_for_returning = ((time_to_sniff_Classic_Energy) - (1000 * 0.5)); // this will terminate earlier than the time allowing it to run

Uart BTserial(&sercom4, 3, 2, SERCOM_RX_PAD_3, UART_TX_PAD_2);

void SERCOM4_Handler()
{
  BTserial.IrqHandler();
}

volatile bool inq_flag;
volatile bool start_inq_flag;
extern String Sniffed_Mac_Addresses[];
//extern char *Sniffed_Mac_Addresses[];
extern int sniffed_macs_global_counter;
char RxBuffer[50];
char _c_ = ' ';
uint8_t Rx_cntr;
String _mac_;
extern int TotalLowEnergyMACS;
int TotalClassicMacs = 0;
int ok_times = 0;
volatile bool end_classic_scan_flagg;

extern const int size_of_addres;
extern bool internet_sending_flagg;

void hc_init()
{
  end_classic_scan_flagg = false;
  ok_times = 0;
  start_inq_flag = true;
  inq_flag = false;
  BTserial.println("AT+INQM=1,9,4"); //RSSI, Max 10 devices, ~30s
  // put your setup code here, to run once:
  delay(1000);
  return;
}

void hc_sniff()
{
  //if (((currentMillis - previousMillis) < time_to_sniff_Classic_Energy_for_returning) && (!end_classic_scan_flagg)) //i now need all functions to return so this will require time since they all depend on time to finish
  if ((!end_classic_scan_flagg))
    {
      if (start_inq_flag == true)
      {
        end_classic_scan_flagg=false;
        ok_times=0;
        Serial.println("Send Plain AT Command to HC-05"); //debug
        Serial.println(F("========================================"));
        BTserial.println("AT");
        start_inq_flag = false;
      }

      while (BTserial.available())
      {
        _c_ = BTserial.read();
        RxBuffer[Rx_cntr] = _c_;
        Rx_cntr++;
        //Serial.println("Print the bytes received in RXBuffer");
        Serial.print(_c_);

        if (_c_ == '\n')
        {
          Serial.println("REACHED THE END OF THE LINE");
          Serial.println(F("----------------------------------------"));
          //Serial.println("printing RxBuffer contents gives: ");
          //Serial.println(RxBuffer);
          //Serial.println(F("========================================"));

          // if ((inq_flag == true) && (RxBuffer[0] = 'O') && (RxBuffer[1] == 'K'))
          // {
          //   ok_times++;
          //   Serial.println("Received OK after receiving devices");
          //   //Serial.println("Authorise the start inquiry flag for anoter inquire");
          //   //start_inq_flag=true;
          //   Serial.println("Send AT Command to HC-05"); //debug
          //   Serial.println(F("========================================"));
          //   BTserial.println("AT+INQ");
          // }

          if ((RxBuffer[0] = 'E') && (RxBuffer[1] == 'R') && (RxBuffer[2] == 'R') && (RxBuffer[3] == 'O' && (RxBuffer[4] == 'R')))
          {
            Serial.println(F("ERROR Message Received"));
          }

          if ((RxBuffer[0] = 'O') && (RxBuffer[1] == 'K'))
          {
            ok_times++;

            if(ok_times==5)
            {
              internet_sending_flagg=true;
              end_classic_scan_flagg = true;
              _mac_ = '\0';
              //memset(RxBuffer, 0, sizeof(RxBuffer));
              for(int i=0; i<=50; i++)
              {
                RxBuffer[i] = '\0';
              }
              Rx_cntr = 0;
              return;
            }
            else{
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

            //Sniffed_Mac_Addresses[sniffed_macs_global_counter] = (char *)malloc(sizeof(char) * size_of_addres);
            Sniffed_Mac_Addresses[sniffed_macs_global_counter] = _mac_;
            //strcpy(Sniffed_Mac_Addresses[sniffed_macs_global_counter], _mac_.c_str());
            sniffed_macs_global_counter = sniffed_macs_global_counter + 1;
            TotalClassicMacs = TotalClassicMacs + 1;
            _mac_ = '\0';
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
            for(int i=0; i<=50; i++)
            {
              RxBuffer[i] = '\0';
            }
          Rx_cntr = 0;
          //start_inq_flag=true;
        }
      }
    }

  else
  {
    return;
  }
}

void ClassicEnergyCheckForDuplicates()
{
  int i, j, k;
  int old_number_of_macs = sniffed_macs_global_counter;

  //Serial.println("Function: ClassicEnergyCheckForDuplicates");
  /*    Serial.println("The following are the CLASSIC ENERGY sniffed Mac Addresses:");
    Serial.println(F("========================================")); 

    for(int m = TotalLowEnergyMACS; m< sniffed_macs_global_counter; m++)
    {
        Serial.println(Sniffed_Mac_Addresses[m]);
    }
    Serial.println(F("========================================"));
*/
  //lets sort them and remove the duplicates

  for (i = 0; i < sniffed_macs_global_counter; i++)
  {
    for (j = i + 1; j < sniffed_macs_global_counter; j++)
    {
      /* If any duplicate found */
      if ((strcmp(((Sniffed_Mac_Addresses[i].c_str())), ((Sniffed_Mac_Addresses[j].c_str()))) == 0))
      //if ((strcmp(((Sniffed_Mac_Addresses[i])), ((Sniffed_Mac_Addresses[j]))) == 0))
      {
        /* Delete the current duplicate element */
        for (k = j; k < sniffed_macs_global_counter - 1; k++)
        {
          Sniffed_Mac_Addresses[k] = Sniffed_Mac_Addresses[k + 1];
        }
        /* Decrement size after removing duplicate element */
        sniffed_macs_global_counter--;

        /* If shifting of elements occur then don't increment j */
        j--;
      }
    }
  }

  Serial.println("Function: ClassicEnergyCheckForDuplicates");
  Serial.print("Old Unsorted Array had: ");
  Serial.print(old_number_of_macs);
  Serial.println(" Elements");
  Serial.print("New Sorted Array has: ");
  Serial.print(sniffed_macs_global_counter);
  Serial.println(" Elements");
  Serial.println(F("========================================"));

  for (int m = 0; m < sniffed_macs_global_counter; m++)
  {
    Serial.println(Sniffed_Mac_Addresses[m]);
  }
  Serial.println(F("========================================"));
  Serial.println(F("========================================"));
  return;
  //delay(1000);
}

#endif