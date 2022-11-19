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

mylines = []                             # Declare an empty list named mylines.
with open ('I:\\Other computers\\My MacBook Air\\2_CBT_DEVICES\\Processing\\StudyCenter\\SS_Esp.txt', 'r') as myfile: # Open lorem.txt for reading text data.
    for myline in myfile:                # For each line, stored as myline,
        mylines.append(myline)           # add its contents to mylines.


#print(mylines) 
#print(mylines[0]) 

number_of_minutes=len(mylines)
print("The number of individual minutes is:",end=" ") 
print(number_of_minutes)

Sniff_times = []
mac_addresses = []

for line in mylines:
    data = json.loads(line)
    #print(data['time'])
    Sniff_times.append(data['time'])
    for mac in data['macs']:
        #print(mac['addr'])
        # if (mac['addr'] == '00:19:10:09:80:e8' or mac['addr'] == 'E8:DB:84:3B:3C:0A'):
        #     print('do not append')
        if mac['addr'] not in mac_addresses:
            mac_addresses.append(mac['addr'])

number_of_macs=len(mac_addresses)
print("The total number of MAC Addreses is:",end=" ") 
print(number_of_macs)

#data_dictionary = {"Addr":[], "times":[]}
list_of_dictionaries = [{"Addr":[], "times":[]} for i in range(number_of_macs)]

counter=0
for mac in mac_addresses:
    #list_of_dictionaries[counter]["Addr"].append(mac)
    for line in mylines:
        data = json.loads(line)
        for m_a_c in data['macs']:
            #print(m_a_c)
            if mac == m_a_c['addr']:
                #list_of_dictionaries[counter]["times"].append(data['time'])
                #one = datetime.strptime(data['time'])#, "%m/%d/%Y %H:%M:%S")
                one = data['time']
                #print(one)
                #list_of_dictionaries[counter]["times"].append((one.time()).strftime("%m/%d/%Y %H:%M:%S"))
                list_of_dictionaries[counter]["Addr"].append(mac)
                #list_of_dictionaries[counter]["times"].append(one.time().strftime("%H:%M:%S"))
                list_of_dictionaries[counter]["times"].append(one)
                #print(data['time'])
    counter=counter+1

count=0
for mac in mac_addresses:
#for i in range(len(list_of_dictionaries)):
    x_dates = [datetime.strptime(elem, "%d/%m/%Y %H:%M:%S") for elem in list_of_dictionaries[count]['times']]
    y_axis = list_of_dictionaries[count]['Addr']
    y_length = len(list_of_dictionaries[count]['times'])

    #TODAY ADDITON
    if y_length>=1:
    #add begin
        length_of_times = len(x_dates)
        for i in range(length_of_times):
            #start_time_object = datetime.strptime(x_dates[i], "%m/%d/%Y %H:%M:%S")
            p_time = x_dates[i]
            p_m =mac
            plt.plot(p_time, p_m,'o',color='black')
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
                            plt.plot(t, m,marker='o',color='limegreen') #this is for esp color
                            t=[]
                            m=[]

                        else:
                            print("Time within contious plot window")
                            ploted_time.append(t1)
                            ploted_time.append(t2)
                            plt.plot(t, m,marker='o',color='orangered') #this color is for esp32
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
                                plt.plot(t, m,marker='o',linestyle = '--',color='limegreen') #this color is for both
                                t=[]
                                m=[]
                            else:    
                                plt.plot(t, m,marker='o',linestyle = '--',color='orangered') #this color is for HC-05
                                t=[]
                                m=[]

                        elif t1 not in ploted_time:
                            plt.plot(t1, mac,marker='o',color='orangered') #this color is for esp32

                        elif t2 not in ploted_time:
                            plt.plot(t2, mac,marker='o',color='orangered') #this color is for esp32

                        else:
                            print("Already plotted")
                else:
                    print("not sequential")
    #e=add end

    #plt.plot(x_dates, y_axis,'o-')
    count=count+1

# print("The list of minutes is:",end=" ") 
# print(Sniff_times)
sniff_time_length = len(Sniff_times)
#print("The number of minutes is:",end=" ") 
#print(sniff_time_length)

x_start_minor_1 = datetime.strptime(Sniff_times[0], "%d/%m/%Y %H:%M:%S")
x_end_minor_1 = datetime.strptime(Sniff_times[sniff_time_length-1], "%d/%m/%Y %H:%M:%S")

print("The start for minor is:",end=" ") 
print(x_start_minor_1)
print("type of date_string =", type(x_start_minor_1))
print("The end range for minor:",end=" ") 
print(x_end_minor_1)


later = x_start_minor_1 + timedelta(seconds=60)
minor_diff = later-x_start_minor_1
print("Have I added the minute:",end=" ") 
print(minor_diff)

later2 = x_start_minor_1 + timedelta(seconds=120)
major_diff = later2-x_start_minor_1
print("Have I added the 2 mins:",end=" ") 
print(major_diff)

major_ticks = np.arange(x_start_minor_1, x_end_minor_1, major_diff)
minor_ticks = np.arange(x_start_minor_1, x_end_minor_1, minor_diff)

#plt.xticks(minor_ticks)
plt.xticks(major_ticks)
plt.minorticks_on()

plt.grid(b=True,which="minor",axis="x")
plt.grid(b=True,which="major",axis="x",color="red")
plt.grid(b=True,which="major",axis="y")
myFmt = dates.DateFormatter('%H:%M:%S')
plt.gca().xaxis.set_major_formatter(myFmt)

custom_lines = [Line2D([0], [0],marker='o', color='w',markerfacecolor='black', markersize=15),
                Line2D([0], [0],marker='o', color='w',markerfacecolor='orangered', markersize=15),
                Line2D([0], [0],marker='o', color='w',markerfacecolor='limegreen', markersize=15)]
first_legend = plt.legend(custom_lines,['Device detected once','Device detected more than once','Control device'])
ax = plt.gca().add_artist(first_legend)

import matplotlib.lines as mlines
solid = mlines.Line2D([], [], color='black', linestyle='-',markersize=15, label='Solid line: Device was always detected')
dashed = mlines.Line2D([], [], color='black', linestyle='--',markersize=15, label='Dashed line: Device not always detected')
plt.legend(handles=[solid,dashed],  bbox_to_anchor=(0,0.8), loc='lower left')
plt.xlabel('Time', fontweight ='bold', fontsize = 15)
plt.ylabel('Devices found', fontweight ='bold', fontsize = 15)
plt.title("ESP32 At Enginerring Study Center Public Location", fontweight = 'bold', fontsize = 15)
plt.show()