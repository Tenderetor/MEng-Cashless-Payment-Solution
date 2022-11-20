#ifndef JSONING_H
#define JSONING_H

#include <Arduino.h>
#include <ArduinoJson.h>

extern int sniffed_macs_global_counter;
extern String Sniffed_Mac_Addresses[];
//extern char * Sniffed_Mac_Addresses[];

int doc_how_many_times; //how many times I have to loop through the global_variable counter for serialise to not fail
uint8_t doc_how_many_times_remainder;
String Json_output_string[20];
String Internet_String;
int output_string_counter = 0;

extern String Tym;

extern volatile bool done_jsondocumenting_flagg;
bool first_time_posting_flagg;

extern uint8_t number_b4_upload;
uint8_t times_we_loaded_output=0;

extern int rssi[]; //collect the rssi data on each peripheral

void AlldomumentingJson()
{
  Serial.println("======================START==========================");
  Serial.println("=====================================================");
  Serial.print("ALL DOCUMENTING FUNCTION");
  //calculate the capacity of json document
  //uint16_t capa = JSON_ARRAY_SIZE(sniffed_macs_global_counter) * JSON_OBJECT_SIZE(8);
  uint16_t capa = 128*sniffed_macs_global_counter;
  Serial.print("The global count is:");
  Serial.println(sniffed_macs_global_counter);
  Serial.print("The created capacity is:");
  Serial.println(capa);
  Serial.println("=====================================================");
  
  DynamicJsonDocument doc(capa); //this was the original before trying to fix the output issue

  //doc.clear();
  Serial.println("The documentingJson function");


    doc["time"].set(Tym);
    JsonArray macs = doc.createNestedArray("macs");

    for(int i =0; i<sniffed_macs_global_counter; i++)
    {
      JsonObject rooot = macs.createNestedObject();
      rooot["addr"].set(Sniffed_Mac_Addresses[i]);
    }

    serializeJson(doc, Serial);
    Serial.println();
    Serial.println("==============================NEW DATA STRUCTURE======================================");

    //show the serialized json string in two formats
    Serial.println(F("Print serialized data to serial monitor... "));

    Serial.println("The following is the serialized output string for POST");
    serializeJson(doc, Internet_String);
    Serial.println(Internet_String);
    
    //clear document for reuse
    Serial.println("======================domumentingJson Finished==========================");
    doc.clear();

    Tym='\0';
    for (int e = 0; e < 150; e++)
    {
      (Sniffed_Mac_Addresses[e]) = '\0';
    }        

    capa=0;
    done_jsondocumenting_flagg = true;
    first_time_posting_flagg = false;

    return;
  
}

