import json
#from os import minor
import time;
from datetime import datetime, timedelta
from turtle import color, width
from matplotlib import dates 
import matplotlib.pyplot as plt
from matplotlib.dates import date2num
import matplotlib.ticker as ticker
from matplotlib.ticker import FixedLocator, FixedFormatter
from matplotlib.ticker import IndexFormatter, IndexLocator
import numpy as np

hc05_lines = []
with open ('I:\Other computers\\My MacBook Air\\2_CBT_DEVICES\\Processing\\Chalkies\\Chalkboard_Arduino.txt', 'r') as myfile: 
    for hc05_line in myfile:                
        hc05_lines.append(hc05_line)           

esp_lines = []
with open ('I:\Other computers\\My MacBook Air\\2_CBT_DEVICES\\Processing\\Chalkies\\Chalkboard_Esp.txt', 'r') as myfile: 
    for esp_line in myfile:                
        esp_lines.append(esp_line)           

hc_05_time_list = []
hc_05_mac_addresses_list = []

for hc05_line in hc05_lines:
    hc05_data = json.loads(hc05_line)
    hc_05_time_list.append(hc05_data['time'])
    for mac in hc05_data['macs']:
        #print(mac['addr'])
        if mac['addr'] not in hc_05_mac_addresses_list:
            hc_05_mac_addresses_list.append(mac['addr'])

esp_time_list = []
esp_mac_addresses_list = []

for esp_line in esp_lines:
    esp_data = json.loads(esp_line)
    esp_time_list.append(esp_data['time'])
    for mac in esp_data['macs']:
        #print(mac['addr'])
        if mac['addr'] not in esp_mac_addresses_list:
            esp_mac_addresses_list.append(mac['addr'])

hc05_number_of_macs=len(hc_05_mac_addresses_list)
hc05_list_of_lists = [{"Addr":[], "times":[]} for i in range(hc05_number_of_macs)]

#the following code stores the macs and times in a list of lists
hc05_counter=0
for mac in hc_05_mac_addresses_list:
    for line in hc05_lines:
        hc05_data = json.loads(line)
        for m_a_c in hc05_data['macs']:
            if mac == m_a_c['addr']:
                one = hc05_data['time']
                hc05_list_of_lists[hc05_counter]["Addr"].append(mac)
                hc05_list_of_lists[hc05_counter]["times"].append(one)
    hc05_counter=hc05_counter+1

esp_number_of_macs=len(esp_mac_addresses_list)
esp_list_of_lists = [{"Addr":[], "times":[]} for i in range(esp_number_of_macs)]

#the following code stores the macs and times in a list of lists
esp_counter=0
for mac in esp_mac_addresses_list:
    for line in esp_lines:
        esp_data = json.loads(line)
        for m_a_c in esp_data['macs']:
            if mac == m_a_c['addr']:
                one = esp_data['time']
                esp_list_of_lists[esp_counter]["Addr"].append(mac)
                esp_list_of_lists[esp_counter]["times"].append(one)
    esp_counter=esp_counter+1

# the following is for study center time
# sniff_start_time = datetime.strptime("22/4/2022 13:40:00", "%d/%m/%Y %H:%M:%S") #this time is for ss sniff data
# sniff_stop_time = datetime.strptime("22/4/2022 13:57:00", "%d/%m/%Y %H:%M:%S") #this time is for ss sniff data
# phase_a_end_time = datetime.strptime("22/4/2022 13:45:00", "%d/%m/%Y %H:%M:%S")
# phase_b_end_time = datetime.strptime("22/4/2022 13:50:00", "%d/%m/%Y %H:%M:%S")
# phase_c_end_time = datetime.strptime("22/4/2022 13:55:00", "%d/%m/%Y %H:%M:%S")

#  this is for the chalkboard
sniff_start_time = datetime.strptime("25/4/2022 12:00:00", "%d/%m/%Y %H:%M:%S") #this time is for ss sniff data
sniff_stop_time = datetime.strptime("25/4/2022 12:16:00", "%d/%m/%Y %H:%M:%S") #this time is for ss sniff data
phase_a_end_time = datetime.strptime("25/4/2022 12:05:00", "%d/%m/%Y %H:%M:%S")
phase_b_end_time = datetime.strptime("25/4/2022 12:10:00", "%d/%m/%Y %H:%M:%S")
phase_c_end_time = datetime.strptime("25/4/2022 12:15:00", "%d/%m/%Y %H:%M:%S")

HC05_macs_sniffed_phase_a = []
HC05_macs_sniffed_phase_b = []
HC05_macs_sniffed_phase_c = []

hc05_count=0
for mac in hc_05_mac_addresses_list:
    hc05_date_time = [datetime.strptime(elem, "%d/%m/%Y %H:%M:%S") for elem in hc05_list_of_lists[hc05_count]['times']]
    hc05_one_addresses = hc05_list_of_lists[hc05_count]['Addr'] #this is one address which correpsonds to the date times above
    hc05_amount_of_date_times = len(hc05_list_of_lists[hc05_count]['times'])
    #print(hc05_one_addresses)
    #print(hc05_date_time[0])
    #print("=====================================")
    #compare the current time to the start time
    for counter_of_time in range(hc05_amount_of_date_times):
        HC05_recorded_time = hc05_date_time[counter_of_time]
        #if sniff_start_time <= HC05_recorded_time and sniff_stop_time >= HC05_recorded_time:
            #print(recorded_time)
        if  sniff_start_time <= HC05_recorded_time and phase_a_end_time >= HC05_recorded_time:
            if hc05_one_addresses[0] not in HC05_macs_sniffed_phase_a:
                HC05_macs_sniffed_phase_a.append(hc05_one_addresses[0]) #this will record the first element of the same address list
        
        if  phase_a_end_time <= HC05_recorded_time and phase_b_end_time >= HC05_recorded_time:
            if hc05_one_addresses[0] not in HC05_macs_sniffed_phase_b:
                HC05_macs_sniffed_phase_b.append(hc05_one_addresses[0])

        if  phase_b_end_time <= HC05_recorded_time and phase_c_end_time >= HC05_recorded_time:
            if hc05_one_addresses[0] not in HC05_macs_sniffed_phase_c:
                HC05_macs_sniffed_phase_c.append(hc05_one_addresses[0])             

    hc05_count=hc05_count+1

