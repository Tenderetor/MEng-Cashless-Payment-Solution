import json
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

#########################################################################
#load for hc05
mylines1 = []                             # Declare an empty list named mylines.
with open ('I:\\Other computers\\My MacBook Air\\2_CBT_DEVICES\\Processing\\StudyCenter\\SS_Arduino.txt', 'r') as myfile1: # Open lorem.txt for reading text data.  #I:\Other computers\My MacBook Air\2_CBT_DEVICES\Processing\StudyCenter
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
with open ('I:\\Other computers\\My MacBook Air\\2_CBT_DEVICES\\Processing\\StudyCenter\\SS_Esp.txt', 'r') as myfile2: 
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
    ESP_capped_macs.append(mac_addresses2[i].upper())

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
for i in range(number_of_macs2):
    sorted_esp_macs[i] = sorted_esp_macs[i].lower()

print()
print ("The hc05 list : " + str(sorted_hc_macs))
print()
print ("The sorted esp32 list is : " + str(sorted_esp_macs))

#############################################################################
#extracting the HC05 data and times as per sorted list

number_of_macs1=len(sorted_hc_macs)
print("The total number of MAC Addreses is:",end=" ") 
print(number_of_macs1)

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
#extracting the ESP32 data and times as per sorted list

number_of_macs=len(sorted_esp_macs)
print("The total number of MAC Addreses is:",end=" ") 
print(number_of_macs)

#data_dictionary = {"Addr":[], "times":[]}
list_of_dictionaries2 = [{"Addr":[], "times":[]} for i in range(number_of_macs)]

counter2=0
for mac2 in sorted_esp_macs:
    #list_of_dictionaries[counter]["Addr"].append(mac)
    print()
    print(mac2)
    for line2 in mylines2:
        data = json.loads(line2)
        for m_a_c2 in data2['macs']:
            #print(m_a_c)
            if mac2 == m_a_c2['addr']:
                #list_of_dictionaries[counter]["times"].append(data['time'])
                #one = datetime.strptime(data['time'])#, "%m/%d/%Y %H:%M:%S")
                one2 = data2['time']
                #print(one)
                #list_of_dictionaries[counter]["times"].append((one.time()).strftime("%m/%d/%Y %H:%M:%S"))
                list_of_dictionaries2[counter2]["Addr"].append(mac2)
                #list_of_dictionaries[counter]["times"].append(one.time().strftime("%H:%M:%S"))
                list_of_dictionaries2[counter2]["times"].append(one2)
                print(data['time'])
    counter2=counter2+1

############################################################################
# PLOT FOR HC05

count1=0
for mac1 in sorted_hc_macs:
#for i in range(len(list_of_dictionaries)):
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
            p_m =mac1
            HC_PLOT = plt.plot(p_time, p_m,'o',color='black')
            start_time_object = x_dates[i]
            start_math = start_time_object#.time()
            print("We are comparing 1:", end=" ")
            print(start_math,end=" ")
            for j in range(length_of_times):
                ploted_time=[]
                if i==j:
                    print("same time")
                elif j == i-1:
                    #cmp_time_object = datetime.strptime(x_dates[j],"%m/%d/%Y %H:%M:%S")
                    cmp_time_object = x_dates[j]
                    cmp_maths = cmp_time_object#.time()
                    print("and 2: ", end=" ")
                    print(cmp_maths,end=" ")
                    difference = start_time_object-cmp_maths
                    print("The difference is:", end=" ")
                    print(difference)
                    minutes_diff = difference.total_seconds()/60
                    t=[]
                    m=[]
                    m.append(mac1)
                    m.append(mac1)
                    t1=x_dates[j]
                    t.append(t1)
                    t2=x_dates[i]
                    t.append(t2)

                    if( minutes_diff <= 0.55):
                        #if(mac == 'e8:db:84:3b:3a:5a' or mac == 'e8:db:84:3d:ef:9a'): #this is for esp
                        if(mac1 == 'E8:DB:84:3B:3A:5A' or mac1 == 'E8:DB:84:3D:EF:9A'):
                            print("Time within contious plot window")
                            ploted_time.append(t1)
                            ploted_time.append(t2)
                            HC_PLOT=plt.plot(t, m,marker='o',color='limegreen') #this is for esp color
                            t=[]
                            m=[]

                        else:
                            print("Time within contious plot window")
                            ploted_time.append(t1)
                            ploted_time.append(t2)
                            HC_PLOT=plt.plot(t, m,marker='o',color='dodgerblue') #this color is for HC-05                     
                            t=[]
                            m=[]
                    else:
                        print("Time is too long")
                        # if cmp_time_object not in ploted_time:
                        #         #plt.plot(x_dates[i], mac,'o',color='red',linestyle='--')
                        #         plt.plot(t, m,marker='o',linestyle = '--',color='red')
                        #         t=[]
                        #         m=[]
                        if t1 not in ploted_time and t2 not in ploted_time:
                            if(mac1 == 'E8:DB:84:3B:3A:5A' or mac1 == 'E8:DB:84:3D:EF:9A'):
                                HC_PLOT=plt.plot(t, m,marker='o',linestyle = '--',color='limegreen') #this color is for HC-05 
                                t=[]
                                m=[]
                            else:    
                                HC_PLOT=plt.plot(t, m,marker='o',linestyle = '--',color='dodgerblue') #this color is for HC-05 
                                t=[]
                                m=[]

                        elif t1 not in ploted_time:
                            HC_PLOT=plt.plot(t1, mac1,marker='o',color='dodgerblue') #this color is for HC-05 

                        elif t2 not in ploted_time:
                            HC_PLOT=plt.plot(t2, mac1,marker='o',color='dodgerblue') #this color is for HC-05 

                        else:
                            print("Already plotted")
                else:
                    print("not sequential")
    #e=add end

    #plt.plot(x_dates, y_axis,'o-')
    count1=count1+1

