#ifndef INTERNET_H
#define INTERNET_H

#include <Arduino.h>
#include <ArduinoJson.h>
#include <GSMSimHTTP.h>
#include "wiring_private.h"
#include <Wire.h>
#include "SDCARD.h"

#define RESET_PIN 20 // you can use any pin.
extern String Json_output_string[];
extern String Sniffed_Mac_Addresses[];
extern String Internet_String;

bool Internet_setup_flag;
String PostResult;                 // this holds the post result data to be decoded
volatile bool SuccessfulPostFlagg; // this will return from the function handling the post reslt
extern int sniffed_macs_global_counter;
extern uint8_t doc_how_many_times_remainder;
extern int doc_how_many_times;
extern unsigned long previousMillis;
extern unsigned long reset_previousMillis;
extern volatile bool done_jsondocumenting_flagg;
bool internet_sending_flagg;
uint8_t counter = 0; //loop through Json_output_string array
extern uint8_t times_we_loaded_output;

//String PostOutputLater[10]; //the buffer stores the entire json string of a failed post. here I am now optimizing for ram
//int later_counter; //the counter to add index to the buffer we use to post later
bool atleast_one_post_failed;
int mini_global_u=0;

Uart gsmSerial(&sercom0, 5, 6, SERCOM_RX_PAD_1, UART_TX_PAD_0);
GSMSimHTTP http(gsmSerial, RESET_PIN); // GSMSimHTTP inherit from GSMSimGPRS. You can use GSMSim and GSMSimGPRS methods with it.
void post_status(String status_message);

void SERCOM0_Handler()
{
    gsmSerial.IrqHandler();
}

void Internet_initialization()
{
    
    Serial.println(F("Setting up gsm to go into gprs mode once and for all:"));

    // Init module...
    delay(5000);
    http.init(); // use for init module. Use it if you dont have any valid reason.

    while (http.setPhoneFunc(1) != true)
    {
        Serial.print(F("Set Phone Function... ")); //might wantto have many trials here
        Serial.println(http.setPhoneFunc(1));
        delay(1000);
    }

    Serial.print(F("is Module Registered to Network?... "));
    Serial.println(http.isRegistered());
    delay(1000);

    Serial.print(F("Signal Quality... "));
    Serial.println(http.signalQuality());
    delay(1000);

    Serial.print(F("Operator Name... "));
    Serial.println(http.operatorNameFromSim());
    delay(1000);

    Serial.print(F("First close GPRS... "));
    Serial.println(http.closeConn());
    delay(1000);

    Serial.print(F("Now connect GPRS."));

    while (!http.isConnected())
    {
        Serial.print(F("."));
        Serial.println(http.connect());
        delay(500);
    }

    Serial.println(F("Internet initialization done!!"));

    Internet_setup_flag = true; // this will let the main loop
    previousMillis = millis();
    reset_previousMillis = millis();
    return;
}

void Send_To_The_Internet()
{
    Serial.print("SD Save... ");
    SaveToSDCard(Internet_String);
    Serial.println();

        Serial.print(F("First close GPRS... "));
    Serial.println(http.closeConn());
    delay(1000);

    Serial.print(F("Now connect GPRS."));

    while (!http.isConnected())
    {
        Serial.print(F("."));
        Serial.println(http.connect());
        delay(500);
    }

    Serial.println(F("GSM has already been initiated continue..."));
    Serial.print(F("Get IP Address... "));
    Serial.println(http.getIP());
    delay(1000);
    
        Serial.println();

        Serial.print("SD Save... ");
        SaveToSDCard(Internet_String);
        Serial.println();

        Serial.println("Post... ");
        Serial.println(Internet_String);

        //Serial.println(http.post("gjm.yjg.mybluehost.me/kptbot.php", output, "application/json"));
        PostResult = http.post("gjm.yjg.mybluehost.me/kptbot.php", Internet_String, "application/json");
        Serial.println(PostResult);
        post_status(PostResult);

        if (!SuccessfulPostFlagg)
        {
            Serial.println("POST FAILED. now saving data for later POST trying...");
            atleast_one_post_failed = true; //maybe test after a time (maybe end of day)if this flag is true and then act
            //PostOutputLater[later_counter]=Json_output_string[0]; //output is a long string with many data
            Serial.println("The following failed while and has been saved for later save/post");
            //Serial.println(PostOutputLater[later_counter]);
        }
        else
        {
            Serial.println("POST was Successful, no need to store");
            Serial.println("======================================================================================");
            Serial.println();
        }
    Internet_String = '\0';

    Serial.print("The internet string has:");
    Serial.println(Internet_String);

    Serial.println("The Internet has finished");
    Serial.println("======================================================================================");
    times_we_loaded_output=0;
    output_string_counter=0;

    doc_how_many_times = 0;
    doc_how_many_times_remainder = 0;
    counter = 0;
    done_jsondocumenting_flagg = false;
    internet_sending_flagg = false;
       
    number_b4_upload = 0;
    sniffed_macs_global_counter = 0;
    output_string_counter = 0;
    mini_global_u=0;
    previousMillis = millis();

}

void post_status(String status_message)
{
    int first_marker = status_message.indexOf('|');
    int second_marker = status_message.lastIndexOf('|');
    String status_code = status_message.substring(first_marker, (second_marker + 1));
    String Datalenghth = status_message.substring(second_marker + 1);
    String SuccessCode = "|HTTPCODE:200|";
    String WrongDataLength = "LENGTH:0";
    bool FlaggCorrectCode = false;
    bool FlaggWrongLength = false;

    Serial.print("This is the extracted status code :: ");
    Serial.println(status_code);
    Serial.print("This is the extracted posted data length :: ");
    Serial.println(Datalenghth);

    //now we check if the status code is right
    if (strcmp((SuccessCode.c_str()), (status_code.c_str())) == 0)
    {
        Serial.println("The HTTP IS GOOD");
        FlaggCorrectCode = true;
    }
    else
    {
        Serial.println("The HTTP IS BAD");
        FlaggCorrectCode = false;
    }

    //now we check if the length is greater than 0
    if (strcmp((WrongDataLength.c_str()), (Datalenghth.c_str())) == 0)
    {
        Serial.println("The LENGTH IS bad");
        FlaggWrongLength = true;
    }
    else
    {
        Serial.println("The LENGTH IS good");
        FlaggWrongLength = false;
    }

    //now we test if a post is successful by testing that the data length is not zero and that the code is 200
    if ((FlaggCorrectCode == false) || (FlaggWrongLength == true))
    {
        Serial.println("POST FAILED. Now we flag this event...");
        SuccessfulPostFlagg = false;
    }
    else if ((FlaggCorrectCode == true) || (FlaggWrongLength == false))
    {
        Serial.println("POST Success!!");
        SuccessfulPostFlagg = true;
    }
}

#endif