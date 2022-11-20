#ifndef SDCARD_H
#define SDCARD_H

#include <Arduino.h>
#include <SPI.h>
#include <SD.h>

const int chipSelect = 10;
extern int number_of_trips; //this increases everytime I press the button

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

    number_of_trips = 1;
    char filename[sizeof "trip100.txt"];
    sprintf(filename,"trip%03d.txt", number_of_trips);

    File dataFile = SD.open(filename, FILE_WRITE);

    //if the file is available, write to it:

    if (dataFile)
    {
        dataFile.println(Data_To_Save);
        dataFile.close();
        //print to the serial port too:
        Serial.println("The SD saved data");
        //Serial.println(PostOutputLater[i_]);
    }
    // if the file isn't open, pop up an error:
    else
    {
        Serial.println("error opening file");
    }
       
}

#endif