print("The HC05 total devices for phase A is:",end=" ") 
print(len(HC05_macs_sniffed_phase_a))    

print("The HC05 total devices for phase B is:",end=" ") 
print(len(HC05_macs_sniffed_phase_b)) 

print("The HC05 total devices for phase C is:",end=" ") 
print(len(HC05_macs_sniffed_phase_c)) 

ESP_macs_sniffed_phase_a = []
ESP_macs_sniffed_phase_b = []
ESP_macs_sniffed_phase_c = []

esp_count=0

for mac in esp_mac_addresses_list:
    esp_date_time = [datetime.strptime(elem, "%d/%m/%Y %H:%M:%S") for elem in esp_list_of_lists[esp_count]['times']]
    esp_one_addresses = esp_list_of_lists[esp_count]['Addr']
    esp_amount_of_date_times = len(esp_list_of_lists[esp_count]['times'])
    #print(esp_one_addresses)
    #print(esp_date_time[0])
    #print("=====================================")
    for ESP_counter_of_time in range(esp_amount_of_date_times):
        ESP_recorded_time = esp_date_time[ESP_counter_of_time]
        #if sniff_start_time <= ESP_recorded_time and sniff_stop_time >= ESP_recorded_time:
            #print(recorded_time)
        if  sniff_start_time <= ESP_recorded_time and phase_a_end_time >= ESP_recorded_time:
            if esp_one_addresses[0] not in ESP_macs_sniffed_phase_a:
                ESP_macs_sniffed_phase_a.append(esp_one_addresses[0]) #this will record the first element of the same address list
        
        if  phase_a_end_time <= ESP_recorded_time and phase_b_end_time >= ESP_recorded_time:
            if esp_one_addresses[0] not in ESP_macs_sniffed_phase_b:
                ESP_macs_sniffed_phase_b.append(esp_one_addresses[0])

        if  phase_b_end_time <= ESP_recorded_time and phase_c_end_time >= ESP_recorded_time:
            if esp_one_addresses[0] not in ESP_macs_sniffed_phase_c:
                ESP_macs_sniffed_phase_c.append(esp_one_addresses[0])    

    esp_count=esp_count+1

print("The ESP total devices for phase A is:",end=" ") 
print(len(ESP_macs_sniffed_phase_a))    

print("The ESP total devices for phase B is:",end=" ") 
print(len(ESP_macs_sniffed_phase_b)) 

print("The ESP total devices for phase C is:",end=" ") 
print(len(ESP_macs_sniffed_phase_c)) 

Hc05_height = [(len(HC05_macs_sniffed_phase_a)-2), (len(HC05_macs_sniffed_phase_b)-2), (len(HC05_macs_sniffed_phase_c)-2)] #array is 3 except for neelsie data
Esp_height = [(len(ESP_macs_sniffed_phase_a)-2), (len(ESP_macs_sniffed_phase_b)-2), (len(ESP_macs_sniffed_phase_c)-2)] #array is 3 except for neelsie data

control_devices = [2, 2, 2] #Array size is 3 except for non neelsie

barWidth = 0.25
bar1 = np.arange(len(Hc05_height))
bar2 = [x + barWidth for x in bar1]
bar3 = [x + barWidth for x in bar1]
bar4 = np.arange(len(Hc05_height))

# Setting the interval of ticks of y-axis to 10.
listOf_Yticks = np.arange(0, 20, 2)
plt.yticks(listOf_Yticks)

plt.bar(bar4, control_devices, color='limegreen', width = barWidth,  edgecolor ='k', label='Control Devices')

plt.bar(bar1, Hc05_height, color='dodgerblue', width = barWidth,  edgecolor ='k', label='HC05', bottom=control_devices)

plt.bar(bar3, control_devices, color='limegreen', width = barWidth,  edgecolor ='k')
plt.bar(bar2, Esp_height, color ='orangered', width = barWidth,  edgecolor ='k', label ='ESP32',bottom=control_devices)

plt.legend(prop={'size': 30},fontsize = 25)

plt.xlabel('Time frame', fontweight ='bold', fontsize = 30)
plt.ylabel('Total devices found', fontweight ='bold', fontsize = 30)

# the following is for chalkboard data 
plt.xticks([r + (barWidth/2) for r in range(len(Hc05_height))],
        ['12:00:00 - 12:04:59', '12:05:00 - 12:09:59', '12:10:00 - 12:14:59'])

# the following is for study study center DATA
# plt.xticks([r + (barWidth/2) for r in range(len(Hc05_height))],
#         ['12:40:00 - 12:44:59', '12:45:00 - 12:49:59', '12:50:00 - 12:54:59'])

plt.xticks(fontsize=25)
plt.yticks(fontsize=25)
plt.gca().yaxis.grid(True)
#plt.legend()
plt.title("Periodic Total Devices Detected At The Engineering Student Cafe", fontweight = 'bold', fontsize = 30)
plt.show()