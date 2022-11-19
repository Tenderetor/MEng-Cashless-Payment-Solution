import json
from traceback import print_tb
from turtle import color
#from os import minor
import matplotlib
import matplotlib.patches as mpatches
import time;
from datetime import datetime, timedelta
from matplotlib import dates 
import matplotlib.pyplot as plt
from matplotlib.dates import date2num
import matplotlib.ticker as ticker
from matplotlib.ticker import FixedLocator, FixedFormatter
from matplotlib.ticker import IndexFormatter, IndexLocator
import numpy as np
from matplotlib.lines import Line2D    
import itertools
import re
#########################################################################
#load for hc05   /Users/kpt/Desktop/BLE_Reliability/Results/Nano_2.txt
mylines1 = []                             # Declare an empty list named mylines.
with open ('I:\\Other computers\\My MacBook Air\\BLE_Reliability\\Results\\Nano_2.txt', 'r') as myfile1: # Open lorem.txt for reading text data.  #I:\Other computers\My MacBook Air\2_CBT_DEVICES\Processing\StudyCenter
    for myline1 in myfile1:                # For each line, stored as myline,
        mylines1.append(myline1)           # add its contents to mylines.

Sniff_times1 = []
mac_addresses1 = []

for line1 in mylines1:
    data1 = json.loads(line1)
    #print(data['time'])
    Sniff_times1.append(data1['time'])
    for mac1 in data1['macs']:
        if mac1['addr'] not in mac_addresses1:
            mac_addresses1.append(mac1['addr'])

#########################################################################
#load for esp32
mylines2 = []                             # Declare an empty list named mylines.
with open ('I:\\Other computers\\My MacBook Air\\BLE_Reliability\\Results\\Esp32_2.txt', 'r') as myfile2: 
    for myline2 in myfile2:                # For each line, stored as myline,
        mylines2.append(myline2)           # add its contents to mylines.

Sniff_times2 = []
mac_addresses2 = []

for line2 in mylines2:
    data2 = json.loads(line2)
    Sniff_times2.append(data2['time'])
    for mac2 in data2['macs']:
        if mac2['addr'] not in mac_addresses2:
            mac_addresses2.append((mac2['addr']))

############################################################################
#sorting the 2 lists so that we start from same macs
number_of_macs2=len(mac_addresses2)

ESP_capped_macs = []

for i in range(number_of_macs2):
    ESP_capped_macs.append(mac_addresses2[i])

sorted_esp_macs = []
sorted_hc_macs = []
len_of_sorted = 0
for hc_mac in mac_addresses1:
    for esp_mac in ESP_capped_macs:
        if((esp_mac == hc_mac) and (esp_mac not in sorted_esp_macs)):
            sorted_esp_macs.append(esp_mac)
            sorted_hc_macs.append(esp_mac)
            break

#the hc05 macs which do not appear in esp32 must also be aded at the end
for left_hc_macs in mac_addresses1:
    if(left_hc_macs not in sorted_hc_macs):
        sorted_hc_macs.append(left_hc_macs)

#the esp32 macs which do not appear in hc05 must also be aded at the end
for left_esp_mac in ESP_capped_macs:
    if (left_esp_mac not in sorted_esp_macs):
        sorted_esp_macs.append(left_esp_mac)

#change the esp32 macs to lower case 
# for i in range(number_of_macs2):
#     sorted_esp_macs[i] = sorted_esp_macs[i].lower()

print()
print ("The hc05 list : " + str(sorted_hc_macs))
print()
print ("The sorted esp32 list is : " + str(sorted_esp_macs))

#############################################################################
#extracting the HC05 data and times as per sorted list

number_of_macs1=len(sorted_hc_macs)
# print("The total number of MAC Addreses is:",end=" ") 
# print(number_of_macs1)

#data_dictionary = {"Addr":[], "times":[]}
list_of_dictionaries1 = [{"Addr":[], "times":[]} for i in range(number_of_macs1)]