/*
void domumentingJson(uint8_t loop_start, uint8_t loop_end)
{
  uint16_t capa = 128*20;
  DynamicJsonDocument doc(capa); 
  doc.clear();
  Serial.println("The documentingJson function");
  Serial.print("The capacity created is: ");
  Serial.println(capa);
  uint8_t length_ = loop_end - loop_start;

  Serial.print("loop_end - loop_start = ");
  Serial.println(length_);

  if (length_ >= 20)
  {
    for (int k = loop_start; k < (loop_end); k++)
    //for(int k = loop_start; k < (loop_start+5); k++)
    {
      JsonObject root = doc.createNestedObject();
      //Serial.print("The current address: ");
      //Serial.println(Sniffed_Mac_Addresses[k]);

      root["addr"] = Sniffed_Mac_Addresses[k]; //(periphery_address[k]);
      root["lat"] = latitude;
      root["lon"] = longitude;
      root["time"] = Tym;

      //Serial.println("After setting root data in document");
    }
    //show the serialized json string in two formats
    Serial.println(F("Print serialized data to serial monitor... "));

    //format 1
    serializeJsonPretty(doc, Serial); //output was once serial
    Serial.println();
    //serializeJsonPretty(doc, Json_output_string); //output was once serial

    //format 2 fails to print when there is too many passengers data
    Serial.println("The following is the serialized output string for POST");
    serializeJson(doc, Json_output_string[output_string_counter]);
    Serial.println(Json_output_string[output_string_counter]);
    times_we_loaded_output=times_we_loaded_output+1;
    //serializeJson(doc, Json_output_string);
    //Serial.println(Json_output_string[output_string_counter]);
    Serial.println();
    Serial.println("The counter adding to the array in documentingJson is now ");
    Serial.println(output_string_counter);
    Serial.println();

    //Serial.println(Json_output_string[output_string_counter]);
    output_string_counter++;
    Serial.println();

    //clear document for reuse
    Serial.println("======================domumentingJson First Half==========================");
    doc.clear();

    return;
  }
  else if (loop_end - loop_start < 20)
  {
    for (int k = loop_start; k < loop_end; k++)
    {

      JsonObject root = doc.createNestedObject();
      Serial.print("The current address: ");
      Serial.println(Sniffed_Mac_Addresses[k]);

      root["addr"] = Sniffed_Mac_Addresses[k]; //(periphery_address[k]);
      root["lat"] = latitude;
      root["lon"] = longitude;
      root["time"] = Tym;

      Serial.println("After setting root data in document");
    }
    //show the serialized json string in two formats
    Serial.println(F("Print serialized data to serial monitor... "));

    //format 1
    serializeJsonPretty(doc, Serial); //output was once serial
    Serial.println();
    //serializeJsonPretty(doc, Json_output_string); //output was once serial

    //format 2 fails to print when there is too many passengers data
    Serial.println("The following is the serialized output string for POST");
    serializeJson(doc, Json_output_string[output_string_counter]);
    Serial.println(Json_output_string[output_string_counter]);
    //serializeJson(doc, Json_output_string);
    //Serial.println(Json_output_string[output_string_counter]);
    times_we_loaded_output=times_we_loaded_output+1;
    Serial.println();
    Serial.println("The counter adding to the array in documentingJson is now ");
    Serial.println(output_string_counter);
    Serial.println();

    //Serial.println(Json_output_string[output_string_counter]);
    output_string_counter++;
    Serial.println();

    //clear document for reuse
    Serial.println("======================domumentingJson END==========================");
    doc.clear();
    return;
  }
  //loop through the detected ble devices and add to json document->array
}

void recursion_before_calling_domumentingJson(int amount_of_macs_to_be_sent)
{
  Serial.println("Inside the Recursion function");
  doc_how_many_times = amount_of_macs_to_be_sent / 20;
  doc_how_many_times_remainder = amount_of_macs_to_be_sent % 20;

  Serial.print("doc_how_many_times ");
  Serial.println(doc_how_many_times);
  Serial.print("doc_how_many_times_remainder ");
  Serial.println(doc_how_many_times_remainder);
  //if (first_time_posting_flagg)
  //{
    if (doc_how_many_times < 1)
    {
      Serial.println("doc_how_many_times is less than 1 call");
      domumentingJson(0, sniffed_macs_global_counter);
      done_jsondocumenting_flagg = true;
      first_time_posting_flagg = false;
      finished_recursion_flagg = true;
      Serial.println("Done with recursion and documenting now we can set the flag done_jsondocumenting_flagg = true");
    }
    else if (doc_how_many_times >= 1)
    {
      uint8_t limit = 0;
      Serial.println("doc_how_many_times is greater than 1 loop:");
      for (uint8_t y = 0; y < doc_how_many_times; y++)
      {
        Serial.println("documentingJson will be called atleast once");
        domumentingJson((y * 20), ((y + 1) * 20)); //sort for these addrs which are less than 10
        limit = ((y + 1) * 20);
      }
      Serial.print("The limit to be used is ");
      Serial.println(limit);

      if (doc_how_many_times_remainder > 0)
      {
        Serial.print("The doc_how_many_times_remainder variable value is ");
        Serial.println(doc_how_many_times_remainder);
        Serial.println("The variable doc_how_many_times_remainder is greater than 1 there fore lets documentjson the remainder");
        domumentingJson(limit, sniffed_macs_global_counter);
        //serial "so we are going from x to y"
      }
      Serial.println("Done with recursion and documenting now we can set the flag done_jsondocumenting_flagg = true");
      finished_recursion_flagg = true;
      done_jsondocumenting_flagg = true;
      first_time_posting_flagg = false;
    }
  //}
  /*
  else
  {
    Serial.println("recursion_before_calling_domumentingJson AFTER THE FIRST POST");
    Serial.println("=====================================================================");
    if (doc_how_many_times < 1)
    {
      Serial.println("doc_how_many_times is less than 1 call");
      domumentingJson(number_b4_upload, sniffed_macs_global_counter);
      done_jsondocumenting_flagg = true;
      first_time_posting_flagg = false;
      finished_recursion_flagg = true;
      Serial.println("Done with recursion and documenting now we can set the flag done_jsondocumenting_flagg = true");
    }
    else if (doc_how_many_times >= 1)
    {
      uint8_t limit = 0;
      Serial.println("doc_how_many_times is greater than 1 loop:");
      for (uint8_t y = 0; y < doc_how_many_times; y++)
      {
        Serial.println("documentingJson will be called atleast once");
        domumentingJson((number_b4_upload + (y * 10)), ((number_b4_upload) + ((y + 1) * 10))); //sort for these addrs which are less than 10
        limit = ((number_b4_upload) + ((y + 1) * 10));
      }
      Serial.print("The limit to be used is ");
      Serial.println(limit);

      if (doc_how_many_times_remainder > 0)
      {
        Serial.print("The doc_how_many_times_remainder variable value is ");
        Serial.println(doc_how_many_times_remainder);
        Serial.println("The variable doc_how_many_times_remainder is greater than 1 there fore lets documentjson the remainder");
        domumentingJson(limit, sniffed_macs_global_counter);
        //serial "so we are going from x to y"
      }
      Serial.println("Done with recursion and documenting now we can set the flag done_jsondocumenting_flagg = true");
      done_jsondocumenting_flagg = true;
      first_time_posting_flagg = false;
      finished_recursion_flagg = true;
      return;
    }
  }
  
 return;
}
*/
#endif