############################################################################
# HOLD PLOTS FOR ESP32

count2=0
for mac in sorted_esp_macs:
#for i in range(len(list_of_dictionaries)):
    x_dates = [datetime.strptime(elem, "%d/%m/%Y %H:%M:%S") for elem in list_of_dictionaries2[count2]['times']]
    y_axis = list_of_dictionaries2[count2]['Addr']
    y_length = len(list_of_dictionaries2[count2]['times'])

    #TODAY ADDITON
    if y_length>=1:
    #add begin
        length_of_times = len(x_dates)
        for i in range(length_of_times):
            #start_time_object = datetime.strptime(x_dates[i], "%m/%d/%Y %H:%M:%S")
            p_time = x_dates[i]
            p_m =mac
            ESP_PLOT=plt.plot(p_time, p_m,'o',color='black')
            start_time_object = x_dates[i]
            start_math = start_time_object#.time()
            print("We are comparing 1:", end=" ")
            print(start_math,end=" ")
            for j in range(length_of_times):
                ploted_time=[]
                if i==j:
                    print("same time")
                elif j == i-1:
                    #cmp_time_object = datetime.strptime(x_dates[j],"%m/%d/%Y %H:%M:%S")
                    cmp_time_object = x_dates[j]
                    cmp_maths = cmp_time_object#.time()
                    print("and 2: ", end=" ")
                    print(cmp_maths,end=" ")
                    difference = start_time_object-cmp_maths
                    print("The difference is:", end=" ")
                    print(difference)
                    minutes_diff = difference.total_seconds()/60
                    t=[]
                    m=[]
                    m.append(mac)
                    m.append(mac)
                    t1=x_dates[j]
                    t.append(t1)
                    t2=x_dates[i]
                    t.append(t2)

                    if( minutes_diff <= 0.55):
                        #if(mac == 'e8:db:84:3b:3a:5a' or mac == 'e8:db:84:3d:ef:9a'): #this is for esp
                        if(mac == 'e8:db:84:3b:3a:5a' or mac == 'e8:db:84:3d:ef:9a'):
                            print("Time within contious plot window")
                            ploted_time.append(t1)
                            ploted_time.append(t2)
                            ESP_PLOT = plt.plot(t, m,marker='o',color='limegreen') #this is for esp color
                            t=[]
                            m=[]

                        else:
                            print("Time within contious plot window")
                            ploted_time.append(t1)
                            ploted_time.append(t2)
                            ESP_PLOT=plt.plot(t, m,marker='o',color='orangered') #this color is for esp32
                            t=[]
                            m=[]
                    else:
                        print("Time is too long")
                        # if cmp_time_object not in ploted_time:
                        #         #plt.plot(x_dates[i], mac,'o',color='red',linestyle='--')
                        #         plt.plot(t, m,marker='o',linestyle = '--',color='red')
                        #         t=[]
                        #         m=[]
                        if t1 not in ploted_time and t2 not in ploted_time:
                            if(mac == 'e8:db:84:3b:3a:5a' or mac == 'e8:db:84:3d:ef:9a'):
                                ESP_PLOT=plt.plot(t, m,marker='o',linestyle = '--',color='limegreen') #this color is for both
                                t=[]
                                m=[]
                            else:    
                                ESP_PLOT=plt.plot(t, m,marker='o',linestyle = '--',color='orangered') #this color is for HC-05
                                t=[]
                                m=[]

                        elif t1 not in ploted_time:
                            ESP_PLOT=plt.plot(t1, mac,marker='o',color='orangered') #this color is for esp32

                        elif t2 not in ploted_time:
                            ESP_PLOT=plt.plot(t2, mac,marker='o',color='orangered') #this color is for esp32

                        else:
                            print("Already plotted")
                else:
                    print("not sequential")
    #e=add end

    #plt.plot(x_dates, y_axis,'o-')
    count2=count2+1