counter1=0
for mac1 in sorted_hc_macs:
    #list_of_dictionaries[counter]["Addr"].append(mac)
    for line1 in mylines1:
        data1 = json.loads(line1)
        for m_a_c1 in data1['macs']:
            #print(m_a_c)
            if mac1 == m_a_c1['addr']:
                #list_of_dictionaries[counter]["times"].append(data['time'])
                #one = datetime.strptime(data['time'])#, "%m/%d/%Y %H:%M:%S")
                one1 = data1['time']
                #print(one)
                #list_of_dictionaries[counter]["times"].append((one.time()).strftime("%m/%d/%Y %H:%M:%S"))
                list_of_dictionaries1[counter1]["Addr"].append(mac1)
                #list_of_dictionaries[counter]["times"].append(one.time().strftime("%H:%M:%S"))
                list_of_dictionaries1[counter1]["times"].append(one1)
                #print(data['time'])
    counter1=counter1+1

#############################################################################
#extracting the ESP32 data as per original
list_of_dictionaries_2 = [{"Addr":[], "times":[]} for i in range(number_of_macs2)]

counter_2=0
for mac in mac_addresses2:
    #list_of_dictionaries[counter]["Addr"].append(mac)
    for line2 in mylines2:
        data = json.loads(line2)
        for m_a_c in data['macs']:
            #print(m_a_c)
            if mac == m_a_c['addr']:
                #list_of_dictionaries[counter]["times"].append(data['time'])
                #one = datetime.strptime(data['time'])#, "%m/%d/%Y %H:%M:%S")
                one = data['time']
                #print(one)
                #list_of_dictionaries[counter]["times"].append((one.time()).strftime("%m/%d/%Y %H:%M:%S"))
                list_of_dictionaries_2[counter_2]["Addr"].append(mac)
                #list_of_dictionaries[counter]["times"].append(one.time().strftime("%H:%M:%S"))
                list_of_dictionaries_2[counter_2]["times"].append(one)
                #print(data['time'])
    counter_2=counter_2+1
#############################################################################
#ESP32 lets try to search the original dictionary wit a sorted list
new_list_of_dictionaries_2 = [{"Addr":[], "times":[]} for i in range(number_of_macs2)]
loading_counter = 0

for new_esp_mac in sorted_esp_macs:
    for i in range(number_of_macs2):
        current_mac_from_unsorted = list_of_dictionaries_2[i]["Addr"]
        compare_mac = current_mac_from_unsorted[0]
        if(new_esp_mac == compare_mac):
            # print()
            # print("from unsorted " +str(compare_mac), end="=")
            # print(new_esp_mac)
            # print()
            new_list_of_dictionaries_2[loading_counter]=(list_of_dictionaries_2[i]).copy()
            break
        else:
            continue

    loading_counter=loading_counter+1    

# print("the new sorted dictionary is:")
# print(new_list_of_dictionaries_2)
# print()
# print("the old unsorted dictionary is:")
# print(list_of_dictionaries_2)
# print()

############################################################################
# Simulataneous PLOT FOR HC05 & ESP32

count1=0
count2=0

# for (i_hc_mac,i_esp_mac) in itertools.zip_longest(sorted_hc_macs, sorted_esp_macs):
#     print("yes")

