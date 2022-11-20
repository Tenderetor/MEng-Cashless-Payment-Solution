#ifndef LOCATIONANDTIME_H
#define LOCATIONANDTIME_H

#include <string.h>
#include "TinyGPSplus.h"

TinyGPSPlus gps;//This is the GPS object that will pretty much do all the grunt work with the NMEA data
String Tym;
extern unsigned long previousMillis;

// This custom version of delay() ensures that the gps object
// is being "fed".
static void smartDelay(unsigned long ms)
{
  unsigned long start = millis();
  do 
  {
    while (Serial1.available())
      gps.encode(Serial1.read());
  } while (millis() - start < ms);
}

void gps_time()
{
  String _tym_;

  while ((Serial1.available() > 0) && (Serial1.read() != '\r')) 
  {
    smartDelay(1000);
  }

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
    _tym_ += "0";}
    _tym_ += gps.time.hour();
    _tym_ += ":";
    if (gps.time.minute() < 10) {
    _tym_ += "0";}
    _tym_ += gps.time.minute();
    _tym_ += ":";
    if (gps.time.second() < 10) {
    _tym_ += "0";}
    _tym_ += gps.time.second();
  }
  else
  {
    _tym_ = "TIME INVALID";
  }
 
 // Serial.print("Inside gps functon the returned tym ->");
  //Serial.println(_tym_);
  Tym =_tym_;
  _tym_ ='\0';
  //Serial.println();    

 return;
}

String printDateTime(TinyGPSDate &d, TinyGPSTime &t)
{
  String tyming;

  if (!d.isValid())
  {
    //Serial.print(F("********** "));
    tyming += "DATE INVALID";
  }
  else
  {
    char sz[32];
    sprintf(sz, "%02d/%02d/%02d ", d.month(), d.day(), d.year());
    //Serial.print(sz);
    tyming += sz;
  }
  
  if (!t.isValid())
  {
    //Serial.print(F("******** "));
    tyming += "TIME INVALID";
  }
  else
  {
    char sz[32];
    sprintf(sz, "%02d:%02d:%02d ", t.hour(), t.minute(), t.second());
    //Serial.print(sz);
    tyming += sz;
  } 

  smartDelay(0);

  return tyming;
}

#endif