for (i_hc_mac,i_esp_mac) in itertools.zip_longest(sorted_hc_macs, sorted_esp_macs):
    if(count1 <= 19): #hard coding because the hc05 picked 10 macs 
        x_dates = [datetime.strptime(elem, "%d/%m/%Y %H:%M:%S") for elem in list_of_dictionaries1[count1]['times']]
        y_axis = list_of_dictionaries1[count1]['Addr']
        y_length = len(list_of_dictionaries1[count1]['times'])

        #TODAY ADDITON
        if y_length>=1:
        #add begin
            length_of_times = len(x_dates)
            for i in range(length_of_times):
                #start_time_object = datetime.strptime(x_dates[i], "%m/%d/%Y %H:%M:%S")
                p_time = x_dates[i]
                # bo_ol =  re.search('[a-zA-Z]', i_hc_mac)
                # if bo_ol:
                #     i_hc_mac=i_hc_mac
                # else:
                #     i_hc_mac=i_hc_mac+"ii"

                i_hc_mac=i_hc_mac.upper()
                p_m=i_hc_mac
                HC_PLOT = plt.plot(p_time, p_m,'o',color='dodgerblue')
                start_time_object = x_dates[i]
                start_math = start_time_object#.time()
                #print("We are comparing 1:", end=" ")
                #print(start_math,end=" ")
                for j in range(length_of_times):
                    ploted_time=[]
                    if i==j:
                        #print("same time")
                        continue
                    elif j == i-1:
                        #cmp_time_object = datetime.strptime(x_dates[j],"%m/%d/%Y %H:%M:%S")
                        cmp_time_object = x_dates[j]
                        cmp_maths = cmp_time_object#.time()
                        #print("and 2: ", end=" ")
                        #print(cmp_maths,end=" ")
                        difference = start_time_object-cmp_maths
                        #print("The difference is:", end=" ")
                        #print(difference)
                        minutes_diff = difference.total_seconds()/60
                        t=[]
                        m=[]
                        m.append(i_hc_mac)
                        m.append(i_hc_mac)
                        t1=x_dates[j]
                        t.append(t1)
                        t2=x_dates[i]
                        t.append(t2)

                        if( minutes_diff <= 0.55):
                            if(i_hc_mac == 'DC:23:4D:3D:6F:FB' or i_hc_mac == 'DC:23:4D:3E:12:10' or i_hc_mac == 'DC:23:4D:3E:11:19'):
                                #print("Time within contious plot window")
                                ploted_time.append(t1)
                                ploted_time.append(t2)
                                plt.plot(t, m,marker='o',color='darkgreen') #this is for esp color
                                t=[]
                                m=[]

                            elif(i_hc_mac == 'E8:DB:84:3B:5F:32' or i_hc_mac == 'A4:E5:7C:68:DE:EA' or i_hc_mac == 'E8:DB:84:3A:7A:5E'):
                                #print("Time within contious plot window")
                                ploted_time.append(t1)
                                ploted_time.append(t2)
                                plt.plot(t, m,marker='o',color='gray') #this is for esp color
                                t=[]
                                m=[]

                            else:
                                #print("Time within contious plot window")
                                ploted_time.append(t1)
                                ploted_time.append(t2)
                                plt.plot(t, m,marker='o',color='dodgerblue') #this color is for HC-05                     
                                t=[]
                                m=[]
                        else:
                            #print("Time is too long")
                            # if cmp_time_object not in ploted_time:
                            #         #plt.plot(x_dates[i], mac,'o',color='red',linestyle='--')
                            #         plt.plot(t, m,marker='o',linestyle = '--',color='red')
                            #         t=[]
                            #         m=[]
                            if t1 not in ploted_time and t2 not in ploted_time:
                                if(i_hc_mac == 'DC:23:4D:3D:6F:FB' or i_hc_mac == 'DC:23:4D:3E:12:10' or i_hc_mac == 'DC:23:4D:3E:11:19'):
                                    plt.plot(t, m,marker='o',linestyle = '--',color='darkgreen') #this color is for HC-05 
                                    t=[]
                                    m=[]
                                elif(i_hc_mac == 'E8:DB:84:3B:5F:32' or i_hc_mac == 'A4:E5:7C:68:DE:EA' or i_hc_mac == 'E8:DB:84:3A:7A:5E'):
                                    plt.plot(t, m,marker='o',linestyle = '--',color='gray') 
                                    t=[]
                                    m=[]    
                                else:    
                                    plt.plot(t, m,marker='o',linestyle = '--',color='dodgerblue') #this color is for HC-05 
                                    t=[]
                                    m=[]

                            elif t1 not in ploted_time:
                                plt.plot(t1, i_hc_mac,marker='o',color='dodgerblue') #this color is for HC-05 

                            elif t2 not in ploted_time:
                                plt.plot(t2, i_hc_mac,marker='o',color='dodgerblue') #this color is for HC-05 

                            else:
                                #print("Already plotted")
                                continue
                    else:
                        #print("not sequential")
                        continue
        #e=add end
    # else:
    #     continue


    #######################################################################
    #esp part
    if(count2 <= 19):
        x_dates2 = [datetime.strptime(elem1, "%d/%m/%Y %H:%M:%S") for elem1 in new_list_of_dictionaries_2[count2]['times']]
        y_axis = new_list_of_dictionaries_2[count2]['Addr']
        y_length = len(new_list_of_dictionaries_2[count2]['times'])
        # print()
        # print(i_esp_mac)
        # print("the current esp32 dates " +str(x_dates2))
        # print()

        #TODAY ADDITON
        if y_length>=1:
        #add begin
            length_of_times2 = len(x_dates2)
            for i in range(length_of_times2):
                #start_time_object = datetime.strptime(x_dates[i], "%m/%d/%Y %H:%M:%S")
                p_time2 = x_dates2[i]

                bo_ol2 =  re.search('[a-zA-Z]', i_esp_mac)
                if bo_ol2:
                    i_esp_mac =i_esp_mac
                else:
                    i_esp_mac =i_esp_mac+"i"

                p_m2 =i_esp_mac
                plt.plot(p_time2, p_m2,'o',color='orangered')
                start_time_object = x_dates2[i]
                start_math = start_time_object#.time()
                #print("We are comparing 1:", end=" ")
                #print(start_math,end=" ")
                for j in range(length_of_times2):
                    ploted_time=[]
                    if i==j:
                        #print("same time")
                        continue
                    elif j == i-1:
                        #cmp_time_object = datetime.strptime(x_dates[j],"%m/%d/%Y %H:%M:%S")
                        cmp_time_object = x_dates2[j]
                        cmp_maths = cmp_time_object#.time()
                        #print("and 2: ", end=" ")
                        #print(cmp_maths,end=" ")
                        difference = start_time_object-cmp_maths
                        #print("The difference is:", end=" ")
                        #print(difference)
                        minutes_diff = difference.total_seconds()/60
                        t=[]
                        m=[]
                        m.append(i_esp_mac)
                        m.append(i_esp_mac)
                        t1=x_dates2[j]
                        t.append(t1)
                        t2=x_dates2[i]
                        t.append(t2)

                        if( minutes_diff <= 0.55):
                            #if(mac == 'e8:db:84:3b:3a:5a' or mac == 'e8:db:84:3d:ef:9a'): #this is for esp
                            if(i_esp_mac == 'dc:23:4d:3d:6f:fb' or i_esp_mac == 'dc:23:4d:3e:12:10' or i_esp_mac == 'dc:23:4d:3e:11:19'):
                                #print("Time within contious plot window")
                                ploted_time.append(t1)
                                ploted_time.append(t2)
                                plt.plot(t, m,marker='o',color='darkgreen') #this is for esp color
                                t=[]
                                m=[]

                            elif(i_esp_mac == 'e8:db:84:3b:5f:32' or i_esp_mac == 'a4:e5:7c:68:de:ea' or i_esp_mac == 'e8:db:84:3a:7a:5e'):
                                #print("Time within contious plot window")
                                ploted_time.append(t1)
                                ploted_time.append(t2)
                                plt.plot(t, m,marker='o',color='gray') #this is for esp color
                                t=[]
                                m=[]

                            else:
                                #print("Time within contious plot window")
                                ploted_time.append(t1)
                                ploted_time.append(t2)
                                plt.plot(t, m,marker='o',color='orangered') #this color is for esp32
                                t=[]
                                m=[]
                        else:
                            #print("Time is too long")
                            # if cmp_time_object not in ploted_time:
                            #         #plt.plot(x_dates[i], mac,'o',color='red',linestyle='--')
                            #         plt.plot(t, m,marker='o',linestyle = '--',color='red')
                            #         t=[]
                            #         m=[]
                            if t1 not in ploted_time and t2 not in ploted_time:
                                if(i_esp_mac == 'dc:23:4d:3d:6f:fb' or i_esp_mac == 'dc:23:4d:3e:12:10' or i_esp_mac == 'dc:23:4d:3e:11:19'):
                                    plt.plot(t, m,marker='o',linestyle = '--',color='darkgreen') #this color is for both
                                    t=[]
                                    m=[]
                                
                                elif(i_esp_mac == 'e8:db:84:3b:5f:32' or i_esp_mac == 'a4:e5:7c:68:de:ea' or i_esp_mac == 'e8:db:84:3a:7a:5e'):
                                    plt.plot(t, m,marker='o',linestyle = '--',color='gray') 
                                    t=[]
                                    m=[]      

                                else:    
                                    plt.plot(t, m,marker='o',linestyle = '--',color='orangered') #this color is for HC-05
                                    t=[]
                                    m=[]

                            elif t1 not in ploted_time:
                                plt.plot(t1, i_esp_mac,marker='o',color='orangered') #this color is for esp32

                            elif t2 not in ploted_time:
                                plt.plot(t2, i_esp_mac,marker='o',color='orangered') #this color is for esp32

                            else:
                                #print("Already plotted")
                                continue
                    else:
                        #print("not sequential")
                        continue
    #e=add end

    #plt.plot(x_dates, y_axis,'o-')
    count1=count1+1
    count2=count2+1

print("final hc macs "+str(sorted_hc_macs))
print("final esp macs" +str(sorted_esp_macs))


####################################################################################
#Plotting the held data
# print("The list of minutes is:",end=" ") 
# print(Sniff_times)
sniff_time_length = len(Sniff_times2)
#print("The number of minutes is:",end=" ") 
#print(sniff_time_length)

x_start_minor_1 = datetime.strptime(Sniff_times2[0], "%d/%m/%Y %H:%M:%S")
x_end_minor_1 = datetime.strptime(Sniff_times2[sniff_time_length-1], "%d/%m/%Y %H:%M:%S")

#print("The start for minor is:",end=" ") 
#print(x_start_minor_1)
#print("type of date_string =", type(x_start_minor_1))
#print("The end range for minor:",end=" ") 
#print(x_end_minor_1)


later = x_start_minor_1 + timedelta(seconds=60)
minor_diff = later-x_start_minor_1
#print("Have I added the minute:",end=" ") 
#print(minor_diff)

later2 = x_start_minor_1 + timedelta(seconds=120)
major_diff = later2-x_start_minor_1
#print("Have I added the 2 mins:",end=" ") 
#print(major_diff)

major_ticks = np.arange(x_start_minor_1, x_end_minor_1, major_diff)
minor_ticks = np.arange(x_start_minor_1, x_end_minor_1, minor_diff)

#plt.xticks(minor_ticks)
plt.xticks(major_ticks)
plt.minorticks_on()

plt.grid(b=True,which="minor",axis="y",color='black')
#plt.grid(b=True,which="major",axis="x")
#plt.grid(b=True,which="major",axis="y")
myFmt = dates.DateFormatter('%H:%M:%S')
ti_cks = [1.5, 3.5, 5.5, 7.5, 9.5, 11.5, 13.5, 15.5, 17.5, 19.5, 21.5, 23.5, 25.5, 27.5, 29.5, 31.5, 33.5, 35.5, 37.5] 
plt.gca().yaxis.set_minor_locator(ticker.FixedLocator(ti_cks))
plt.gca().xaxis.set_major_formatter(myFmt)
#plt.gca().set_yticklabels([])


# the following is for ploting HC-05 legends
custom_lines = [Line2D([0], [0],marker='o', color='w',markerfacecolor='dodgerblue', markersize=19),
                Line2D([0], [0],marker='o', color='w',markerfacecolor='orangered', markersize=19),
                Line2D([0], [0],marker='o', color='w',markerfacecolor='gray', markersize=19),
                Line2D([0], [0],marker='o', color='w',markerfacecolor='darkgreen', markersize=19)]
first_legend = plt.legend(custom_lines,['Random BLE device detected by Arduino Nano system','Random BLE device detected by ESP32 system','ESP32 advertising BLE','Vizia Airtag (BLE advertising Smart tracker)'],loc='best',prop={'size': 13},fontsize=15)
ax = plt.gca().add_artist(first_legend)
#from matplotlib.legend import Legend


import matplotlib.lines as mlines
solid = mlines.Line2D([], [], color='black', linestyle='-',markersize=22, label='Solid line: Device was always detected')
dashed = mlines.Line2D([], [], color='black', linestyle='--',markersize=22, label='Dashed line: Device not always detected')
plt.legend(handles=[solid,dashed],loc='upper center', bbox_to_anchor=(0.6,1),prop={'size': 13},fontsize=15)
plt.xticks(fontsize=15)
plt.yticks(fontsize=12)
plt.xlabel('Time', fontweight ='bold', fontsize = 25)
plt.ylabel('Devices found', fontweight ='bold', fontsize = 25)
plt.title("Arduino Nano & ESP32 Tracking Bluetooth Low Energy Devices: 2", fontweight = 'bold', fontsize = 30)
plt.show()