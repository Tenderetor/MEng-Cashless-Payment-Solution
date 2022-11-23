<?php
require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Telegram\Bot\Commands\Command;

$factory = (new Factory)
    ->withServiceAccount('xxx.json')
    ->withDatabaseUri('https://xxx.firebaseio.com/');

$database = $factory->createDatabase();

$botkey = "1826631603:AAHCaw1052cv__Ley2L0rBDS1H0tmMZ4aSU";
$Globals['bot_key'] = $botkey;
$message_url = "https://api.telegram.org/bot" . $botkey . "/sendMessage";
$registerd_macs = array();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $update = json_decode(file_get_contents("php://input"), TRUE);

    if ((isset($update[0]['trip'])) && (isset($update[0]['lat'])) && (isset($update[0]['lon'])) && (isset($update[0]['time']))) {
        $trip_ref = 'Trip_Number'; //reference to firebase
        $trip_fetchdata = $database->getReference($trip_ref)->getValue(); // this returns the paid passenger nodes
        $trip_child_keys = $database->getReference($trip_ref)->getChildKeys();

        $length = count($trip_child_keys);
        $counter = 0;

        foreach ($update as $trip_data) {
            //echo("data");
            //echo ("\r\n"); 
            //echo("lat: ");
            //echo($trip_data['lat']);
            //echo ("\r\n"); s
            //echo("lon: ");
            //echo($trip_data['lon']);
            //echo ("\r\n");
            //echo("time: ");
            //echo($trip_data['time']);

            foreach ($trip_fetchdata as $data) {
                if ($counter == $length - 1) {
                    $p = $data['trip'];
                    echo ($p);
                    $number = $p + 1;
                    $ref = 'Trip_Number' . $number;

                    $postdata1 = [
                        'trip' => $number,
                        'lat' => $trip_data['lat'],
                        'lon' => $trip_data['lon'],
                        'time' => $trip_data['time']
                    ];

                    $postdata2 = [
                        'mac' => "00:00:00:00:00:00",
                        'lat' => $trip_data['lat'],
                        'lon' => $trip_data['lon'],
                        'time' => $trip_data['time']
                    ];

                    $trip_ref = 'Trip_Number';
                    $post1 = $database->getReference($trip_ref)->push($postdata1);
                    $post2 = $database->getReference($ref)->push($postdata2);
                }
                $counter++;
            }
        }
    } //end of an if

    //test if post request is from Arduino
    else if ((isset($update[0]['addr'])) && (isset($update[0]['lat'])) && (isset($update[0]['lon'])) && (isset($update[0]['time']))) {

        $trip_ref = 'Trip_Number'; //reference to firebase
        $trip_fetchdata = $database->getReference($trip_ref)->getValue(); // this returns the paid passenger nodes
        $trip_child_keys = $database->getReference($trip_ref)->getChildKeys();

        /*The following lines create the node for the latest trip*/
        $length = count($trip_child_keys);
        $counter = 0;

        foreach ($trip_fetchdata as $data) {
            if ($counter == $length - 1) {
                $latest_trip = $data['trip'];
                $current_trip_ref = 'Trip_Number' . $latest_trip;
            }
            $counter++;
        }
        /*The creation of the node for the latest trip has ended*/

        /*The following lines get the reference of the data which is already onto the dB*/
        $database_sniffed_fetchdata = $database->getReference($current_trip_ref)->getValue();
        $sniffed_child_keys = array_keys($database_sniffed_fetchdata);
        $sniffed_child_keys_values = array_values($sniffed_child_keys);

        $len = count($sniffed_child_keys);

        $reversed_database_sniffed_fetchdata = array_reverse($database_sniffed_fetchdata);
        $reversed_sniffed_child_keys = array_reverse($sniffed_child_keys_values);

        /*
            echo("The Reversed data_fetched at start");
            echo ("\r\n");
            foreach($reversed_database_sniffed_fetchdata as $endata1)
            {
                echo($endata1["mac"]); 
                echo ("\r\n");
            }
            echo ("\r\n");
            
            echo("The Reversed keys at start");
            echo ("\r\n");
            foreach($reversed_sniffed_child_keys as $key)
            {
                echo($key);
                echo ("\r\n");
            }
            echo ("\r\n");
            */

        foreach ($update as $sniffedpass) {
            //echo("first loop");
            //echo ("\r\n"); 
            $address_from_mcu = $sniffedpass['addr'];
            $time_from_mcu = $sniffedpass['time'];
            echo ("FROM MCU=> ");
            echo ($address_from_mcu);
            echo ("\r\n");
            //echo ("\r\n"); 
            $mcu_time = strtotime($time_from_mcu);

            $counter_database = 0;
            foreach ($reversed_database_sniffed_fetchdata as $database_pass)
            //foreach($reversed_sniffed_child_keys as $key)
            {
                //$database_sniffed_time = $reversed_database_sniffed_fetchdata[$key]['time_first_seen'];
                //$database_sniffed_time_last_seen = $reversed_database_sniffed_fetchdata[$key]['time_last_seen'];
                //$database_sniffed_mac = $reversed_database_sniffed_fetchdata[$key]['mac'];

                $database_sniffed_mac = $database_pass['mac'];
                $database_sniffed_time = $database_pass['time_first_seen'];
                $database_sniffed_time_last_seen = $database_pass['time_last_seen'];

                echo ("FROM Reversed dB=> ");
                echo ($database_sniffed_mac);
                echo ("\r\n");

                //$counter_database++;
                //$database_sniffed_time = $database_pass['time_first_seen'];
                //$database_sniffed_time_last_seen = $database_pass['time_last_seen'];
                //$database_sniffed_mac = $database_pass['mac'];
                //echo("Counting = ");
                //echo($counter_database);
                //echo ("\r\n");
                //echo("FROM dB=> ");
                //echo($database_sniffed_mac);
                //echo ("\r\n");

                $time = strtotime($database_sniffed_time);
                $last_time = strtotime($database_sniffed_time_last_seen);
                //echo("The db last seen time is ");
                //echo($last_time);
                //echo ("\r\n");

                if ($address_from_mcu == $database_sniffed_mac) {

                    echo ("This MAC Matched");
                    echo ("\r\n");
                    echo ("The key is ");
                    echo ($reversed_sniffed_child_keys[$counter_database]);
                    echo ("\r\n");
                    echo ("\r\n");
                    //break;

                    //echo("The converted time in database is ");
                    //echo($time);
                    //echo ("\r\n");

                    //echo("The converted time from mcu is ");
                    //echo($mcu_time);
                    //echo ("\r\n");

                    //echo("The difference is ");
                    $ek1 = $mcu_time - $time;
                    //echo($ek1);
                    //echo ("\r\n");

                    //echo("Is it within ten minutes? ");
                    $ekll = $ek1 / 60;
                    //echo($ekll);
                    //echo ("\r\n");

                    //echo("The last seen time from db is ");
                    //echo($last_time);
                    //echo ("\r\n");

                    if ($last_time > 0) {
                        //echo("The last seen time from db is ");
                        //echo($last_time);
                        //echo ("\r\n");

                        $ek2 = $mcu_time - $last_time;
                        $ekl2 = $ek2 / 60;
                        $has_last_flagg = true;
                    } else {
                        $has_last_flagg = false;
                    }


                    //if(($ekll < 10)||(($ekl2<10)&&($has_last_flagg==true)))
                    if ((($ekll < 10) && ($has_last_flagg == false)) || (($ekl2 < 10) && ($has_last_flagg == true))) {
                        //$child_indicator_key = $reversed_sniffed_child_keys[$counter_database+1];
                        echo ("Has been seen in the last ten minutes");
                        echo ("\r\n");
                        echo ("Adding node time data ");
                        echo ("\r\n");
                        echo ("\r\n");
                        $flagg_seen_in_ten = true;
                        $postdata_ = [
                            'time_last_seen' => $sniffedpass['time']
                        ];
                        $key = $reversed_sniffed_child_keys[$counter_database];
                        $ref_ = $current_trip_ref . '/' . $key;
                        $post = $database->getReference($ref_)->update($postdata_);
                        break;
                    }

                    //if((($has_last_flagg == false)&&($ekll >= 10)))
                    if ((($has_last_flagg == false) && ($ekll >= 10)) || (($has_last_flagg == true) && ($ekl2 >= 10))) {
                        echo ("NOT SEEN IN 10");
                        echo ("\r\n");
                        //echo ("\r\n"); 
                        $flagg_seen_in_ten = false;
                        $postdata_ = [
                            'lat' => $sniffedpass['lat'],
                            'lon' => $sniffedpass['lon'],
                            'mac' => $sniffedpass['addr'],
                            'time_first_seen' => $sniffedpass['time']
                        ];
                        $ref_ = $current_trip_ref;
                        $post = $database->getReference($ref_)->push($postdata_);
                        break;
                    }
                } else if (($address_from_mcu != $database_sniffed_mac) && ($counter_database == $len - 1)) {
                    echo ("FROM MCU=> ");
                    echo ($address_from_mcu);
                    echo (" is a New Mac");
                    echo ("\r\n");
                    echo ("\r\n");
                    $postdata_ = [
                        'lat' => $sniffedpass['lat'],
                        'lon' => $sniffedpass['lon'],
                        'mac' => $sniffedpass['addr'],
                        'time_first_seen' => $sniffedpass['time']
                    ];
                    $ref_ = $current_trip_ref;
                    $post = $database->getReference($ref_)->push($postdata_);
                    $dont_flagg = true;
                    break;
                }

                $counter_database++;
            }

            //$child_indicator_key = $reversed_sniffed_child_keys[$counter_database];

            if (($flagg_seen_in_ten == true) && ($dont_flagg == false)) {
                $postdata_ = [
                    'time_last_seen' => $sniffedpass['time']
                ];
                $ref_ = $current_trip_ref . '/' . $child_indicator_key;
                $post = $database->getReference($ref_)->update($postdata_);
            }

            /* the following is for running and saving times without considering the last seens etc
                    $postdata_ = [
                    'lat' => $sniffedpass['lat'],
                    'lon' => $sniffedpass['lon'],
                    'mac' => $sniffedpass['addr'],
                    'time_first_seen' => $sniffedpass['time']
                    ];
                    $ref_ = $current_trip_ref;
                    $post = $database->getReference($ref_)->push($postdata_);       
                */
        }

        $db_sniffed_fetchdata = $database->getReference($current_trip_ref)->getValue();
        $sniffed_child_keys = array_keys($db_sniffed_fetchdata);
        $sniffed_child_keys_values = array_values($sniffed_child_keys);

        $reversed_database_sniffed_fetchdata2 = array_reverse($db_sniffed_fetchdata);
        $reversed_sniffed_child_keys2 = array_reverse($sniffed_child_keys_values);

        //echo ("\r\n");
        //echo("The Reversed data_fetched at the end");
        //echo ("\r\n");
        //print_r($reversed_database_sniffed_fetchdata2);
        //foreach($reversed_database_sniffed_fetchdata2 as $endata)
        //{
        //echo($endata["mac"]);
        //echo ("\r\n");
        //}

        //echo ("\r\n");
        //echo("The Reversed keys at the end");
        //echo ("\r\n");
        //foreach($reversed_sniffed_child_keys2 as $key2)
        //{
        //echo($key2);
        //echo("\r\n");
        //}

        $ref = 'registered_passengers';
        $fetchdata = $database->getReference($ref)->getValue();

        if ($fetchdata > 0) {
            foreach ($update as $passenger) {
                $macsniffed = $passenger['addr'];
                //SearchFirebase($fetchdata,$macsniffed,$database);
            }
        }
    } //end of an if

    //    if ((isset($update[0]['addr'])) && (isset($update[0]['lat'])) && (isset($update[0]['lon'])) && (isset($update[0]['time']))) 
    else if ((isset($update['message']['chat']['id'])) && (isset($update['message']['chat']['first_name'])) && (isset($update['message']['chat']['last_name'])) && (isset($update['message']['text']))) {
        //echo("Starting prompts the right way!");
        //$chatId = 1818711871; //sa number
        //$chatId = 1805318131; //zim number
        //$botkey = "1826631603:AAHCaw1052cv__Ley2L0rBDS1H0tmMZ4aSU";
        //$message_url = "https://api.telegram.org/bot" . $botkey . "/sendMessage";
        //$text = 'Welcome to taxi AA1!! Select one of the following options';
        //$buttons = [["Travel"], ["Exit"]];
        //sendMessage($message_url, $chatId, $text, $buttons);

        startPrompts();
    }
    
    else{
        echo ("Hello curl \n\n");
        Confirmations_Expire();
    }
    
}

function Confirmations_Expire()
{
  
    $ref = 'Driver_Fueling';
    Delete_Expired($ref);

    echo("New ref \n\n");
    
    $ref = "Passenger_Top_Up";
    Delete_Expired($ref);
    
    echo("New ref \n\n");
    echo "<br>";
    delete_agent_top_up_flag();
    
    echo("New ref \n\n");
    echo "<br>";
    
    $ref = "Agent_and_Passenger";
    Delete_Expired($ref);

}

function delete_agent_top_up_flag()
{
    $ref_node = "Agent_and_Passenger";

    date_default_timezone_set('Africa/Johannesburg');
    $date = date('y-m-d G:i:s');

    $current_time_for_maths = strtotime($date);  
        
    $data =  $GLOBALS['database']->getReference($ref_node)->getValue();
    $child_keys = $GLOBALS['database']->getReference($ref_node)->getChildKeys();
        
    foreach ($child_keys as $key)
    {
        $posted_time = $data[$key]['time'];
        $posted_time_for_maths = strtotime($posted_time);
        
        $time_difference = $current_time_for_maths - $posted_time_for_maths;
        $time_result = $time_difference/60;
        
        echo "<br>";
        echo("key is $key and time at $posted_time therefore ");
        
        if($time_result >= 2) //its expired
        {
            echo ("expired \n\n");
            echo "<br>";

            $ref2 = 'Agent_Send_Money';
            $data2 =  $GLOBALS['database']->getReference($ref)->getValue();
            $agent_ide_ntity = $data[$key]['tele_id'];
            
            echo("Looking for the tele_id for agent to delete flagg: $agent_ide_ntity \n\n");
            echo "<br>";
                    
            // $text = "time has expired";
            // $buttons = null;
            // sendMessage($GLOBALS['message_url'], $agent_ide_ntity, $text, $buttons);
            
            $ref3 = 'Agent_Send_Money';
            clea_r_e_rror_count($agent_ide_ntity, $ref3); 
        }
        else{
            echo ("not expired \n\n");
            echo "<br>";
        }
    }   
}

function Delete_Expired($ref_node)
{
    date_default_timezone_set('Africa/Johannesburg');
    $date = date('y-m-d G:i:s');

    $current_time_for_maths = strtotime($date);  
        
    $data =  $GLOBALS['database']->getReference($ref_node)->getValue();
    $child_keys = $GLOBALS['database']->getReference($ref_node)->getChildKeys();
        
    foreach ($child_keys as $key)
    {
        $posted_time = $data[$key]['time'];
        $posted_time_for_maths = strtotime($posted_time);
        
        $time_difference = $current_time_for_maths - $posted_time_for_maths;
        $time_result = $time_difference/60;
        
        echo "<br>";
        echo("key is $key and time at $posted_time therefore ");
        
        if($time_result >= 2) //its expired
        {
            $tele_id = $data[$key]['tele_id'];
            
            $text = "time has expired to complete transaction!!";
            $buttons = null;
            sendMessage($GLOBALS['message_url'], $tele_id, $text, $buttons);
            
            echo ("expired \n\n");
            $del_ref = $ref_node . '/' . $key;
            $deleting = $GLOBALS['database']->getReference($del_ref)->remove();
        }
        else{
            echo ("not expired \n\n");
            echo "<br>";
        }
                
    }    
}

function SearchFirebase($datafetched, $searching_for_data, $database_ref) //$sniffed_mac changes to $searching_for_data
{
    foreach ($datafetched as $pass) {
        if ($pass['mac'] == $searching_for_data) {
            //print('This is a valid mac address');

            //store the registered macs
            //array_push($registerd_macs,$searching_for_data);

            //or maybe lets already store it onto firebase already as a trip passenger
            $postdata_ = [
                'lat' => $pass['lat'],
                'lon' => $pass['lon'],
                'mac' => $pass['mac']
            ];
            $ref_ = 'trip_passengers';
            $post = $database_ref->getReference($ref_)->push($postdata_);

            //send the passengers prompts
            //get the passenger detail to start prompts:
            //here is the hard coded
            //$chatId = 1818711871; //sa number
            //$chatId = 1805318131; //zim number
            //here is the generic
            $chatId = $pass['tele_id'];
            $botkey = "1826631603:AAHCaw1052cv__Ley2L0rBDS1H0tmMZ4aSU";
            $message_url = "https://api.telegram.org/bot" . $botkey . "/sendMessage";
            $text = 'Welcome to taxi AA1!! Select one of the following options';
            $buttons = [["Travel"], ["Exit"]];
            sendMessage($message_url, $chatId, $text, $buttons);
        } else {
            print('this mac is not registered!!');
        }
    }
}

function sendMessage($url, $id, $message, $buttons = null)
{

    $data = array(
        'text' => $message,
        'chat_id' => $id
    );

    if ($buttons != null) {
        $data['reply_markup'] = [
            'keyboard' => $buttons,
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
            'parse_mode' => 'HTML',
            'selective' => true
        ];
    }

    $data_string = json_encode($data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        )
    );
    session_unset();

    curl_exec($ch);
    curl_close($ch);
}

function delete_pin($messageID, $chatID)
{

    $url = "https://api.telegram.org/bot1826631603:AAHCaw1052cv__Ley2L0rBDS1H0tmMZ4aSU/deleteMessage";

    $curl = curl_init();

    $fields = array(
        'chat_id' => $chatID,
        'message_id' => $messageID
    );

    $fields_string = http_build_query($fields);

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_string);

    $data = curl_exec($curl);

    curl_close($curl);
}

function startPrompts()
{
    $update = json_decode(file_get_contents("php://input"), true);
    $chatId = $update["message"]["chat"]["id"];
    $userName = $update["message"]["chat"]["first_name"];
    $message = $update["message"]["text"];
    $message_id =  $update["message"]["message_id"];
    
    // $id = 123;
    // $ref = "Registered_Agent";
    // $postdata = [
    //     'tele_id' => $chatId,
    //     'Balance' => $id,
    //     'Name' => 'style',
    //     'pin' => $id
    // ];
    // $post = $GLOBALS['database']->getReference($ref)->push($postdata);
    
    Check_identity($chatId, $message, $userName);

    if (($GLOBALS['registered_passenger_flagg'] == true) && ($GLOBALS['registered_driver_flagg'] == false))
    { 
        // $text = 'registered passenger: Sorry the Bot is going through development.';
        // $buttons = null;
        // sendMessage($GLOBALS['message_url'], $chatId, $text, $buttons);     
        
        Travelling_or_Paying($chatId);

        if (($GLOBALS['passenger_is_travelling_flagg'] == false) && ($GLOBALS['passenger_is_paying_flagg'] == false) && ($GLOBALS['passenger_correct_taxi_flagg'] == false) &&($GLOBALS['Check_Balance_Passenger']==false)&&($GLOBALS['Deleting_Account_Passenger']==false)) {

            // $text = "registered passenger: Sorry the Bot is going through development. You may encounter unexpected behavior \r\n\r\nRegistered passenger first options";
            // $buttons = null;
            // sendMessage($GLOBALS['message_url'], $chatId, $text, $buttons);     
    
            Registered_Passenger_First_Options($message, $userName, $chatId);
        }

        if (($GLOBALS['passenger_is_travelling_flagg'] == true) && ($GLOBALS['passenger_is_paying_flagg'] == false) && ($GLOBALS['passenger_correct_taxi_flagg'] == false) && ($GLOBALS['Check_Balance_Passenger']==false)&&($GLOBALS['Deleting_Account_Passenger']==false)) {
            //the travelling flag is is still up in database and therefore the passenger is confirmed as travlling
            //the travelling flag and paying flag are still down in database which means this is maybe the first text from passenger

            //condition to check text before proceeding
            if ($message == "Exit") {
                
                // $text = "registered passenger: Sorry the Bot is going through development. You may encounter unexpected behavior \r\n\r\nExit Selected after passenger selected travelling";
                // $buttons = null;
                // sendMessage($GLOBALS['message_url'], $chatId, $text, $buttons);     

                exiit($chatId);
            } else {
                
                // $text = "registered passenger: Sorry the Bot is going through development. You may encounter unexpected behavior \r\n\r\nCheck Taxi availability";
                // $buttons = null;
                // sendMessage($GLOBALS['message_url'], $chatId, $text, $buttons);     

                Check_Taxi_availability($message, $chatId);
            }
        }

        if (($GLOBALS['passenger_is_travelling_flagg'] == true) && ($GLOBALS['passenger_is_paying_flagg'] == false) && ($GLOBALS['passenger_correct_taxi_flagg'] == true) && ($GLOBALS['passenger_correct_stop_flagg'] == false) && ($GLOBALS['Check_Balance_Passenger']==false)&&($GLOBALS['Deleting_Account_Passenger']==false)) {
            //condition to check text before proceeding
            if ($message == "Exit") {

                // $text = "registered passenger: Sorry the Bot is going through development. You may encounter unexpected behavior \r\n\r\nExit Selected";
                // $buttons = null;
                // sendMessage($GLOBALS['message_url'], $chatId, $text, $buttons);     

                exiit($chatId);
            } else {
                // $text = "registered passenger: Sorry the Bot is going through development. You may encounter unexpected behavior \r\n\r\nCheck selected stop";
                // $buttons = null;
                // sendMessage($GLOBALS['message_url'], $chatId, $text, $buttons);     

                Check_Selected_Stop($message, $chatId);
            }
        }

        if (($GLOBALS['passenger_is_travelling_flagg'] == true) && ($GLOBALS['passenger_is_paying_flagg'] == false) && ($GLOBALS['passenger_correct_taxi_flagg'] == true) && ($GLOBALS['passenger_correct_stop_flagg'] == true) && ($GLOBALS['Check_Balance_Passenger']==false)&&($GLOBALS['Deleting_Account_Passenger']==false)) {
            
            // $text = "registered passenger: Sorry the Bot is going through development. You may encounter unexpected behavior \r\n\r\nPassenger gets the pay option";
            // $buttons = null;
            // sendMessage($GLOBALS['message_url'], $chatId, $text, $buttons);     
            
            $refi = "Wrong_taxi_id";
            clea_r_e_rror_count($chatId, $refi);
            pay_option($message, $userName, $chatId);
        }


        if (($GLOBALS['passenger_is_travelling_flagg'] == true) && ($GLOBALS['passenger_is_paying_flagg'] == true) && ($GLOBALS['passenger_correct_taxi_flagg'] == true) && ($GLOBALS['Check_Balance_Passenger'] == false)&&($GLOBALS['Deleting_Account_Passenger']==false)) {
            // //the paying flag is is still up in database and therefore the passenger is confirmed as wants to pay
            
            // $text = "registered passenger: Sorry the Bot is going through development. You may encounter unexpected behavior \r\n\r\nCheck the user pin";
            // $buttons = null;
            // sendMessage($GLOBALS['message_url'], $chatId, $text, $buttons);     
            
            $refi = "Wrong_pay_option";
            clea_r_e_rror_count($chatId, $refi);
            delete_pin($message_id, $chatId);
            Check_user_pin($message, $chatId);
        }
        
        if(($GLOBALS['Check_Balance_Passenger']==true)&&($GLOBALS['Deleting_Account_Passenger']==false))
        {
            if ($message == "Exit")
            {
                exiit($chatId);
            }
            
            else{
                delete_pin($message_id, $chatId);
                Show_Passenger_Bal($chatId,$message);
            }
            
        }
        if(($GLOBALS['Deleting_Account_Passenger']==true))
        {
            //$text = "Your Account will be deleted";
            delete_pin($message_id, $chatId);
            //$buttons = null;
            //sendMessage($GLOBALS['message_url'], $chatId, $text, $buttons);
            Check_User_Pin_for_Deleting($message, $chatId);
        }

    } 
    else if (($GLOBALS['registered_passenger_flagg'] == false) && ($GLOBALS['registered_driver_flagg'] == true)) {

        // $text = "Bot under development\r\n\r\nregistered driver";
        // $buttons = null;
        // sendMessage($GLOBALS['message_url'], $chatId, $text, $buttons);  
        
        DriverMenu($message, $userName, $chatId,$message_id);
    }

    else if(($GLOBALS['registered_passenger_flagg']==false)&&($GLOBALS['registered_driver_flagg']==false) && ($GLOBALS['registered_Agent_flagg'] == true))
    {
        // $text = "This is a registered mobile money agent";
        // $buttons = null;
        // sendMessage($GLOBALS['message_url'], $chatId, $text, $buttons);     
        
        Registered_Agent_Menu($chatId,$userName,$message,$message_id);
    }

    else if (($GLOBALS['registered_passenger_flagg'] == false) && ($GLOBALS['registered_driver_flagg'] == false) && ($GLOBALS['blocked_passenger_flagg'] == true)) {
        $text = "Contact admin to reclaim account";
        $buttons = null;
        sendMessage($GLOBALS['message_url'], $chatId, $text, $buttons);
    }
}

function Show_Passenger_Bal($p_id,$p_mes)
{
    $ref = "registered_passengers";
    $reg_pass_key = find_and_key($p_id, $ref);
    $data = $GLOBALS['database']->getReference($ref)->getValue();
    $pin = $data[$reg_pass_key]['pin'];
    
    if($p_mes == $pin)
    {
        $bal = $data[$reg_pass_key]['Balance'];
        $display_bal = number_format((float)$bal, 2, '.', '');
        
        $ref = 'Pass_Pin_Error';
        clea_r_e_rror_count($p_id, $ref);
        
        $ref = 'Passenger_Check_Balance';
        clea_r_e_rror_count($p_id, $ref);
        
        $text = "Your available balance is ZAR$display_bal";
        $buttons = [["Say Hi"]];
        sendMessage($GLOBALS['message_url'], $p_id, $text, $buttons);        
    }
    else{
        $text = "pin is incorrect";
        $buttons = null;
        sendMessage($GLOBALS['message_url'], $p_id, $text, $buttons);

        $error_ref = 'Pass_Pin_Error';
        $act_ref = "Blocked_Passengers";
        Check_Input_Error($p_id, $error_ref,$act_ref);        
    }
}

function Check_identity($identity, $txt, $user_name)
{
    //check if passenger is not blocked
    $blocked_passenger_ref = "Blocked_Passengers"; //reference to firebase node
    $blocked_passenger_node_data = $GLOBALS['database']->getReference($blocked_passenger_ref)->getValue();
    $blocked_passenger_node_data_child_keys = $GLOBALS['database']->getReference($blocked_passenger_ref)->getChildKeys();

    foreach ($blocked_passenger_node_data_child_keys as $blocked_passenger_key) {
        $blockeded_passenger_tele_id = $blocked_passenger_node_data[$blocked_passenger_key]['tele_id'];
        if ($identity == $blockeded_passenger_tele_id) {
            $text = "This account is blocked";
            $buttons = null;
            sendMessage($GLOBALS['message_url'], $identity, $text, $buttons);

            $GLOBALS['blocked_passenger_flagg'] = true;
            break;
        } else {
            $GLOBALS['blocked_passenger_flagg'] = false;
        }
    }

    if ($GLOBALS['blocked_passenger_flagg'] == false) {
        //check if passenger is registered
        $registered_passenger_ref = "registered_passengers"; //reference to firebase node
        $registered_passenger_node_data = $GLOBALS['database']->getReference($registered_passenger_ref)->getValue();
        $registered_passenger_node_data_child_keys = $GLOBALS['database']->getReference($registered_passenger_ref)->getChildKeys();

        foreach ($registered_passenger_node_data_child_keys as $registered_passenger_key) {
            $registered_passenger_tele_id = $registered_passenger_node_data[$registered_passenger_key]['tele_id'];
            if ($identity == $registered_passenger_tele_id) {
                $GLOBALS['registered_passenger_flagg'] = true;
                $GLOBALS['UNIVERSAL_registered_passenger_data'] = $registered_passenger_node_data[$registered_passenger_key];

                $GLOBALS['registered_passenger_key'] = $registered_passenger_key;
                break;
            } else {
                $GLOBALS['registered_passenger_flagg'] = false;
            }
        }

        if ($GLOBALS['registered_passenger_flagg'] == false) {
            $ref = "Drivers";
            $GLOBALS['registered_driver_flagg'] = find_and_flagg($identity, $ref);

            $ref = "Registered_Agent";
            $GLOBALS['registered_Agent_flagg'] = find_and_flagg($identity, $ref);
        }

        if (($GLOBALS['registered_passenger_flagg'] == false) && ($GLOBALS['registered_driver_flagg'] == false) && $GLOBALS['registered_Agent_flagg'] == false) {
            //$ref = "wants2register";
            //$passenger_wants_to_register_flagg = find_and_flagg($identity, $ref);
            Register_Options($identity, $user_name, $txt);
            
        
        // $text = 'Not rgistered passenger: Sorry the Bot is going through development if you experience weird things';
        // $buttons = null;
        // sendMessage($GLOBALS['message_url'], $identity, $text, $buttons);  
    }
    }
}

function Register_Options($i_dentity, $n_ame, $me_ssage)
{
    Wants_To_Register_As($i_dentity);

    if ($GLOBALS['register_as_passenger_flagg'] == true) {
        $m_length = strlen($me_ssage);

        if ((is_numeric($me_ssage)) && ($m_length == 4)) {
            $text = "the pin is a number with correct length";
            $buttons = [["Exit"]];
            sendMessage($GLOBALS['message_url'], $i_dentity, $text, $buttons);

            $new_user_pin = $me_ssage;
            $new_user_name = $n_ame;
            $postdata = [
                'name' => $new_user_name,
                'pin' => $new_user_pin,
                'tele_id' => $i_dentity,
                'Balance' => 1000
            ];
            $refreg_ = 'registered_passengers';
            $flagging = true;
            $post = $GLOBALS['database']->getReference($refreg_)->push($postdata);

            $text = "Done!! \n\rYou are now registered with pin $new_user_pin and balance of ZAR1000.00. \n\r\n\rPlease delete this message to avoid spying on your pin. In future use, the pin will be automatically deleted from screen as soon as you send it.";
            $buttons = [["Say Hi"]];
            sendMessage($GLOBALS['message_url'], $i_dentity, $text, $buttons);

            //exit and remove flags
            $error_ref = 'Registration_Error';
            clea_r_e_rror_count($i_dentity, $error_ref);

            $ref = 'wants2register';
            clea_r_e_rror_count($i_dentity, $ref);
        } else if (((!is_numeric($me_ssage)) || ($m_length != 4)) && ($me_ssage != "Exit")) {
            //pin is not correct
            $ref = "Registration_Error";
            $opt = "Register";
            e_rror_count($i_dentity, $ref, $opt);

            $f_lagg = find_and_flagg($i_dentity, $ref);
            if ($f_lagg == true) {
                $text = "Make sure the pin is a FOUR DIGITS pin or select Exit below.";
                $buttons = [["Exit"]];
                sendMessage($GLOBALS['message_url'], $i_dentity, $text, $buttons);
            } else {
                echo ("The errors have caused a restart");
            }
        } else if ($me_ssage == "Exit") {
            $error_ref = 'Registration_Error';
            clea_r_e_rror_count($i_dentity, $error_ref);

            $ref = 'wants2register';
            clea_r_e_rror_count($i_dentity, $ref);

            $text = "Just click \"Register\" if you need anything else. \r\n\r\nGoodbye!!!";
            $buttons = [["Register"]];
            sendMessage($GLOBALS['message_url'], $i_dentity, $text, $buttons);
        } else {
            $text = $me_ssage;
            $buttons = "Exit";
            sendMessage($GLOBALS['message_url'], $i_dentity, $text, $buttons);
        }
    } 
    
    elseif (($GLOBALS['register_as_passenger_flagg'] == false)&&($GLOBALS['register_as_driver_flagg']==false)&&($GLOBALS['register_as_agent_flagg']==false)) 
    {
        //passenger has not indicated that they want to reg
        if (($me_ssage == "Register As Passenger")) {
            //flag with the database that this id wants to register
            $regref = "wants2register";
            $regpostdata = [
                'tele_id' => $i_dentity
            ];
            $regpost = $GLOBALS['database']->getReference($regref)->push($regpostdata);

            $text = "Enter 4 digit pin of choice or select Exit below";
            $buttons = [["Exit"]];
            sendMessage($GLOBALS['message_url'], $i_dentity, $text, $buttons);
        }
        
        else if(($me_ssage == "Register As Driver"))
        {
            
            //flag with the database that this id wants to register
            $regref = "Driver_Wants_To_Register";
            $regpostdata = [
            'tele_id' => $i_dentity
            ];
            $regpost = $GLOBALS['database']->getReference($regref)->push($regpostdata);

            $text = "Enter 4 digits for taxi identity or select Exit below";
            $buttons = [["Exit"]];
            sendMessage($GLOBALS['message_url'], $i_dentity, $text, $buttons); 
    
            // $text = "Under development"; 
            // $buttons = [["Exit"]];
            // sendMessage($GLOBALS['message_url'], $i_dentity, $text, $buttons);            
        }
        
        else if(($me_ssage == "Register As Agent"))
        {
            $ref = "Agent_Want_To_Register";
            $postdata = [
                'tele_id' => $i_dentity,
            ];
            $post = $GLOBALS['database']->getReference($ref)->push($postdata);
    
            $text = "Enter a 4 digit pin of your choice"; 
            $buttons = [["Cancel Registration"]];
            sendMessage($GLOBALS['message_url'], $i_dentity, $text, $buttons);            
        }
        
        else if (($me_ssage == "Exit")) {
            //exit and remove flags
            $error_ref = 'Registration_Error';
            clea_r_e_rror_count($i_dentity, $error_ref);

            $ref = 'wants2register';
            clea_r_e_rror_count($i_dentity, $ref);
            
            $text = "Good bye!! ";
            $buttons = [['Say Hi']];
            sendMessage($GLOBALS['message_url'], $i_dentity, $text, $buttons);
        } 
        
        else { //if flagging is simply false with any other message
            $text = "Sorry, you are not registered!! \r\n\r\nSelect register from the options below if you want to use service"; // I will use the taxi id number here
            $buttons = [["Register As Passenger"],["Register As Driver"],["Register As Agent"], ["Exit"]];
            sendMessage($GLOBALS['message_url'], $i_dentity, $text, $buttons);
        }
    }
    
    else if(($GLOBALS['register_as_driver_flagg']==true)&&($GLOBALS['register_as_agent_flagg']==false))
    {
        Registering_driver_steps($i_dentity,$me_ssage,$m_length, $n_ame);
    }
    
    else if(($GLOBALS['register_as_agent_flagg']==true)&&($GLOBALS['register_as_driver_flagg']==false)&&($GLOBALS['register_as_passenger_flagg'] == false))
    {
        if(($me_ssage=="Cancel Registration")||($me_ssage=="Exit"))
        {
            Registering_Agent_Clear_Flag($i_dentity);
            
            $text = "Registration Cancelled"; // I will use the taxi id number here
            $buttons = [["Say Hi"]];
            sendMessage($GLOBALS['message_url'], $i_dentity, $text, $buttons);    
        }
        
        else{
            Registering_Agent($i_dentity,$me_ssage, $n_ame);
            // $text = "Registering as Agent under development"; // I will use the taxi id number here
            // $buttons = [["Exit"]];
            // sendMessage($GLOBALS['message_url'], $i_dentity, $text, $buttons);
        }

    }    
}

function Registering_Agent($registering_agent_i_dentity,$registering_agent_me_ssage, $registering_agent_n_ame)
{
    $m_length = strlen($registering_agent_me_ssage);
    
    if ((is_numeric($registering_agent_me_ssage)) && ($m_length == 4)) {
        $text = "the pin is a number with correct length";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $$registering_agent_i_dentity, $text, $buttons);

        $new_user_pin = $registering_agent_me_ssage;
        $new_user_name = $registering_agent_n_ame;
        $postdata = [
            'name' => $new_user_name,
            'pin' => $new_user_pin,
            'tele_id' => $registering_agent_i_dentity,
            'Balance' => 1000
        ];
        $refreg_ = 'Registered_Agent';
        $post = $GLOBALS['database']->getReference($refreg_)->push($postdata);

        $text = "Done!! \n\rYou are now a registered Agent with pin $new_user_pin and balance of ZAR1000.00. \n\r\n\rPlease delete this message to avoid spying on your pin. In future use, the pin will be automatically deleted from screen as soon as you send it.";
        $buttons = [["Say Hi"]];
        sendMessage($GLOBALS['message_url'], $registering_agent_i_dentity, $text, $buttons);

        //exit and remove flags
        Registering_Agent_Clear_Flag($registering_agent_i_dentity);
        
    } else if (((!is_numeric($registering_agent_me_ssage)) || ($m_length != 4)) && ($registering_agent_me_ssage != "Exit")) {
        //pin is not correct
        $ref = "Agent_Registration_Error";
        $opt = null;
        e_rror_count($registering_agent_i_dentity, $ref, $opt);

        $f_lagg = find_and_flagg($registering_agent_i_dentity, $ref);
        if ($f_lagg == true) {
            $text = "Make sure the pin is a FOUR DIGITS pin or select Exit below.";
            $buttons = [["Cancel Registration"]];
            sendMessage($GLOBALS['message_url'], $$registering_agent_i_dentity, $text, $buttons);
        } else {
            echo ("The errors have caused a restart");
        }
    }    
}

function Taxi_Id_Availability($registering_driver_message,$registering_driver_id)
{
    //call the database and check for the taxi id
    $drivers_taxis = "Drivers";
    $drivers_taxis_data = $GLOBALS['database']->getReference($drivers_taxis)->getValue(); //
    $taxis_data = $GLOBALS['database']->getReference($drivers_taxis)->getValue(); // 
    $drivers_taxis_data_child_keys = $GLOBALS['database']->getReference($drivers_taxis)->getChildKeys();

    foreach ($drivers_taxis_data_child_keys as $key) {
        $taxi_id = $drivers_taxis_data[$key]['taxi_id'];
        if ($taxi_id == $registering_driver_message) {
            $GLOBALS['taxi_id_already_registered'] = true;
            break;
        } else {
            //flag if taxi is not available 
            $GLOBALS['taxi_id_already_registered']=false;
        }
    }

    if ($GLOBALS['taxi_id_already_registered'] == true) {
        // $ref = "Driver_Registering_Entered_Unavailable_Taxi_ID";
        // $postdata = [
        //     'tele_id' => $registering_driver_id,
        //     'Count' => 1
        // ];
        // $post = $GLOBALS['database']->getReference($ref)->push($postdata);
        
        $ref = "Driver_Registering_Entered_Unavailable_Taxi_ID";
        $opt=null;
        e_rror_count($registering_driver_id, $ref, $opt);
        
        $text = "This taxi id is already in use. Try another one";
        $buttons = null;
        sendMessage($GLOBALS['message_url'], $registering_driver_id, $text, $buttons);          
        
    } elseif ($GLOBALS['taxi_id_already_registered']  == false) {
        
        $ref = "Driver_Registering_Entered_Available_Taxi_ID";
        $postdata = [
            'tele_id' => $registering_driver_id,
            'taxi_id' => $registering_driver_message
        ];
        $post = $GLOBALS['database']->getReference($ref)->push($postdata);
        
        $text = "The taxi id is available.\r\nNow enter your 4 digit pin of choice.";
        $buttons = [["Cancel Registration"]];
        sendMessage($GLOBALS['message_url'], $registering_driver_id, $text, $buttons);

    }

}

function Wants_To_Register_As($registering_identity)
{
    $ref = "wants2register";
    $GLOBALS['register_as_passenger_flagg'] = find_and_flagg($registering_identity, $ref);
    
    $ref = "Driver_Wants_To_Register";
    $GLOBALS['register_as_driver_flagg'] = find_and_flagg($registering_identity, $ref);
    
    $ref = "Agent_Want_To_Register";
    $GLOBALS['register_as_agent_flagg'] = find_and_flagg($registering_identity, $ref);
    
}

function Registering_Agent_Clear_Flag($registering_agent_identity)
{
    $ref = 'Agent_Want_To_Register';
    clea_r_e_rror_count($registering_agent_identity, $ref);  
    
    $ref = 'Agent_Registration_Error';
    clea_r_e_rror_count($registering_agent_identity, $ref);  

}

function Registering_Driver_Clear_Flag($registering_driver_identity)
{
    $ref = 'Driver_Wants_To_Register';
    clea_r_e_rror_count($registering_driver_identity, $ref);   
    
    $ref = 'Driver_Registering_Entered_Unavailable_Taxi_ID';
    clea_r_e_rror_count($registering_driver_identity, $ref);  
    
    $ref = 'Driver_Registering_Entered_Wrong_ID_Format';
    clea_r_e_rror_count($registering_driver_identity, $ref);  
    
    $ref='Driver_Registering_Entered_Available_Taxi_ID';
    clea_r_e_rror_count($registering_driver_identity, $ref);  
    
    $ref = "Driver_Registration_Pin_Error";
    clea_r_e_rror_count($registering_driver_identity, $ref);  
}

function Registering_driver_steps($reg_driver_i_dentity, $reg_driver_me_ssage, $reg_driver_m_length, $reg_driver_n_ame)
{
    $ref = "Driver_Registering_Entered_Available_Taxi_ID";
    $GLOBALS['Driver_Entered_Correct_Taxi_Id'] = find_and_flagg($reg_driver_i_dentity, $ref);
    
    $reg_driver_m_length = strlen($reg_driver_me_ssage);
    
    if($GLOBALS['Driver_Entered_Correct_Taxi_Id']==false)
    {
        if ((is_numeric($reg_driver_me_ssage)) && ($reg_driver_m_length == 4)) 
        {
            Taxi_Id_Availability($reg_driver_me_ssage,$reg_driver_i_dentity); //check if taxi id is not used by someone else
        }
        
        else if (((!is_numeric($reg_driver_me_ssage)) || ($reg_driver_m_length != 4)) && ($reg_driver_me_ssage != "Exit")) 
        {
            //the taxi id has not 4 digits
            $ref = "Driver_Registering_Entered_Wrong_ID_Format";
            $opt = null;
            e_rror_count($reg_driver_i_dentity, $ref, $opt);

            $f_lagg = find_and_flagg($reg_driver_i_dentity, $ref);
            if ($f_lagg == true) {
                $text = "Make sure the taxi id is a FOUR DIGITS id or select Exit below.";
                $buttons = [["Exit"]];
                sendMessage($GLOBALS['message_url'], $reg_driver_i_dentity, $text, $buttons);
            } else {
                echo ("The errors have caused a restart");
            }
        }
        
        else if($reg_driver_me_ssage == "Exit")
        {
            Registering_Driver_Clear_Flag($reg_driver_i_dentity);
            
            $text = "The registration has been cancelled";
            $buttons = [["Say Hi"]];
            sendMessage($GLOBALS['message_url'], $reg_driver_i_dentity, $text, $buttons);    
        }    
    }
    
    else if($GLOBALS['Driver_Entered_Correct_Taxi_Id']==true)
    {
        
        if(($reg_driver_me_ssage=="Exit")||($reg_driver_me_ssage=="Cancel Registration"))
        {
            //clear all error flags
            Registering_Driver_Clear_Flag($reg_driver_i_dentity);
            
            $text = "Your registration has been cancelled!!";
            $buttons = [["Say Hi"]];
            sendMessage($GLOBALS['message_url'], $reg_driver_i_dentity, $text, $buttons);
        }        
    
        else if ((is_numeric($reg_driver_me_ssage)) && ($reg_driver_m_length == 4)) {
  
            
            //lets fetch the taxi id entered previously
        
            $ref = "Driver_Registering_Entered_Available_Taxi_ID";
            $ke_y = find_and_key($pass_Id, $ref);
            $data = $GLOBALS['database']->getReference($ref)->getValue();
            $ke_y = find_and_key($reg_driver_i_dentity, $ref);
            $taxi_number = $data[$ke_y]['taxi_id'];
        
            // $text = "the pin is a number with correct length";
            // //$buttons = [["Say Hi"]];
            // sendMessage($GLOBALS['message_url'], $reg_driver_i_dentity, $text, $buttons);

            $new_user_pin = $reg_driver_me_ssage;
            $new_user_name = $reg_driver_n_ame;
            $postdata = [
                'name' => $new_user_name,
                'pin' => $new_user_pin,
                'tele_id' => $reg_driver_i_dentity,
                'taxi_id' => $taxi_number,
                'Balance' => 10
            ];
            $refreg_ = 'Drivers';
            
            $post = $GLOBALS['database']->getReference($refreg_)->push($postdata);

            $text = "Done!! \n\rYou are now a registered driver with taxi id $taxi_number, pin $new_user_pin and balance of ZAR10.00. \n\r\n\rPlease delete this message to avoid spying on your pin. In future use, the pin will be automatically deleted from screen as soon as you send it.";
            $buttons = [["Say Hi"]];
            sendMessage($GLOBALS['message_url'], $reg_driver_i_dentity, $text, $buttons);

            //exit and remove flags
            Registering_Driver_Clear_Flag($reg_driver_i_dentity);
        }
        
        else if (((!is_numeric($reg_driver_me_ssage)) || ($reg_driver_m_length != 4)) && ($reg_driver_me_ssage != "Exit")) {
            //pin is not correct
            $ref = "Driver_Registration_Pin_Error";
            $opt=null;
            e_rror_count($reg_driver_i_dentity, $ref, $opt);

            $f_lagg = find_and_flagg($reg_driver_i_dentity, $ref);
            if ($f_lagg == true) {
                $text = "Retry, make sure the pin is a FOUR DIGITS pin or select Exit below.";
                $buttons = [["Cancel Registration"]];
                sendMessage($GLOBALS['message_url'], $reg_driver_i_dentity, $text, $buttons);
            } else {
                echo ("The errors have caused a restart");
            }
        }

    }
}

function Travelling_or_Paying($passenger_tele_id)
{
    
    echo ("hello travel function");
    echo ("\r\n");

    //search if pass wants to travel
    $registered_passenger_wants2travell_database_node = "wants2travel";
    $passengers_want2travel_data =  $GLOBALS['database']->getReference($registered_passenger_wants2travell_database_node)->getValue();
    $passengers_want2travel_data_keys = $GLOBALS['database']->getReference($registered_passenger_wants2travell_database_node)->getChildKeys();

    //this loop checks if the passenger wants to travel
    $i_travelling = 0;
    if ($passengers_want2travel_data > 0) {
        foreach ($passengers_want2travel_data as $passenger_wants2travel) {
            if ($passenger_wants2travel['tele_id'] == $passenger_tele_id) {
                $GLOBALS['passenger_is_travelling_flagg'] = true;
                break;
            } else {
                $GLOBALS['passenger_is_travelling_flagg'] = false;
            }
            $i_travelling = $i_travelling + 1;
        }
    }

    $GLOBALS['travelling_pass_indicator_key'] = $passengers_want2travel_data_keys[$i_travelling]; //this the key to the indicator which i will want to delete after passenger indicates that they want to pay

    //search if passenger has selected the right taxi
    $correct_taxi = "Selected_Correct_Taxi";
    $correct_taxi_data = $GLOBALS['database']->getReference($correct_taxi)->getValue();
    $correct_taxi_data_keys = $GLOBALS['database']->getReference($correct_taxi)->getChildKeys();

    //this loop checks if the passenger wants to travel
    $i_correct = 0;
    if ($correct_taxi_data > 0) {
        foreach ($correct_taxi_data as $passenger_correct) {
            if ($passenger_correct['tele_id'] == $passenger_tele_id) {
                $GLOBALS['passenger_correct_taxi_flagg'] = true;
                break;
            } else {
                $GLOBALS['passenger_correct_taxi_flagg'] = false;
            }
            $i_correct = $i_correct + 1;
        }
    }

    $GLOBALS['pass_correct_taxi_indicator_key'] = $correct_taxi_data_keys[$i_correct]; //this the key to the indicator which i will want to delete after passenger indicates that they want to pay

    //search if the passenger has indicated if they want to pay
    $registered_passenger_wants2pay = "wants2pay";
    $passengers_want2pay_data = $GLOBALS['database']->getReference($registered_passenger_wants2pay)->getValue();
    $passengers_want2pay_data_keys = $GLOBALS['database']->getReference($registered_passenger_wants2pay)->getChildKeys();

    //check if passenger has indicated desire to pay
    $i_paying = 0;
    foreach ($passengers_want2pay_data as $passenger_wants2pay) {

        if ($passenger_wants2pay['tele_id'] == $passenger_tele_id) {

            $GLOBALS['passenger_is_paying_flagg'] = true;
            break;
        } else {
            $GLOBALS['passenger_is_paying_flagg'] = false;
        }
        $i_paying = $i_paying + 1;
    }
    $GLOBALS['paying_pass_indicator_key'] = $passengers_want2pay_data_keys[$i_paying]; //this the key to the indicator which i will want to delete after pin is correct and pass has paid        

    $ref = "Selected_Stop";
    $GLOBALS['passenger_correct_stop_flagg'] = find_and_flagg($passenger_tele_id, $ref);
    
    $ref = "Passenger_Check_Balance";
    $GLOBALS['Check_Balance_Passenger'] = find_and_flagg($passenger_tele_id, $ref);
    
    $ref = "Passenger_Deleting_Account";
    $GLOBALS['Deleting_Account_Passenger'] = find_and_flagg($passenger_tele_id, $ref);    
}

function exiit($ID)
{
    clear_flaggs($ID);
    $text = "Just say \"Hi\" if you need anything else. \r\n\r\nGoodbye!!!";
    $buttons = [["Say Hi"]];
    sendMessage($GLOBALS['message_url'], $ID, $text, $buttons);
}

function clear_flaggs($passenger_identity)
{
    $ref = 'Subtraction_amount';
    clea_r_e_rror_count($passenger_identity, $ref);

    $ref = 'wants2pay';
    clea_r_e_rror_count($passenger_identity, $ref);

    $ref = 'wants2travel';
    clea_r_e_rror_count($passenger_identity, $ref);

    $ref = 'Selected_Correct_Taxi';
    clea_r_e_rror_count($passenger_identity, $ref);

    $ref = 'Wrong_taxi_id';
    clea_r_e_rror_count($passenger_identity, $ref);

    $ref = "Wrong_pay_option";
    clea_r_e_rror_count($passenger_identity, $ref);

    $ref = "Selected_Stop";
    clea_r_e_rror_count($passenger_identity, $ref);

    $ref = "Selected_Stop_Error";
    clea_r_e_rror_count($passenger_identity, $ref);

    $ref = "Pay_Error";
    clea_r_e_rror_count($passenger_identity, $ref);

    $ref = 'Registration_Error';
    clea_r_e_rror_count($passenger_identity, $ref);

    $ref = 'wants2register';
    clea_r_e_rror_count($passenger_identity, $ref);
    
    $ref = 'Passenger_Check_Balance';
    clea_r_e_rror_count($passenger_identity, $ref);
    
    $ref = 'Passenger_Deleting_Account';
    clea_r_e_rror_count($passenger_identity, $ref);
}

function Agent_Clear_Flaggs($A_id)
{
    $ref = 'Agent_Check_Balance';
    clea_r_e_rror_count($A_id, $ref);
    
    $ref = 'Agent_Send_Money';
    clea_r_e_rror_count($A_id, $ref);    

    $ref = "Agent_and_Passenger";
    clea_r_e_rror_count($A_id, $ref);  

    $ref="Agent_Send_Amount";
    clea_r_e_rror_count($A_id, $ref);  
    
    $ref="Agent_Confirmation_Code_Error";
    clea_r_e_rror_count($A_id, $ref); 
    
    $ref="Agent_Check_Amount_Error";
    clea_r_e_rror_count($A_id, $ref); 

    $ref="Agent_Fuel_Sell";
    clea_r_e_rror_count($A_id, $ref); 

}

function find_and_flagg($i_dentity, $ref_node)
{
    //this function finds and identity at a specific location and returns true if found else false

    $data =  $GLOBALS['database']->getReference($ref_node)->getValue();
    $data_keys = $GLOBALS['database']->getReference($ref_node)->getChildKeys();

    if ($data > 0) {
        foreach ($data as $u_ser) {
            if ($u_ser['tele_id'] == $i_dentity) {
                $find_flagg = true;
                break;
            } else {
                $find_flagg = false;
            }
        }
    }

    if ($find_flagg == true) {
        return true;
    } else {
        return false;
    }
}

function find_and_key_2($idd, $i_dentity, $ref_node, $var)
{
    $data =  $GLOBALS['database']->getReference($ref_node)->getValue();
    $child_keys = $GLOBALS['database']->getReference($ref_node)->getChildKeys();

    foreach ($child_keys as $key) {
        $u_ser = $data[$key][$var];
        if ($i_dentity == $u_ser) {
            return $key;
        } else {
            echo ("Not the same");
        }
    }
}

function find_and_key($i_dentity, $ref_node)
{
    $data =  $GLOBALS['database']->getReference($ref_node)->getValue();
    $child_keys = $GLOBALS['database']->getReference($ref_node)->getChildKeys();

    foreach ($child_keys as $key) {
        $u_ser = $data[$key]['tele_id'];
        if ($i_dentity == $u_ser) {
            return $key;
        } else {
            echo ("Not the same");
        }
    }
}

function clea_r_e_rror_count($passenger_identity, $reference_node)
{
    $node_data = $GLOBALS['database']->getReference($reference_node)->getValue();
    $node_data_child_keys = $GLOBALS['database']->getReference($reference_node)->getChildKeys();

    foreach ($node_data_child_keys as $passenger_key) {
        $passenger_tele_id = $node_data[$passenger_key]['tele_id'];
        if ($passenger_identity == $passenger_tele_id) {
            $del_ref = $reference_node . '/' . $passenger_key;
            $deleting = $GLOBALS['database']->getReference($del_ref)->remove();

            break;
        } else {
            echo ('The identity is not found');
        }
    }
}

function Registered_Passenger_First_Options($passenger_text, $passenger_name, $telegram_identity)
{
    if ($passenger_text == 'Travel') {
        //flag this event into dB to enable just the taxi id entering and not the old extracting route
        $database_wants2travel_ref_node = "wants2travel";
        $wants2travel_postdata = [
            'tele_id' => $telegram_identity
        ];
        $wants2travel_post_response = $GLOBALS['database']->getReference($database_wants2travel_ref_node)->push($wants2travel_postdata);
        //let's give the passenger the options
        Available_Taxis_Option($telegram_identity);
    } 

    else if (($passenger_text == "Exit")||($passenger_text == "Cancel")) {
        exiit($telegram_identity);
    } 

    else if ($passenger_text == "Top Up Wallet") {

        $trasaction_code = bin2hex(random_bytes(4));
            
        date_default_timezone_set('Africa/Johannesburg');
        $d = date('y-m-d');
        $t = date('G:i:s');
        $time = $d.",".$t;

        $ref_node = "Passenger_Top_Up";
        $postdata = [
            'tele_id' => $telegram_identity,
            'pass_name' => $passenger_name,
            'time' => $time,
            'transaction_code' => $trasaction_code
        ];
        $wants2travel_post_response = $GLOBALS['database']->getReference($ref_node)->push($postdata);
        
        $text = "Your transaction code valid for 2 minutes is $trasaction_code \r\n Select exit to cancel transaction.";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);
    }
    else if($passenger_text == "Check Balance")
    {
        $text = "Enter your pin to check the balance";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);       
        
        //flag into database
        $ref = "Passenger_Check_Balance";
        $postdata = [ 
            'tele_id' => $telegram_identity
        ];

        $post = $GLOBALS['database']->getReference($ref)->push($postdata);  
    }
    
    else if($passenger_text == "Delete Account")
    {
        $text = "Enter your pin to confirm delete";
        $buttons = [["Cancel"]];
        sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);       
        
        //flag into database
        $ref = "Passenger_Deleting_Account";
        $postdata = [ 
            'tele_id' => $telegram_identity
        ];

        $post = $GLOBALS['database']->getReference($ref)->push($postdata);  
    }
    else {
        $text = "Hi $passenger_name, Select one of the following options from your keyboard: \r\n";
        $buttons = [["Travel"],["Check Balance"],["Top Up Wallet"],["Exit"],["Delete Account"]];
        sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);
    }
}

function Available_Taxis_Option($passenger_id)
{
    $ref = "travelling_taxis";
    $data =  $GLOBALS['database']->getReference($ref)->getValue();
    $child_keys = $GLOBALS['database']->getReference($ref)->getChildKeys();

    $text = "Select your taxi id number from the list below:\r\n \r\n";
    foreach ($child_keys as $key) {

        $i_d = $data[$key]['taxi_id'];
        $text .= "Taxi number: $i_d\r\n";

        $number_of_trips = $data[$key]['count'];
        if ($number_of_trips == 1) {
            $money = $data[$key][1]['Amount'];
            $place = $data[$key][1]['Destination'];

            $text .= "To $place, for ZAR $money\r\n \r\n";
        } else if ($number_of_trips > 1) {
            for ($counter = 1; $counter <= $number_of_trips; $counter++) {
                $money = $data[$key][$counter]['Amount'];
                $place = $data[$key][$counter]['Destination'];

                $text .= "To $place, for ZAR $money";
                $text .= "\n";
            }
            $text .= "\n";
        }
    }
    $buttons = [["Exit"]];
    sendMessage($GLOBALS['message_url'], $passenger_id, $text, $buttons);
}

function Check_Taxi_availability($passenger_message, $passenger_chatId)
{
    //call the database and check for the taxi id
    $traveling_taxis = "travelling_taxis";
    $traveling_taxis_data = $GLOBALS['database']->getReference($traveling_taxis)->getValue(); //
    $taxis_data = $GLOBALS['database']->getReference($traveling_taxis)->getValue(); // 
    $traveling_taxis_data_child_keys = $GLOBALS['database']->getReference($traveling_taxis)->getChildKeys();

    foreach ($traveling_taxis_data_child_keys as $key) {
        $taxi_id = $traveling_taxis_data[$key]['taxi_id'];
        $num_stops = $traveling_taxis_data[$key]['count'];
        if ($taxi_id == $passenger_message) {
            //flag if the taxi is available
            $available_taxi_flagg = true;
            break;
        } else {
            //flag if taxi is not available 
            $available_taxi_flagg = false;
        }
    }

    if ($available_taxi_flagg == true) {
        //now we have a flagg to indicate that the pass has selected a correct taxi. 
        //this will allow for us to give a pay option using this flag
        $sel_ref = "Selected_Correct_Taxi";
        $sel_postdata = [
            'tele_id' => $passenger_chatId,
            'taxi_id' => $passenger_message
        ];
        $sel_post = $GLOBALS['database']->getReference($sel_ref)->push($sel_postdata);

        Available_stops($passenger_chatId, $key, $num_stops, $taxis_data);
    } elseif ($available_taxi_flagg == false) {
        //this taxi is going to kayamandi and the cost is ZAR13. Select pay option if you wish to pay or exit 
        $text = "No such taxi found!!";
        $buttons = null;
        sendMessage($GLOBALS['message_url'], $passenger_chatId, $text, $buttons);

        //i will want to count how many time a wrong taxi id is entered and restart prompts
        $refi = "Wrong_taxi_id";
        $opt = "Travel";
        e_rror_count($passenger_chatId, $refi, $opt);
    }
}

function Available_stops($p_id, $taxi_key, $stops, $traveling_taxis_data)
{
    $text = "Select your destination from list below:";
    //$round_amount = number_format((float)$amount, 2, '.', '');

    if ($stops == 1) {
        $stop1_amount = $traveling_taxis_data[$taxi_key][1]['Amount'];
        $stop1_destination = $traveling_taxis_data[$taxi_key][1]['Destination'];
        $one .= "To " . $stop1_destination . " for ZAR" . $stop1_amount;
        $buttons = [[$one], ["Exit"]];
    }

    if ($stops == 2) {
        $stop1_amount = $traveling_taxis_data[$taxi_key][1]['Amount'];
        $stop1_destination = $traveling_taxis_data[$taxi_key][1]['Destination'];
        $o_ne .= "To " . $stop1_destination . " for ZAR" . $stop1_amount;

        $stop2_amount = $traveling_taxis_data[$taxi_key][2]['Amount'];
        $stop2_destination = $traveling_taxis_data[$taxi_key][2]['Destination'];
        $t_wo .= "To " . $stop2_destination . " for ZAR" . $stop2_amount;

        $buttons = [[$o_ne], [$t_wo], ["Exit"]];
    }

    if ($stops == 3) {
        $stop1_amount = $traveling_taxis_data[$taxi_key][1]['Amount'];
        $stop1_destination = $traveling_taxis_data[$taxi_key][1]['Destination'];
        $o_ne .= "To " . $stop1_destination . " for ZAR" . $stop1_amount;

        $stop2_amount = $traveling_taxis_data[$taxi_key][2]['Amount'];
        $stop2_destination = $traveling_taxis_data[$taxi_key][2]['Destination'];
        $t_wo .= "To " . $stop2_destination . " for ZAR" . $stop2_amount;

        $stop3_amount = $traveling_taxis_data[$taxi_key][3]['Amount'];
        $stop3_destination = $traveling_taxis_data[$taxi_key][3]['Destination'];
        $t_hree .= "To " . $stop3_destination . " for ZAR" . $stop3_amount;

        $buttons = [[$o_ne], [$t_wo], [$t_hree], ["Exit"]];
    }

    if ($stops == 4) {
        $stop1_amount = $traveling_taxis_data[$taxi_key][1]['Amount'];
        $stop1_destination = $traveling_taxis_data[$taxi_key][1]['Destination'];
        $o_ne .= "To " . $stop1_destination . " for ZAR" . $stop1_amount;

        $stop2_amount = $traveling_taxis_data[$taxi_key][2]['Amount'];
        $stop2_destination = $traveling_taxis_data[$taxi_key][2]['Destination'];
        $t_wo .= "To " . $stop2_destination . " for ZAR" . $stop2_amount;

        $stop3_amount = $traveling_taxis_data[$taxi_key][3]['Amount'];
        $stop3_destination = $traveling_taxis_data[$taxi_key][3]['Destination'];
        $t_hree .= "To " . $stop3_destination . " for ZAR" . $stop3_amount;

        $stop4_amount = $traveling_taxis_data[$taxi_key][4]['Amount'];
        $stop4_destination = $traveling_taxis_data[$taxi_key][4]['Destination'];
        $f_our .= "To " . $stop4_destination . " for ZAR" . $stop4_amount;

        $buttons = [[$o_ne], [$t_wo], [$t_hree], [$f_our], ["Exit"]];
    }

    if ($stops == 5) {
        $stop1_amount = $traveling_taxis_data[$taxi_key][1]['Amount'];
        $stop1_destination = $traveling_taxis_data[$taxi_key][1]['Destination'];
        $o_ne .= "To " . $stop1_destination . " for ZAR" . $stop1_amount;

        $stop2_amount = $traveling_taxis_data[$taxi_key][2]['Amount'];
        $stop2_destination = $traveling_taxis_data[$taxi_key][2]['Destination'];
        $t_wo .= "To " . $stop2_destination . " for ZAR" . $stop2_amount;

        $stop3_amount = $traveling_taxis_data[$taxi_key][3]['Amount'];
        $stop3_destination = $traveling_taxis_data[$taxi_key][3]['Destination'];
        $t_hree .= "To " . $stop3_destination . " for ZAR" . $stop3_amount;

        $stop4_amount = $traveling_taxis_data[$taxi_key][4]['Amount'];
        $stop4_destination = $traveling_taxis_data[$taxi_key][4]['Destination'];
        $f_our .= "To " . $stop4_destination . " for ZAR" . $stop4_amount;

        $stop5_amount = $traveling_taxis_data[$taxi_key][5]['Amount'];
        $stop5_destination = $traveling_taxis_data[$taxi_key][5]['Destination'];
        $f_ive .= "To " . $stop5_destination . " for ZAR" . $stop5_amount;

        $buttons = [[$o_ne], [$t_wo], [$t_hree], [$f_our], [$f_ive], ["Exit"]];
    }
    sendMessage($GLOBALS['message_url'], $p_id, $text, $buttons);
}

function e_rror_count($identity, $reference_node, $option)
{
    //this will get the database node to search the number of errors recorded yet
    $node_data =  $GLOBALS['database']->getReference($reference_node)->getValue();
    $node_data_keys = $GLOBALS['database']->getReference($reference_node)->getChildKeys();

    foreach ($node_data_keys as $passenger_error_key) {
        $passenger_tele_id = $node_data[$passenger_error_key]['tele_id'];

        if ($identity == $passenger_tele_id) {
            $passenger_has_made_error_before = true;
            $number_of_errors = $node_data[$passenger_error_key]['count'];
            break;
        } else if (($identity != $passenger_tele_id)) {
            $passenger_has_made_error_before = false;
        }
    }

    if ($passenger_has_made_error_before == true) {
        $error_times =  $number_of_errors;

        if ($error_times >= 2) {
            $text = "Too many errors. Lets restart";
            $buttons = [["Say Hi"]];
            sendMessage($GLOBALS['message_url'], $identity, $text, $buttons);

            $text = "App restarting options. Please reselect one of the following options from your keyboard:\r\n \r\n$option \r\nExit";
            $buttons = [[$option], ["Exit"]];
            sendMessage($GLOBALS['message_url'], $identity, $text, $buttons);

            //the limit for mistakes has been reached, restart
            clear_flaggs($identity);
            Agent_Clear_Flaggs($identity);
            Registering_Driver_Clear_Flag($identity);
            Registering_Agent_Clear_Flag($identity);
        }

        if ($error_times < 2) {
            $text = 'Please try again. Make sure your input is correct';
            $buttons = null;
            sendMessage($GLOBALS['message_url'], $identity, $text, $buttons);

            $count = $error_times + 1;
            $ref = $reference_node . '/' . $passenger_error_key;

            $data = [
                'tele_id' => $identity,
                'count' => $count
            ];
            $post_response = $GLOBALS['database']->getReference($ref)->update($data);
        }
    } elseif ($passenger_has_made_error_before == false) {
        $data = [
            'tele_id' => $identity,
            'count' => 1
        ];
        $post_response = $GLOBALS['database']->getReference($reference_node)->push($data);

        $text = 'please try again';
        $buttons = null;
        sendMessage($GLOBALS['message_url'], $identity, $text, $buttons);
    }
}

function Check_Selected_Stop($message, $pass_Id)
{
    $ref = 'Selected_Correct_Taxi';
    $ke_y = find_and_key($pass_Id, $ref);
    $data = $GLOBALS['database']->getReference($ref)->getValue();
    $ke_y = find_and_key($pass_Id, $ref);
    $taxi_number = $data[$ke_y]['taxi_id'];

    $ref = 'travelling_taxis';
    $variable = "taxi_id";
    $ke_y2 = find_and_key_2($pass_Id, $taxi_number, $ref, $variable);
    $data2 = $GLOBALS['database']->getReference($ref)->getValue();
    $trips_number = $data2[$ke_y2]['count'];
    
    // $text = "Driver tele_id is: $driver_of_taxi";
    // $buttons = null;
    // sendMessage($GLOBALS['message_url'], $pass_Id, $text, $buttons);

    for ($count = 1; $count <= $trips_number; $count++) {
        $reco_rd = null;
        $destination = $data2[$ke_y2][$count]['Destination'];
        $amount = $data2[$ke_y2][$count]['Amount'];
        $reco_rd .= "To " . $destination . " for ZAR" . $amount;

        if ($message == $reco_rd) {
            $message_and_record = true;
            $driver_of_taxi = $data2[$ke_y2]['tele_id'];
            break;
        } else {
            $message_and_record = false;
            echo ("what pass said does not match with anywhere the taxi is going");
            //we may want to have a more robust handling of this error
            //this will include the number of times of errors to rrestart etc
        }
    }

    if ($message_and_record == true) {
        //store the amount to be deducted to database because there is current way to get this value to the section where the pin is happening
        $amountpostdata_ = [
            'tele_id' => $pass_Id,
            'taxi_id' => $taxi_number,
            'Destination' => $destination,
            'Driver_id' => $driver_of_taxi,
            'Trip_amount' => $amount
        ];
        $sub_ref_amount = 'Subtraction_amount';
        $amount_post = $GLOBALS['database']->getReference($sub_ref_amount)->push($amountpostdata_);

        $ref = "Selected_Stop";
        $postdata = [
            'tele_id' => $pass_Id
        ];
        $post = $GLOBALS['database']->getReference($ref)->push($postdata);

        //this taxi is going to kayamandi and the cost is ZAR13. Select pay option if you wish to pay or exit 
        $round_amount = number_format((float)$amount, 2, '.', '');
        $text = "This taxi is going to $destination and the cost is ZAR $round_amount. Select pay option if you wish to Pay otherwise select Exit";
        $buttons = [["Pay"], ["Exit"]];
        sendMessage($GLOBALS['message_url'], $pass_Id, $text, $buttons);
    } else {
        $ref = "Selected_Stop_Error";
        $opt = "Travel";
        e_rror_count($pass_Id, $ref, $opt);
    }
}

function pay_option($passenger_text, $passenger_name, $telegram_identity)
{
    if ($passenger_text == "Pay") {
        //the moment pay is entered, we flag this event into the dB
        //we register (into dB) the tele_id as wanting to pay so that we fetch that id from a specific node from the db and use it to search for the pin 
        //this will eliminate the use of the key word
        //the node will be deleted after confirmation of payment

        $payref = "wants2pay";
        $paypostdata = [
            'tele_id' => $telegram_identity
        ];
        $paypost = $GLOBALS['database']->getReference($payref)->push($paypostdata);

        $text = "Enter your pin";
        //$buttons = null;
        sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);
    } else if ($passenger_text == "Exit") {
        exiit($telegram_identity);
    } else {
        $reff = "Wrong_pay_option";
        $opt = "Travel";
        e_rror_count($telegram_identity, $reff, $opt);
    }
}

function Check_User_Pin_for_Deleting($passenger_message, $passenger_chatId)
{
    if ($passenger_message == $GLOBALS['UNIVERSAL_registered_passenger_data']['pin'])
    {
        $ref = 'User_Deleting_Pin_Error';
        clea_r_e_rror_count($passenger_chatId, $ref);
        
        $ref = "Passenger_Deleting_Account";
        clea_r_e_rror_count($passenger_chatId, $ref);
        
        $ref = "registered_passengers";
        $ke_y = find_and_key($passenger_chatId, $ref);	
    	$del_ref = $ref . '/' . $ke_y;
    	
        $deleting = $GLOBALS['database']->getReference($del_ref)->remove();       
    
        $text = "Account has been deleted!! \r\n\r\n";
        $buttons = [['Say Hi']];
        sendMessage($GLOBALS['message_url'], $passenger_chatId, $text, $buttons);            
    }
    
    elseif ($passenger_message != $GLOBALS['UNIVERSAL_registered_passenger_data']['pin']) {
        //pin is not corerect
        $text = "pin is incorrect";
        //$buttons = null;
        sendMessage($GLOBALS['message_url'], $passenger_chatId, $text, $buttons);

        $error_ref = 'User_Deleting_Pin_Error';
        $act_ref = "Blocked_Passengers";
        Check_Input_Error($passenger_chatId, $error_ref,$act_ref);
    }
}

function Check_user_pin($passenger_message, $passenger_chatId)
{
    if ($passenger_message == $GLOBALS['UNIVERSAL_registered_passenger_data']['pin']) {

        //collect variables to the trip i.e destination and fare amount for this I will need keys to the travelling taxis and the fetcheddata
        //search if pass wants to travel
        $amount_ref = "Subtraction_amount";
        $amount_fetchedata =  $GLOBALS['database']->getReference($amount_ref)->getValue();
        $amount_child_keys = $GLOBALS['database']->getReference($amount_ref)->getChildKeys();

        $i_amount = 0;
        foreach ($amount_fetchedata as $amount_to_be_paid) {

            if ($amount_to_be_paid['tele_id'] == $passenger_chatId) {

                $fare_amount = $amount_to_be_paid['Trip_amount'];
                $destination_ = $amount_to_be_paid['Destination'];
                $taxi_identity = $amount_to_be_paid['taxi_id'];
                break;
            } else {
                echo ("identity not found");
            }
            $i_amount = $i_amount + 1;
        }
        $amount_indicator_key = $amount_child_keys[$i_amount]; //this the key to the indicator which i will want to delete after passenger pays their amount

        clear_flaggs($passenger_chatId);

        //lets do the maths to subtract from the available balance
        $passenger_balance = $GLOBALS['UNIVERSAL_registered_passenger_data']['Balance'];

        if ($passenger_balance > $fare_amount) {

            //lets delete the subtraction_amount node
            $amount_del_ref = 'Subtraction_amount/' . $amount_indicator_key;
            $sub_Amount_del = $GLOBALS['database']->getReference($amount_del_ref)->remove();

            $passenger_new_balance =  $passenger_balance - $fare_amount;
            $new_pass_balance = round($passenger_new_balance, 2);

            //update the passenger balances and delete the Subtraction_amount referenced key
            $regi_ref = 'registered_passengers/' . $GLOBALS['registered_passenger_key']; //reference to firebase to registered passenger
            $update_regi_pass = [
                'Balance' => $new_pass_balance
            ];

            //update the balance at the registere_passenger node
            $post = $GLOBALS['database']->getReference($regi_ref)->update($update_regi_pass);

            //lets update or add to the driver amount
            $ref3 = 'travelling_taxis';
            $variable = "taxi_id";
            $ke_y3 = find_and_key_2($passenger_chatId, $taxi_identity, $ref3, $variable);
            $data3 = $GLOBALS['database']->getReference($ref3)->getValue();
            $driver_tele_number = $data3[$ke_y3]['tele_id'];

            $ref4 = "Drivers";
            $reg_driver_key = find_and_key($driver_tele_number, $ref4);
            $data4 = $GLOBALS['database']->getReference($ref4)->getValue();
            $driver_bal_before = $data4[$reg_driver_key]['Balance'];
            $driver_bal_after = $driver_bal_before + $fare_amount;

            //the end
            //now push the update
            $ref5 = "Drivers/";
            $ref5 .= $reg_driver_key;

            $update_driver = [
                'Balance' => $driver_bal_after
            ];

            //update the balance at the registere driver node
            $post = $GLOBALS['database']->getReference($ref5)->update($update_driver);
            //end driver balance update

            $display_fare_amount = number_format((float)$fare_amount, 2, '.', '');
            $new_pass_balance = number_format((float)$new_pass_balance, 2, '.', '');

            $n = 4;
            $confirmation = generateNumericOTP($n);

            $paid_ref = 'paid_passengers/'; //reference to firebase
            $paid_ref .= "$taxi_identity/"; //this we'll delete when we end trip or should i say move to new node
            $paid_ref .= $destination_;
            $postdata = [ //this is just for temporary registering of macs and visual of reg structure
                'name' => $GLOBALS['UNIVERSAL_registered_passenger_data']['name'],
                'confirmation' => $confirmation,
                'tele_id' => $GLOBALS['UNIVERSAL_registered_passenger_data']['tele_id']
            ];

            //post the individual passenger to db remember this is temporal registry
            $post = $GLOBALS['database']->getReference($paid_ref)->push($postdata);

            $text = "Done!! \r\n \r\nYou have successfully paid ZAR $display_fare_amount on taxi ID $taxi_identity to $destination_ \r\n \r\nYour transaction key is $confirmation \r\n \r\nYour new balance is ZAR $new_pass_balance \r\n\r\nJust \"Say Hi\" if you need anything";
            $buttons = [["Say Hi"]];
            sendMessage($GLOBALS['message_url'], $passenger_chatId, $text, $buttons);

            //we can have a function to notify the driver
            //we can have a function to notify the driver
            $num = "two";
            $tot_pass = Check_Payments($driver_tele_number,$num);
            $text = "A passenger has paid with a code $confirmation \r\n\r\n. Destination: $destination_.\r\n Total paid passengers is now $tot_pass";
            $buttons = null;
            sendMessage($GLOBALS['message_url'], $driver_tele_number, $text, $buttons); 
            
        } 
        else {
            //lets delete the subtraction_amount node
            $amount_del_ref = 'Subtraction_amount/' . $amount_indicator_key;
            $sub_Amount_del = $GLOBALS['database']->getReference($amount_del_ref)->remove();

            $text = "Failed!! \r\n \r\nYou have inssuficient balance of ZAR$passenger_balance. The amount for taxi ID $taxi_identity to $destination_ is ZAR$fare_amount";
            $buttons = null;
            sendMessage($GLOBALS['message_url'], $passenger_chatId, $text, $buttons);
        }
    } elseif ($passenger_message != $GLOBALS['UNIVERSAL_registered_passenger_data']['pin']) {
        //pin is not corerect
        $text = "pin is incorrect";
        $buttons = null;
        sendMessage($GLOBALS['message_url'], $passenger_chatId, $text, $buttons);

        $error_ref = 'Pay_Error';
        $act_ref = "Blocked_Passengers";
        Check_Input_Error($passenger_chatId, $error_ref,$act_ref);
    }
}

// Function to generate OTP
function generateNumericOTP($n)
{
    //https://www.geeksforgeeks.org/generating-otp-one-time-password-in-php/ 
    // Take a generator string which consist of
    // all numeric digits
    $generator = "1357902468";

    // Iterate for n-times and pick a single character
    // from generator and append it to $result

    // Login for generating a random character from generator
    //     ---generate a random number
    //     ---take modulus of same with length of generator (say i)
    //     ---append the character at place (i) from generator to result

    $result = "";

    for ($i = 1; $i <= $n; $i++) {
        $result .= substr($generator, (rand() % (strlen($generator))), 1);
    }

    // Return result
    return $result;
}

function Check_Input_Error($identity, $reference_node,$action_ref)
{
    //this will get the database node to search the number of errors recorded yet
    $node_data_with_specific_error_identities =  $GLOBALS['database']->getReference($reference_node)->getValue();
    $node_data_with_specific_error_keys = $GLOBALS['database']->getReference($reference_node)->getChildKeys();

    foreach ($node_data_with_specific_error_keys as $passenger_error_key) {
        $passenger_tele_id = $node_data_with_specific_error_identities[$passenger_error_key]['tele_id'];

        if ($identity == $passenger_tele_id) {
            $passenger_has_made_error_before = true;
            $GLOBALS['pass_error_key'] = $passenger_error_key;
            $GLOBALS['counts_of_error'] = $node_data_with_specific_error_identities[$passenger_error_key]['count'];
            break;
        } else if (($identity != $passenger_tele_id)) {
            $passenger_has_made_error_before = false;
        }
    }

    if ($passenger_has_made_error_before == true) {
        $error_times =  $GLOBALS['counts_of_error'];

        if ($error_times >= 2) {
            //the limit for mistakes has been reached, block the account
            $blockpost = [
                'tele_id' => $identity,
                'Reason' => "Blocked for incorrect pin 3 times"
            ];
            $ref = $action_ref;
            $post = $GLOBALS['database']->getReference($ref)->push($blockpost);

            //I must delete all of this passenger's flaggs. later I must have a function like this to help on exits and uncessary texts from passenger
            clear_flaggs($identity);
            
            $ref = 'Passenger_Check_Balance';
            clea_r_e_rror_count($identity, $ref);
            
            //clear the count for errors when trying to delete
            $ref = 'User_Deleting_Pin_Error';
            clea_r_e_rror_count($identity, $ref);
            
            $ref = 'Passenger_deleting_Acount';
            clea_r_e_rror_count($identity, $ref);    
            
            $ref = 'Agent_Delete_Account';
            clea_r_e_rror_count($identity, $ref);
        
            $ref = "Agent_Deleting_Pin_Error";
            clea_r_e_rror_count($identity, $ref);    

            $text = 'You have put incorrect pin 3 times and now your are blocked from using this account. Contact admin to reclaim';
            $buttons = null;
            sendMessage($GLOBALS['message_url'], $identity, $text, $buttons);
        }

        if ($error_times < 2) {
            $text = 'please try again, 1 more chance remaining. After which your will be blocked';
            $buttons = null;
            sendMessage($GLOBALS['message_url'], $identity, $text, $buttons);

            $count = $error_times + 1;
            $ref = $reference_node . '/' . $passenger_error_key;

            $data = [
                'tele_id' => $identity,
                'count' => $count
            ];
            $post_response = $GLOBALS['database']->getReference($ref)->update($data);
        }
    } elseif ($passenger_has_made_error_before == false) {
        $data = [
            'tele_id' => $identity,
            'count' => 1
        ];
        $post_response = $GLOBALS['database']->getReference($reference_node)->push($data);

        $text = 'please try again, 2 more chances remaining';
        $buttons = null;
        sendMessage($GLOBALS['message_url'], $identity, $text, $buttons);
    }
}

function Check_Payments($d_identity,$n)
{
    //first check the taxi id in travelling taxis.
    //this taxi id will then be the reference to extract the travelling passengers
    $ref = "travelling_taxis";
    $ke_y = find_and_key($d_identity, $ref);

    $ref .= "/$ke_y";
    $data = $GLOBALS['database']->getReference($ref)->getValue();

    $taxi_number = $data['taxi_id'];

    $number_of_destinations = $data['count'];
    
    $total_pass = 0;
    
    $text2 = "The following are the passengers that have paid:\r\n\r\n";
    $total_amount_received=0;
    $destination_total_amount=0;

    for ($counter = 1; $counter <= $number_of_destinations; $counter++) {
        $ref1 = null;
        $ref1 = "paid_passengers/";
        $ref1 .=  "$taxi_number/";

        $dest = $data[$counter]['Destination'];
        $destination_total_amount = $data[$counter]['Amount'];

        $ref1 .= $dest;

        $data2 = $GLOBALS['database']->getReference($ref1)->getValue();
        
        if($data2 != null)
        {
            $keys2 = $GLOBALS['database']->getReference($ref1)->getChildKeys();
    
            $text2 .= "To $dest:\r\n";
            $total_passengers_for_stop = 0;
    
            foreach ($keys2 as $key) {
                $name = $data2[$key]['name'];
                $c_onfim = $data2[$key]["confirmation"];
                $text2 .= "$name with c-number $c_onfim\r\n";
                $total_pass = $total_pass+1;
                $total_passengers_for_stop=$total_passengers_for_stop+1;
            }
            
            $stop_amount = $destination_total_amount*$total_passengers_for_stop;
            $total_amount_received = $total_amount_received+$stop_amount;
            $global_tot = $total_pass;
            
            $display_stop_amount = number_format((float)$stop_amount, 2, '.', '');
            $text2 .= "Amount: \tZAR$display_stop_amount\r\n\r\n";
            $tt .= $text2;
            $text2 = null;
        }
        else{
            // $text = 'data fetched is NULL';
            // $buttons = null;
            // sendMessage($GLOBALS['message_url'], $d_identity, $text, $buttons); 
            ECHO("NULL DATA FETCHED");
        }
        
    }

    if($n=="one")
    {
        if($total_pass==0)
        {
            $text = 'No passenger has paid yet.';
            $buttons = null;
            sendMessage($GLOBALS['message_url'], $d_identity, $text, $buttons);
        }
        else{
            $tt .= " ";
            $display_total_amount_received = number_format((float)$total_amount_received, 2, '.', '');
            $tt .= "Total paid passengers: \t$total_pass \r\nThe total amount received is: \tZAR$display_total_amount_received";
            $buttons = [["Say Hi"]];
            sendMessage($GLOBALS['message_url'], $d_identity, $tt, $buttons);        
        }
    }
    
    else if($n == "two")
    {
        return $total_pass;
    }

}

////////////////////////////Agent Begin///////////////////////////////////////////////////
function Registered_Agent_Menu($Agent_ID,$Agent_name,$Agent_Message,$Agent_Message_ID)
{
    //two options
    //1. Send or load money to client
    //2. Inquire balance
    
    Check_Agent_Flaggs($Agent_ID);
    
    if(($GLOBALS['Agent_Check_Balance_flagg'] == false)&&($GLOBALS['Agent_Send_Money_flagg'] == false)&&($GLOBALS['Agent_Sell_Fuel_flagg'] == false)&&($GLOBALS['Agent_Delete_Account_flagg']==false))
    {
        Agent_First_Options($Agent_ID,$Agent_Message,$Agent_name);
    }

    else if(($GLOBALS['Agent_Check_Balance_flagg'] == true)&&($GLOBALS['Agent_Send_Money_flagg'] == false)&&($GLOBALS['Agent_Sell_Fuel_flagg'] == false)&&($GLOBALS['Agent_Delete_Account_flagg']==false))
    {
        if($Agent_Message == "Exit")
        {
            GoodBye($Agent_ID);
            Agent_Clear_Flaggs($Agent_ID);      
            
        }
        else{
            // $text = "Check the entered pin.";
            // $buttons = [["Exit"]];
            // sendMessage($GLOBALS['message_url'], $Agent_ID, $text, $buttons); 
            
            delete_pin($Agent_Message_ID, $Agent_ID);
            Check_Agent_Pin($Agent_ID,$Agent_Message);
        }
    }
    
    else if(($GLOBALS['Agent_Check_Balance_flagg'] == false)&&($GLOBALS['Agent_Send_Money_flagg'] == true)&&($GLOBALS['Agent_and_Passenger_flagg']==false)&&($GLOBALS['Agent_Sell_Fuel_flagg'] == false)&&($GLOBALS['Agent_Delete_Account_flagg']==false))
    {
        if($Agent_Message == "Exit")
        {
            GoodBye($Agent_ID);
            Agent_Clear_Flaggs($Agent_ID);
        }
        else{
            // $text = "Check the entered transaction code and proceed";
            // $buttons = [["Exit"]];
            // sendMessage($GLOBALS['message_url'], $Agent_ID, $text, $buttons);               
            
            $ref = "Passenger_Top_Up";
            find_transaction_code($Agent_ID,$ref,$Agent_Message);
        }
    }

    else if(($GLOBALS['Agent_Send_Money_flagg'] == true)&&($GLOBALS['Agent_Check_Balance_flagg'] == false)&&($GLOBALS['Agent_and_Passenger_flagg']==true)&&($GLOBALS['Agent_now_check_pin_flagg']==false)&&($GLOBALS['Agent_Sell_Fuel_flagg'] == false) &&($GLOBALS['Agent_Delete_Account_flagg']==false))
    {
        if($Agent_Message == "Exit")
        {
            GoodBye($Agent_ID);
            Agent_Clear_Flaggs($Agent_ID);
        }
        else{
            //$text = "Check the amount and ask for pin";
            // $text = "check mate";
            // $buttons = [["Exit"]];
            // sendMessage($GLOBALS['message_url'], $Agent_ID, $text, $buttons);       
            
            Agent_Check_Amount($Agent_ID,$Agent_Message);      
        }
    }

    else if(($GLOBALS['Agent_Send_Money_flagg'] == true)&&($GLOBALS['Agent_Check_Balance_flagg'] == false)&&($GLOBALS['Agent_and_Passenger_flagg']==true)&&($GLOBALS['Agent_now_check_pin_flagg']==true)&&($GLOBALS['Agent_Delete_Account_flagg']==false))
    {
        if($Agent_Message == "Exit")
        {
            GoodBye($Agent_ID);
            Agent_Clear_Flaggs($Agent_ID);
        }
        else{
            delete_pin($Agent_Message_ID, $Agent_ID);
            Check_Agent_Pin($Agent_ID,$Agent_Message);
        }
    }
    
    // else if(($GLOBALS['Agent_and_Passenger_flagg']==false)&&($GLOBALS['Agent_now_check_pin_flagg']==true))
    // {
    //     //this statement is to run in the case that the Agent _ and _ Passenger _ flagg has been deleted while already trying the pin.
    //     //the two minutes are up
    //     //here here here
        
    //     $text = "Passenger code has expired!! Restart transaction";
    //     $buttons = null;
    //     sendMessage($GLOBALS['message_url'], $Agent_ID, $text, $buttons);
        
    //     // $ref = 'Agent_Send_Money'; 
    //     // clea_r_e_rror_count($Agent_ID, $ref);
        
    //     GoodBye($Agent_ID);
    //     Agent_Clear_Flaggs($Agent_ID);
        
    // }

    else if(($GLOBALS['Agent_Check_Balance_flagg'] == false)&&($GLOBALS['Agent_Send_Money_flagg'] == false) && ($GLOBALS['Agent_Sell_Fuel_flagg'] == true) &&($GLOBALS['Agent_and_Driver_flagg']==false)&&($GLOBALS['Agent_Delete_Account_flagg']==false))
    {
        if($Agent_Message == "Exit")
        {
            GoodBye($Agent_ID);
            Agent_Clear_Flaggs($Agent_ID);      
        }
        else{
            // $text = "Check the entered transaction code";
            // $buttons = [["Exit"]];
            // sendMessage($GLOBALS['message_url'], $Agent_ID, $text, $buttons); 
            
            $ref="Driver_Fueling";
            find_fuel_transaction_code($Agent_ID,$ref,$Agent_Message);
        }
    }

    else if(($GLOBALS['Agent_Sell_Fuel_flagg'] == true) &&($GLOBALS['Agent_and_Driver_flagg']==true)&&($GLOBALS['Agent_Delete_Account_flagg']==false))
    {
        if($Agent_Message == "Exit")
        {
            GoodBye($Agent_ID);
            Agent_Clear_Flaggs($Agent_ID);
        }
        else{
            // $text = "Check the amount and ask for pin from the driver";
            // $buttons = [["Exit"]];
            // sendMessage($GLOBALS['message_url'], $Agent_ID, $text, $buttons);       
            Agent_Check_Fuel_Amount($Agent_ID,$Agent_Message);      
        }        
    } 
    
    else if(($GLOBALS['Agent_Delete_Account_flagg']==true))//&&($GLOBALS['Agent_Check_Balance_flagg'] == false)&&($GLOBALS['Agent_Send_Money_flagg'] == false)&&($GLOBALS['Agent_Sell_Fuel_flagg'] == false))
    {
    
        if(($Agent_Message == "Cancel Registration")||($Agent_Message=="Exit"))
        {
            $ref = 'Agent_Delete_Account';
            clea_r_e_rror_count($Agent_ID, $ref);
        
            $ref = "Agent_Deleting_Pin_Error";
            clea_r_e_rror_count($Agent_ID, $ref); 
            
            $text = 'Deleting of Account cancelled!';
            $buttons = [["Say Hi"]];
            sendMessage($GLOBALS['message_url'], $Agent_ID, $text, $buttons);    
        }
        
        else{
             Check_Agent_Pin_for_Deleting($Agent_Message, $Agent_ID);            
        
            // $text = 'checking pin for Deleting of Account!';
            // $buttons = [["Say Hi"]];
            // sendMessage($GLOBALS['message_url'], $Agent_ID, $text, $buttons);   
        }

    }

}


function Check_Agent_Pin_for_Deleting($Agent_message, $Agent_chatId)
{

    $D1ref = "Registered_Agent";
    $reg_driver_key = find_and_key($Agent_chatId, $D1ref);
    $driver_data =  $GLOBALS['database']->getReference($D1ref)->getValue();
    $pin = $driver_data[$reg_driver_key]["pin"];
        
    if ($Agent_message == $pin)
    {

        $ref = 'Agent_Delete_Account';
        clea_r_e_rror_count($Agent_chatId, $ref);
        
        $ref = "Agent_Deleting_Pin_Error";
        clea_r_e_rror_count($Agent_chatId, $ref);
        
        $ref = "Registered_Agent";
        $ke_y = find_and_key($Agent_chatId, $ref);	
    	$del_ref = $ref . '/' . $ke_y;
    	
        $deleting = $GLOBALS['database']->getReference($del_ref)->remove();       
    
        $text = "Account has been deleted!! \r\n\r\n";
        $buttons = [['Say Hi']];
        sendMessage($GLOBALS['message_url'], $Agent_chatId, $text, $buttons);            
    }
    
    elseif ($Agent_message != $pin) {
        //pin is not corerect
        $text = "pin is incorrect";
        //$buttons = null;
        sendMessage($GLOBALS['message_url'], $Agent_chatId, $text, $buttons);

        $error_ref = 'Agent_Deleting_Pin_Error';
        $act_ref = "Blocked_Agents";
        Check_Input_Error($Agent_chatId, $error_ref,$act_ref);
    }
}


function Check_Agent_Flaggs($A_ID)
{
    $ref = "Agent_Check_Balance";
    $GLOBALS['Agent_Check_Balance_flagg'] = find_and_flagg($A_ID, $ref);    
    
    $ref = "Agent_Send_Money";
    $GLOBALS['Agent_Send_Money_flagg'] = find_and_flagg($A_ID, $ref);   
    
    $ref = "Agent_and_Passenger";
    $GLOBALS['Agent_and_Passenger_flagg'] = find_and_flagg($A_ID, $ref); 

    $ref="Agent_Send_Amount";
    $GLOBALS['Agent_now_check_pin_flagg'] = find_and_flagg($A_ID, $ref); 

    $ref = "Agent_Fuel_Sell";
    $GLOBALS['Agent_Sell_Fuel_flagg'] = find_and_flagg($A_ID, $ref);

    $ref = "Agent_and_Driver";
    $GLOBALS['Agent_and_Driver_flagg'] = find_and_flagg($A_ID, $ref);
    
    $ref = "Agent_Delete_Account";
    $GLOBALS['Agent_Delete_Account_flagg'] = find_and_flagg($A_ID, $ref);

}

function Agent_First_Options($A_ID,$A_Message,$A_name)
{
    if($A_Message == "Top Up Passenger")
    {
        $text = "Enter the passenger's transaction code";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $A_ID, $text, $buttons);     
        
        //now we flag this event to enable the state machine for the agent
        $ref = "Agent_Send_Money";
        $postdata = [ 
            'tele_id' => $A_ID
        ];

        $post = $GLOBALS['database']->getReference($ref)->push($postdata);        
    }
    
    else if($A_Message == "Check Balance")
    {
        $text = "Enter your pin to check the balance";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $A_ID, $text, $buttons);       
        
        //flag into database
        $ref = "Agent_Check_Balance";
        $postdata = [ 
            'tele_id' => $A_ID
        ];

        $post = $GLOBALS['database']->getReference($ref)->push($postdata);          
    }

    else if($A_Message == "Sell Fuel/Cashout")
    {
        $text = "Enter driver transaction code or select Exit below to cancel";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $A_ID, $text, $buttons);       
        
        //flag into database
        $ref = "Agent_Fuel_Sell";
        $postdata = [ 
            'tele_id' => $A_ID
        ];

        $post = $GLOBALS['database']->getReference($ref)->push($postdata);         
    }
    
    else if($A_Message == "Delete Account")
    {
        $ref = "Agent_Delete_Account";
        $postdata = [ 
            'tele_id' => $A_ID
        ];

        $post = $GLOBALS['database']->getReference($ref)->push($postdata);    
        
        $text = "Enter pin to confirm delete";
        $buttons = [["Cancel Registration"]];
        sendMessage($GLOBALS['message_url'], $A_ID, $text, $buttons);      
    }

    else if($A_Message == "Exit")
    {
        Agent_Clear_Flaggs($A_ID);
        $text = "Just say \"Hi\" if you need anything else. \r\n\r\nGoodbye!!!";
        $buttons = [["Say Hi"]];
        sendMessage($GLOBALS['message_url'], $A_ID, $text, $buttons);         
    }
    
    else{
        $text = "Hi Agent $A_name, Select one of the following options from your keyboard:";
        $buttons = [["Top Up Passenger"],["Sell Fuel/Cashout"],["Check Balance"],["Delete Account"],["Exit"]];
        sendMessage($GLOBALS['message_url'], $A_ID, $text, $buttons);         
    }    
}

function GoodBye($A_id)
{
    $text = "Just say \"Hi\" if you need anything else. \r\n\r\nGoodbye!!!";
    $buttons = [["Say Hi"]];
    sendMessage($GLOBALS['message_url'], $A_id, $text, $buttons);  
}

function Check_Agent_Pin($Agent_I_dentity,$Agent_M_essage)
{
    $ref = "Registered_Agent";
    $key = find_and_key($Agent_I_dentity, $ref);   
    $node_data = $GLOBALS['database']->getReference($ref)->getValue();
    $pin = $node_data[$key]['pin'];
    
    if($Agent_M_essage == $pin)
    {
        // $text = "The pin is correct.";
        // $buttons = [["Exit"]];
        // sendMessage($GLOBALS['message_url'], $Agent_I_dentity, $text, $buttons);  
        
        Check_Agent_Flaggs($Agent_I_dentity);
        
        if(($GLOBALS['Agent_Check_Balance_flagg'] == true)&&($GLOBALS['Agent_Send_Money_flagg'] == false))
        {
            $agent_bal = $node_data[$key]['Balance'];
            $bal_money = number_format((float)$agent_bal, 2, '.', '');
            
            $text = "Your Balance is ZAR$bal_money";
            $buttons = [["Exit"]];
            sendMessage($GLOBALS['message_url'], $Agent_I_dentity, $text, $buttons); 
            
            $ref = "Agent_Pin_Error";
            clea_r_e_rror_count($Agent_I_dentity, $ref);
            
            GoodBye($Agent_I_dentity);
            Agent_Clear_Flaggs($Agent_I_dentity);
        }
        
        else if(($GLOBALS['Agent_Send_Money_flagg'] == true)&&($GLOBALS['Agent_Check_Balance_flagg'] == false))
        {
            // $text = "Here we start to send the money by asking for the username to which we want to send the money";
            // $buttons = [["Exit"]];
            // sendMessage($GLOBALS['message_url'], $Agent_I_dentity, $text, $buttons); 
            //now we check how much we want to send from the "Agent_Send_Amount" and get passenger id from "Agent_and_Passenger"
            
            //1. here we get the Agent's balance to continue appropriately
            $ref = "Registered_Agent";
            $agent_regkey = Find_and_key($Agent_I_dentity,$ref);

            $Agent_data = $GLOBALS['database']->getReference($ref)->getValue();
            $agent_balance = $Agent_data[$agent_regkey]['Balance'];

            //2. here we get the amount to be sent
            $ref = "Agent_Send_Amount";
            $amount_key = Find_and_key($Agent_I_dentity,$ref);

            $Amount_data = $GLOBALS['database']->getReference($ref)->getValue();
            $amount_to_be_sent = $Amount_data[$amount_key]['Amount'];

            //now we compare 1 and 2 i.e. amount to be sent by agent and the agent's balance
            if($agent_balance >= $amount_to_be_sent)
            {
                //now we get the passenger tele_id we want to send the money to
                $ref = "Agent_and_Passenger";
                $agent_and_pass_pkey = Find_and_key($Agent_I_dentity,$ref);

                $passenger_and_agent_data = $GLOBALS['database']->getReference($ref)->getValue();
                $pass_id = $passenger_and_agent_data[$agent_and_pass_pkey]['Customer_id'];

                //now we extract the passenger balance using obtained tele_id
                $ref = "registered_passengers";
                $passenger_key = Find_and_key($pass_id,$ref);   

                $passenger_data = $GLOBALS['database']->getReference($ref)->getValue();
                $pass_balance = $passenger_data[$passenger_key]['Balance'];

                //now we credit the giver and debit the receiver
                $new_agent_balance = $agent_balance - $amount_to_be_sent;
                $new_passenger_balance = $pass_balance + $amount_to_be_sent;

                //now we update the passenger balance
                $regi_ref = 'registered_passengers/' . $passenger_key;
                $update_regi_pass = [
                    'Balance' => $new_passenger_balance
                ];
                $post = $GLOBALS['database']->getReference($regi_ref)->update($update_regi_pass);

                //now we update the Agent balance
                $ref = 'Registered_Agent/' . $agent_regkey;
                $update = [
                    'Balance' => $new_agent_balance
                ];
                $post = $GLOBALS['database']->getReference($ref)->update($update);
               
                //now we let the passenger know their balance
                $display_amount_to_be_sent = number_format((float)$amount_to_be_sent, 2, '.', '');
                $display_new_passenger_balance = number_format((float)$new_passenger_balance, 2, '.', '');

                $passtext = "Your account has been topped up with ZAR$display_amount_to_be_sent. \r\n\r\nYour new balance is ZAR$display_new_passenger_balance.";
                $buttons = null;
                sendMessage($GLOBALS['message_url'], $pass_id, $passtext, $buttons); 

                //now we let the agent know their balance
                $display_new_agent_balance = number_format((float)$new_agent_balance, 2, '.', '');

                $Agenttext = "You have sent ZAR$display_amount_to_be_sent. \r\n\r\nYour new balance is ZAR$display_new_agent_balance.";
                $buttons = null;
                sendMessage($GLOBALS['message_url'], $Agent_I_dentity, $Agenttext, $buttons); 

                $ref = "Agent_Pin_Error";
                clea_r_e_rror_count($Agent_I_dentity, $ref);
                
                GoodBye($Agent_I_dentity);
                Agent_Clear_Flaggs($Agent_I_dentity);
            }
            else{
                $text = "Insufficient balance to make transaction!!";
                $buttons = null;
                sendMessage($GLOBALS['message_url'], $Agent_I_dentity, $text, $buttons);  
                
                $ref = "Agent_Pin_Error";
                clea_r_e_rror_count($Agent_I_dentity, $ref);
                
                GoodBye($Agent_I_dentity);
                Agent_Clear_Flaggs($Agent_I_dentity);
            }
        }
    }
    else{
        $text = "The Agent pin is incorrect. Do the error count and give retry message else block";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $Agent_I_dentity, $text, $buttons);      
        
        $error_ref = 'Agent_Pin_Error';
        $act_ref = "Blocked_Agents";
        Check_Input_Error($Agent_I_dentity, $error_ref, $act_ref);
    }
}

function find_transaction_code($A_gentID,$noderef,$co_de)
{
    $data =  $GLOBALS['database']->getReference($noderef)->getValue();
    $child_keys = $GLOBALS['database']->getReference($noderef)->getChildKeys();

    foreach($child_keys as $key)
    {
        $trans_action_number = $data[$key]['transaction_code'];
        $pass_name = $data[$key]['pass_name'];

        if($co_de == $trans_action_number)
        {
            //get the passenger id and then flag it into db together with the agent id
            $id_pass = $data[$key]['tele_id'];
            $code_is_correct_flagg=true;
            break;
    
        }
        else{
            echo("not the same");
            $code_is_correct_flagg=false;
        }
    }

    if($code_is_correct_flagg == true)
    {
        date_default_timezone_set('Africa/Johannesburg'); 
        $d = date('y-m-d'); 
        $t = date('G:i:s'); 
        $time = $d.",".$t;
        
        $ref = "Agent_and_Passenger";
        $postdata = [ 
            'tele_id' => $A_gentID,
            'Customer_id' => $id_pass,
            'time' => $time,
            'Customer_name' => $pass_name,
            'transaction_code' => $trans_action_number
        ];

        $post = $GLOBALS['database']->getReference($ref)->push($postdata);

        $text = "This account belongs to a person named $pass_name, \r\n\r\nEnter Amount to continue";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $A_gentID, $text, $buttons);      

    }

    else if($code_is_correct_flagg == false)
    {
        $text = "Transaction code not found!!\r\n\r\nSelect Exit to cancel";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $A_gentID, $text, $buttons);
        
        //$error_ref = 'Agent_Confirmation_Code_Error';
        //$act_ref = null;
        
        //Check_Input_Error($A_gentID, $error_ref, $act_ref);
        
        $ref = "Agent_Confirmation_Code_Error";
        $opt = null;
        e_rror_count($A_gentID, $ref, $opt);        
    }
}

function Agent_Check_Amount($Agent_I_dentity,$Agent_M_essage) 
{
    if(is_numeric($Agent_M_essage) == 1)
    {
        $ref = "Agent_Send_Amount";
        $postdata = [ 
            'tele_id' => $Agent_I_dentity,
            'Amount' => $Agent_M_essage
        ];

        $post = $GLOBALS['database']->getReference($ref)->push($postdata);  

        $text = "Enter pin";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $Agent_I_dentity, $text, $buttons);
    }

    else{
        $text = "Check that you input correctly the amount";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $Agent_I_dentity, $text, $buttons);  
        
        $ref = "Agent_Check_Amount_Error";
        $opt = null;
        e_rror_count($Agent_I_dentity, $ref, $opt); 
    }
}

function find_fuel_transaction_code($A_gentID,$noderef,$co_de)
{
    $data =  $GLOBALS['database']->getReference($noderef)->getValue();
    $child_keys = $GLOBALS['database']->getReference($noderef)->getChildKeys();

    foreach($child_keys as $key)
    {
        $trans_action_number = $data[$key]['transaction_code'];

        if($co_de == $trans_action_number)
        {
            //get the passenger id and then flag it into db together with the agent id
            $id_driver = $data[$key]['tele_id'];
            $code_is_correct_flagg=true;
            break;
    
        }
        else{
            echo("not the same");
            $code_is_correct_flagg=false;
        }
    }

    if($code_is_correct_flagg == true)
    {
        $ref="Agent_Confirmation_Code_Error";
        clea_r_e_rror_count($A_gentID, $ref); 
    
        $ref = "Agent_and_Driver";
        $postdata = [ 
            'tele_id' => $A_gentID,
            'Customer_id' => $id_driver,
            'transaction_code' => $trans_action_number
        ];

        $post = $GLOBALS['database']->getReference($ref)->push($postdata);
        
        $ref = "Agent_and_Driver_2";
        $post = $GLOBALS['database']->getReference($ref)->push($postdata);

        $text = "Enter the fuel value amount to continue";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $A_gentID, $text, $buttons);      

    }

    else if($code_is_correct_flagg == false)
    {
        $text = "Transaction code not found!!\r\n\r\nSelect Exit to cancel Fuel sell";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $A_gentID, $text, $buttons);
        
        $ref = "Agent_Confirmation_Code_Error";
        $opt = null;
        e_rror_count($A_gentID, $ref, $opt);        
    }
}

function Agent_Check_Fuel_Amount($Agent_I_dentity,$Agent_M_essage) 
{
    if(is_numeric($Agent_M_essage) == 1)
    {
        $ref = 'Agent_and_Driver';
        $agent_n_driver_key = find_and_key($Agent_I_dentity, $ref);
        
        $Agent_and_Driver_data =  $GLOBALS['database']->getReference($ref)->getValue();
        $paying_driver = $Agent_and_Driver_data[$agent_n_driver_key]['Customer_id'];

        $ref = "Driver_to_Pay"; //should be affected by time
        $postdata = [ 
            'tele_id' => $paying_driver,
            'Amount' => $Agent_M_essage
        ];
    
        $post = $GLOBALS['database']->getReference($ref)->push($postdata);

        $DISPLAY_FUEL_AMOUNT = number_format((float)$Agent_M_essage, 2, '.', '');
        $drivertext = "Enter pin to pay ZAR$DISPLAY_FUEL_AMOUNT for fuel/cashout";
        $buttons = [["Cancel"]];
        sendMessage($GLOBALS['message_url'], $paying_driver, $drivertext, $buttons);
        
        $Agent_text = "Now we wait for them to input pin";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $Agent_I_dentity, $Agent_text, $buttons);
        
        //this will bring back the state machine to zero after entering the correct amount
        $ref = 'Agent_and_Driver';
        clea_r_e_rror_count($Agent_I_dentity, $ref);
        
        $ref = "Agent_Fuel_Sell";
        clea_r_e_rror_count($Agent_I_dentity, $ref);
    }

    else{
        $text = "Check that you input correctly the amount";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $Agent_I_dentity, $text, $buttons);  
        
        $ref = "Agent_Check_Amount_Error";
        $opt = null;
        e_rror_count($Agent_I_dentity, $ref, $opt); 
    }
}

//////////////////////////////Driver begin///////////////
function DriverMenu($driver_text, $driver_name, $telegram_identity,$message_ID)
{

    Check_Flagggs($telegram_identity);
    
    if (($GLOBALS['Driver_Deleting_Account_flagg']==false)&&($GLOBALS['driver_is_going_flagg'] == false) && ($GLOBALS['entered_correct_id_flagg'] == false) && ($GLOBALS['entered_destination_flagg'] == false) && ($GLOBALS['entered_Amount_flagg'] == false) && ($GLOBALS['already_travelling_taxi'] == false)&&($GLOBALS['Driver_Fuelling_flagg']==false)&&($GLOBALS['Driver_Agent_flagg']==false)&&($GLOBALS['Driver_Wants_Wallet_flagg']==false)) {
        // $text = 'driver first options';
        // $buttons = null;
        // sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);
        Driver_First_Options($driver_text, $driver_name, $telegram_identity);
    }
    else if (($GLOBALS['Driver_Wants_Wallet_flagg']==true)&&($GLOBALS['driver_is_going_flagg'] == false) && ($GLOBALS['entered_correct_id_flagg'] == false) && ($GLOBALS['entered_destination_flagg'] == false) && ($GLOBALS['entered_Amount_flagg'] == false)&&($GLOBALS['Driver_Fuelling_flagg']==false)&&($GLOBALS['Driver_Agent_flagg']==false)) 
    {
        // $text = 'Here we check the entered driver pin and show bal';
        // $buttons = null;
        // sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);
        
        delete_pin($message_ID, $telegram_identity);
        Show_Driver_Balance($telegram_identity,$driver_text);        
    }
    else if (($GLOBALS['driver_is_going_flagg'] == true) && ($GLOBALS['entered_correct_id_flagg'] == false) && ($GLOBALS['entered_destination_flagg'] == false) && ($GLOBALS['entered_Amount_flagg'] == false)&&($GLOBALS['Driver_Fuelling_flagg']==false)&&($GLOBALS['Driver_Agent_flagg']==false)) {
        // $text = "check the enter taxi identity if equals true\r\n\r\n BOT underdevelopment";
        // $buttons = null;
        // sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons); 
        Check_Entered_Taxi_ID($driver_text, $telegram_identity);
    } else if (($GLOBALS['driver_is_going_flagg'] == true) && ($GLOBALS['entered_correct_id_flagg'] == true) && ($GLOBALS['entered_destination_flagg'] == false) && ($GLOBALS['entered_Amount_flagg'] == false)&&($GLOBALS['Driver_Fuelling_flagg']==false)&&($GLOBALS['Driver_Agent_flagg']==false)) {
        // $text = "Store destination and ask for fare amount\r\n\r\n BOT under development";
        // $buttons = null;
        // sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);
        Get_Destination($telegram_identity, $driver_text);
    } else if (($GLOBALS['driver_is_going_flagg'] == true) && ($GLOBALS['entered_correct_id_flagg'] == true) && ($GLOBALS['entered_destination_flagg'] == true) && ($GLOBALS['entered_Amount_flagg'] == false)&&($GLOBALS['Driver_Fuelling_flagg']==false)&&($GLOBALS['Driver_Agent_flagg']==false)) {
        // $text = "Store amount and ask if they have another destination\r\n\r\n BOT under development";
        // $buttons = null;
        // sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);
        Get_Amount($telegram_identity, $driver_text);
    } else if (($GLOBALS['driver_is_going_flagg'] == true) && ($GLOBALS['entered_correct_id_flagg'] == true) && ($GLOBALS['entered_destination_flagg'] == true) && ($GLOBALS['entered_Amount_flagg'] == true)&&($GLOBALS['Driver_Fuelling_flagg']==false)&&($GLOBALS['Driver_Agent_flagg']==false)) {
        // $text = 'If the response to another trip is yes add and if no exit';
        // $buttons = null;
        // sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);
        Add_Trip_Option($telegram_identity, $driver_text);
    } else if (($GLOBALS['driver_is_going_flagg'] == false) && ($GLOBALS['entered_correct_id_flagg'] == false) && ($GLOBALS['entered_destination_flagg'] == false) && ($GLOBALS['entered_Amount_flagg'] == false) && ($GLOBALS['already_travelling_taxi'] == true)&&($GLOBALS['Driver_Fuelling_flagg']==false)&&($GLOBALS['Driver_Agent_flagg']==false)) {
        
        // $text = "The taxi is already travelling\r\n\r\n BOT under development";
        // $buttons = null;
        // sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);

        //this flagg will only become true when the driver finishes inputting a trip details
        //whenever we are here, we first remind the driver of their current trip details
        //trip_details($telegram_identity);
        //we then ask if they want to 1.finish trip 2.add stop

        Already_Travelling_Options($telegram_identity, $driver_text);
    }
    else if(($GLOBALS['Driver_Fuelling_flagg']==true)&&($GLOBALS['Driver_Agent_flagg']==false))
    {
        if($driver_text == "Cancel")
        {
            $ref = 'Driver_Fueling';
            clea_r_e_rror_count($telegram_identity, $ref);
            
            $text = "Your transaction will be cancelled!!\r\n\r\nGoodbye!!";
            $buttons = [["Say Hi"]];
            sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);

        }
        else{
            $text = "Your transaction code wil expire soon. Did you mean to Cancel?!!\r\n\r\nSelect Cancel below or wait for pin prompt to proceed with transaction";
            $buttons = [["Cancel"]];
            sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);            
        }
    }
    else if(($GLOBALS['Driver_Fuelling_flagg']==true)&&($GLOBALS['Driver_Agent_flagg']==true))
    {
        if($driver_text == "Exit")
        {
            $ref = 'Driver_Fueling';
            clea_r_e_rror_count($telegram_identity, $ref);
            
            $ref = 'Agent_and_Driver_2';
            $variable = "Customer_id";
            $ke_y = find_and_key_2($telegram_identity, $telegram_identity, $ref, $variable);

            $deleting_ref = $ref.'/'. $ke_y;
            $deleting = $GLOBALS['database']->getReference($deleting_ref)->remove();  

            $text = 'Just say \"Hi\" if you need anything else. \r\n\r\nGoodbye!!!';
            $buttons = [["Say Hi"]];
            sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);

        }
        // $text = 'Here we check the entered driver pin and act';
        // $buttons = null;
        // sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);
        
        delete_pin($message_ID, $telegram_identity);
        check_driver_pin($telegram_identity,$driver_text);
    }
    
    else if(($GLOBALS['Driver_Deleting_Account_flagg']==true)&&($GLOBALS['driver_is_going_flagg'] == false) && ($GLOBALS['entered_correct_id_flagg'] == false) && ($GLOBALS['entered_destination_flagg'] == false) && ($GLOBALS['entered_Amount_flagg'] == false) && ($GLOBALS['already_travelling_taxi'] == false)&&($GLOBALS['Driver_Fuelling_flagg']==false)&&($GLOBALS['Driver_Agent_flagg']==false)&&($GLOBALS['Driver_Wants_Wallet_flagg']==false))
    {
        if ($driver_text == "Exit") 
        {
            driver_exiit($telegram_identity);
        }
        else{
            // //$text = "Your Account will be deleted";
            // delete_pin($message_ID, $telegram_identity);
            // //$buttons = null;
            // //sendMessage($GLOBALS['message_url'], $chatId, $text, $buttons);
            Check_Driver_Pin_for_Deleting($driver_text, $telegram_identity);
            
            // $text = "Your Account will be deleted";
            // sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);        
        }
            
    }
}

function Check_Driver_Pin_for_Deleting($driver_message, $driver_chatId)
{
    $D1ref = "Drivers";
    $reg_driver_key = find_and_key($driver_chatId, $D1ref);
    $driver_data =  $GLOBALS['database']->getReference($D1ref)->getValue();
    $pin = $driver_data[$reg_driver_key]["pin"];
        
    if ($driver_message == $pin)
    {
        $ref = 'Driver_Deleting_Account';
        clea_r_e_rror_count($driver_chatId, $ref);
        
        $ref = "Driver_Deleting_Pin_Error";
        clea_r_e_rror_count($driver_chatId, $ref);
        
        $ref = "Drivers";
        $ke_y = find_and_key($driver_chatId, $ref);	
    	$del_ref = $ref . '/' . $ke_y;
    	
        $deleting = $GLOBALS['database']->getReference($del_ref)->remove();       
    
        $text = "Account has been deleted!! \r\n\r\n";
        $buttons = [['Say Hi']];
        sendMessage($GLOBALS['message_url'], $driver_chatId, $text, $buttons);            
    }
    
    elseif ($driver_message != $pin) {
        //pin is not corerect
        $text = "pin is incorrect";
        //$buttons = null;
        sendMessage($GLOBALS['message_url'], $driver_chatId, $text, $buttons);

        $error_ref = 'Driver_Deleting_Pin_Error';
        $act_ref = "Blocked_Drivers";
        Check_Input_Error($driver_chatId, $error_ref,$act_ref);
    }
}


function Check_Flagggs($driver_id)
{
    
    //searchif driver is already going
    $ref = "Already_Travelling_Taxi";
    $GLOBALS['already_travelling_taxi'] = find_and_flagg($driver_id, $ref);

    //search if driver wants to go
    $ref = "travelling_taxis";
    $GLOBALS['travelling_taxi_id_indicator_key'] = find_and_key($driver_id, $ref);

    //search if driver wants to go
    $ref = "DriverGoing_Flagg";
    $GLOBALS['driver_is_going_flagg'] = find_and_flagg($driver_id, $ref);

    //search if driver has entered taxi id
    $ref = "Entered_Correct_Taxi_ID";
    $GLOBALS['entered_correct_id_flagg'] = find_and_flagg($driver_id, $ref);

    //search if driver has  entered a destination yet
    $ref = "Destination_Flagg";
    $GLOBALS['entered_destination_flagg'] = find_and_flagg($driver_id, $ref);

    //search if driver has  entered amount yet
    $ref = "Amount_Flagg";
    $GLOBALS['entered_Amount_flagg'] = find_and_flagg($driver_id, $ref);
    
    //the following will allow for direct pin during the fuel payment
    $ref = "Driver_Fueling";
    $GLOBALS['Driver_Fuelling_flagg'] = find_and_flagg($driver_id, $ref);    
    
    $ref = "Agent_and_Driver_2";
    $vary = "Customer_id";
    $GLOBALS['Driver_Agent_flagg'] = find_and_flagg_2($driver_id, $ref,$vary);   
    
    $ref = "Driver_Wants_Wallet";
    $GLOBALS['Driver_Wants_Wallet_flagg'] = find_and_flagg($driver_id, $ref);    

    $ref = "Driver_Deleting_Account";
    $GLOBALS['Driver_Deleting_Account_flagg'] = find_and_flagg($driver_id, $ref); 
}

function find_and_flagg_2($i_dentity, $ref_node,$var)
{
    //this function finds and identity at a specific location and returns true if found else false

    $data =  $GLOBALS['database']->getReference($ref_node)->getValue();
    $data_keys = $GLOBALS['database']->getReference($ref_node)->getChildKeys();

    if ($data > 0) {
        foreach ($data as $u_ser) {
            if ($u_ser["Customer_id"] == $i_dentity) {
                $find_flagg = true;
                break;
            } else {
                $find_flagg = false;
            }
        }
    }

    if ($find_flagg == true) {
        return true;
    } else {
        return false;
    }
}

function Driver_First_Options($driver_message, $name_driver, $driver_identity)
{
    if ($driver_message == 'Go') {
        $postdata_driver_is_going = [
            'tele_id' => $driver_identity
        ];
        $ref_Drivergoing_flagging = 'DriverGoing_Flagg';
        $driverpost = $GLOBALS['database']->getReference($ref_Drivergoing_flagging)->push($postdata_driver_is_going);

        $text = "Enter taxi ID. Click messaging box for normal keyboard: \r\n \r\nOR \r\n\r\nSelect Exit from your keyboard below:";
        $buttons = [['Exit']];
        sendMessage($GLOBALS['message_url'], $driver_identity, $text, $buttons);
    }
    else if($driver_message == "ADD Fuel/Cashout")
    {
        Fuel_Option($driver_identity);
    }
    
    else if($driver_message=="Wallet Balance")
    {
       $postdata = [
            'tele_id' => $driver_identity
        ];
        $ref = "Driver_Wants_Wallet";
        $post = $GLOBALS['database']->getReference($ref)->push($postdata);

        $text = "Enter pin";
        $buttons = [['Exit']];
        sendMessage($GLOBALS['message_url'], $driver_identity, $text, $buttons);        
    }
    
    else if ($driver_message == "Exit") {
        $text = "Just say \"Hi\" if you need anything else. \r\n\r\nGoodbye!!!";
        $buttons = [["Say Hi"]];
        sendMessage($GLOBALS['message_url'], $driver_identity, $text, $buttons);
        driver_exiit($driver_identity);
    } 
    
    else if ($driver_message == "Delete Account") {
        
        //flag into database
        $ref = "Driver_Deleting_Account";
        $postdata = [ 
            'tele_id' => $driver_identity
        ];

        $post = $GLOBALS['database']->getReference($ref)->push($postdata);    
        
        $text = "Enter pin to confirm delete.";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $driver_identity, $text, $buttons);
        //driver_exiit($driver_identity);
        
    } 

    else {
        $text = "Hi driver $name_driver, Select one of the following options from your keyboard";
        $buttons = [["Go"],["ADD Fuel/Cashout"],["Wallet Balance"], ["Delete Account"], ["Exit"]];
        sendMessage($GLOBALS['message_url'], $driver_identity, $text, $buttons);
    }
}

function driver_exiit($driver_id)
{
    $reference_node = "DriverGoing_Flagg";
    clea_r_e_rror_count($driver_id, $reference_node);

    $reference_node = "Destination_Flagg";
    clea_r_e_rror_count($driver_id, $reference_node);

    $reference_node = "Entered_Correct_Taxi_ID";
    clea_r_e_rror_count($driver_id, $reference_node);

    $reference_node = "Amount_Flagg";
    clea_r_e_rror_count($driver_id, $reference_node);

    $reference_node = "Entered_Wrong_Taxi_ID";
    clea_r_e_rror_count($driver_id, $reference_node);

    $reference_node = "Driver_False_Amount";
    clea_r_e_rror_count($driver_id, $reference_node);
    
    $reference_node = "Driver_Wants_Wallet";
    clea_r_e_rror_count($driver_id, $reference_node);
    
    $reference_node = "Passenger_Check_Balance";
    clea_r_e_rror_count($driver_id, $reference_node);
    
    $reference_node = "Driver_Deleting_Account";
    clea_r_e_rror_count($driver_id, $reference_node);
    
    $ref = "Driver_Deleting_Pin_Error";
    clea_r_e_rror_count($driver_id, $reference_node);
}

function Check_Entered_Taxi_ID($driver_text, $telegram_identity)
{
    if ($driver_text != 'Exit') {
        //search if driver has entered taxi id
        $entered_taxi_ref = "Drivers";
        $entered_correct_data =  $GLOBALS['database']->getReference($entered_taxi_ref)->getValue();
        $entered_correct_data_keys = $GLOBALS['database']->getReference($entered_taxi_ref)->getChildKeys();

        if ($entered_correct_data > 0) {
            foreach ($entered_correct_data_keys as $driver_key) {
                $driver_identity = $entered_correct_data[$driver_key]['tele_id'];

                if ($telegram_identity == $driver_identity) {
                    if ($entered_correct_data[$driver_key]['taxi_id'] == $driver_text) {
                        //we flag this event into database
                        $ref = 'Entered_Correct_Taxi_ID';
                        $postdata = [
                            'tele_id' => $telegram_identity
                        ];
                        $post = $GLOBALS['database']->getReference($ref)->push($postdata);

                        //We also record this taxi on the node that records taxis which are travelling
                        //we flag this event into database
                        $ref = 'travelling_taxis';
                        $postdata = [
                            'tele_id' => $telegram_identity,
                            'taxi_id' => $driver_text,
                            'count' => 1
                        ];
                        $post = $GLOBALS['database']->getReference($ref)->push($postdata);

                        $text = "Enter Destination. Click messaging box for normal keyboard: \r\n \r\nOR \r\n\r\nSelect Exit from your keyboard below:";
                        $buttons = [['Exit']];
                        sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);
                    } else {
                        //ask driver to retry to input the taxi id
                        //if the driver decides to exit, we can clean up  
                        $text = "Please retry your Taxi ID or Exit";
                        $buttons = [["Exit"]];
                        sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);

                        //here we will count how many times the driver has failed their taxi id
                        //3 times we restart the process
                        $ref = "Entered_Wrong_Taxi_ID";
                        $wrong_taxi_id_counter = number_of_counts($telegram_identity, $ref);
                        Driver_Error_Action($wrong_taxi_id_counter, $telegram_identity, $ref);
                    }
                    break;
                } else {
                    echo ("Not the same driver as wanted");
                }
            }
        }
    } elseif ($driver_text == 'Exit') {
        driver_exiit($telegram_identity);
        $text = "Just say \"Hi\" if you need anything else. \r\n\r\nGoodbye!!!";
        $buttons = [["Say Hi"]];
        sendMessage($GLOBALS['message_url'], $telegram_identity, $text, $buttons);
    }
}

function number_of_counts($tele_identity, $node)
{
    $data =  $GLOBALS['database']->getReference($node)->getValue();
    $child_keys = $GLOBALS['database']->getReference($node)->getChildKeys();

    foreach ($child_keys as $key) {
        $identity = $data[$key]['tele_id'];

        if ($tele_identity == $identity) {
            $found_flagg = true;
            $count_ = $data[$key]['count'];
            break;
        } else {
            $found_flagg = false;
            echo ("Not the same");
        }
    }

    if ($found_flagg == true) {
        return $count_;
    } else {
        return 0;
    }
}

function Driver_Error_Action($number_of_errors, $d_identity, $ref_node)
{
    //this function decides what happens given number of errors
    if ($number_of_errors == 0) {
        $data_2_post = 1;
        $push_type = 'push';
        add_to_count($d_identity, $ref_node, $data_2_post, $push_type);

        $text = "Please retry";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $d_identity, $text, $buttons);
    } elseif (($number_of_errors > 0) && ($number_of_errors < 2)) {
        $data_2_post = $number_of_errors + 1;
        $push_type = 'update';
        add_to_count($d_identity, $ref_node, $data_2_post, $push_type);

        $text = "Please retry";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $d_identity, $text, $buttons);
    }

    if ($number_of_errors >= 2) {
        $text = "Too many errors. You can restart!";
        $buttons = null;
        sendMessage($GLOBALS['message_url'], $d_identity, $text, $buttons);

        $ref = 'travelling_taxis';
        clea_r_e_rror_count($d_identity, $ref);

        driver_exiit($d_identity);
    }
}

function add_to_count($tele_identity, $node, $data_to_post, $post_type)
{
    //$GLOBALS['add_to_indicator_key_2']=find_and_key($tele_identity,$node);

    $postdata = [
        'count' => $data_to_post,
        'tele_id' => $tele_identity
    ];

    if ($post_type == 'update') {
        $data =  $GLOBALS['database']->getReference($node)->getValue();
        $child_keys = $GLOBALS['database']->getReference($node)->getChildKeys();

        foreach ($child_keys as $key) {
            $u_ser = $data[$key]['tele_id'];
            if ($tele_identity == $u_ser) {
                $GLOBALS['add_to_indicator_key_2'] = $key;
                break;
            } else {
                echo ("Not the same");
            }
        }

        $kkey = $GLOBALS['add_to_indicator_key_2'];
        $ref = $node . '/' . $kkey;
        $post = $GLOBALS['database']->getReference($ref)->update($postdata);
    } elseif ($post_type == 'push') {
        $ref = $node;
        $post = $GLOBALS['database']->getReference($ref)->push($postdata);
    }
}

function Get_Destination($driver_Identity, $driver_message)
{
    //WE'LL ALSO SOON START CHECKING IF THE MESSAGE IS NOT A NUMBER
    if ($driver_message != 'Exit') {
        $ref = 'travelling_taxis';
        $sub_ref = number_of_counts($driver_Identity, $ref);
        $val = "Destination";
        add_to_node($driver_Identity, $driver_message, $ref, $sub_ref, $val);

        //flag that the destination has been enetered
        $postdata_dest = [
            'tele_id' => $driver_Identity
        ];
        $ref_dest = 'Destination_Flagg';
        $post_result = $GLOBALS['database']->getReference($ref_dest)->push($postdata_dest); //this will be checked before we receive the trip amount

        $text = "Enter amount. Click messaging box for normal keyboard: \r\n \r\nOR \r\n\r\nSelect Exit from your keyboard below:";
        $buttons = [['Exit']];
        sendMessage($GLOBALS['message_url'], $driver_Identity, $text, $buttons);
    } elseif ($driver_message == 'Exit') {
        $ref = 'travelling_taxis';
        clea_r_e_rror_count($driver_Identity, $ref);

        driver_exiit($driver_Identity);
        $text = "Just say \"Hi\" if you need anything else. \r\n\r\nGoodbye!!!";
        $buttons = [["Say Hi"]];
        sendMessage($GLOBALS['message_url'], $driver_Identity, $text, $buttons);
    }
}

function add_to_node($tele_identity, $driver_txt, $node, $sub_reference, $variable)
{
    //this adds to the tripple node. its an atempt to optimise the node so that we can add more trips neatly
    $GLOBALS['add_to_indicator_key'] = find_and_key($tele_identity, $node);

    $kkey = $GLOBALS['add_to_indicator_key'];
    $ref = $node . '/' . $kkey . '/' . $sub_reference; //$tele_identity

    $postdata = [
        $variable => $driver_txt
    ];
    $post = $GLOBALS['database']->getReference($ref)->update($postdata);
}

function Get_Amount($driver_Identity, $driver_message)
{
    if (is_numeric($driver_message) == 1) {
        $ref = 'travelling_taxis';
        $sub_ref = number_of_counts($driver_Identity, $ref);
        $val = "Amount";
        add_to_node($driver_Identity, $driver_message, $ref, $sub_ref, $val);

        //flag that the destination has been enetered
        $postdata_dest = [
            'tele_id' => $driver_Identity
        ];
        $ref_dest = 'Amount_Flagg';
        $post_result = $GLOBALS['database']->getReference($ref_dest)->push($postdata_dest); //this will be checked before we receive the trip amount
        trip_details($driver_Identity);
        $text = 'Do you want to add another trip, or cancel this one? Select from the options below:';
        $buttons = [["ADD"], ["FINISH"], ["CANCEL TRIP"]];
        sendMessage($GLOBALS['message_url'], $driver_Identity, $text, $buttons);
    } elseif ($driver_message == 'Exit') {
        $ref = 'travelling_taxis';
        clea_r_e_rror_count($driver_Identity, $ref);

        driver_exiit($driver_Identity);
        $text = "Just say \"Hi\" if you need anything else. \r\n\r\nGoodbye!!!";
        $buttons = [["Say Hi"]];
        sendMessage($GLOBALS['message_url'], $driver_Identity, $text, $buttons);
    } else {
        //message is not exit or a figure
        $ref = 'Driver_False_Amount';
        $e_rror_count = number_of_counts($driver_Identity, $ref);
        Driver_Error_Action($e_rror_count, $driver_Identity, $ref);
    }
}

function trip_details($d_Identity)
{
    //this will display to the driver their details after inputing
    $ref = "travelling_taxis";
    $data =  $GLOBALS['database']->getReference($ref)->getValue();
    $child_keys = $GLOBALS['database']->getReference($ref)->getChildKeys();

    foreach ($child_keys as $key) {
        $identity = $data[$key]['tele_id'];
        if ($d_Identity == $identity) {
            $GLOBALS['trip_display_indicator_key'] = $key;
            $number_of_trips = $data[$key]['count'];

            // $text = "count is $number_of_trips";
            // $buttons = null;
            // sendMessage($GLOBALS['message_url'], $d_Identity, $text, $buttons);

            if ($number_of_trips == 1) {
                $money = $data[$key][1]['Amount'];
                $place = $data[$key][1]['Destination'];

                $text = "Your trip is to $place, for ZAR $money \r\n \r\n";
                $buttons = [["Say Hi"]];
                sendMessage($GLOBALS['message_url'], $d_Identity, $text, $buttons);
            } else if ($number_of_trips > 1) {
                $text = "Your trip has the following stops: \r\n \r\n";

                for ($counter = 1; $counter <= $number_of_trips; $counter++) {
                    $money = $data[$key][$counter]['Amount'];
                    $place = $data[$key][$counter]['Destination'];

                    $display_money = number_format((float)$money, 2, '.', '');
                    $text .= "To $place, for ZAR $display_money";
                    $text .= "\n";
                }
                $text .= "\n" . " ";
                $buttons = [["Say Hi"]];
                sendMessage($GLOBALS['message_url'], $d_Identity, $text, $buttons);
            }

            break;
        } else {
            echo ("Not the same");
        }
    }
}

function Add_Trip_Option($driver_Identity, $driver_message)
{
    if (($driver_message == "ADD") || ($driver_message == "ADD STOP")) {
        $text = 'You will now be adding to your current trip';
        $buttons = null;
        sendMessage($GLOBALS['message_url'], $driver_Identity, $text, $buttons);

        $ref = "travelling_taxis";
        $latest_count = number_of_counts($driver_Identity, $ref);

        $update_count = $latest_count + 1;
        $db_variable = "count";
        $ref = "travelling_taxis";
        add_to_direct_node($driver_Identity, $ref, $db_variable, $update_count);

        //WE clear some flags using function clea_r_e_rror_count($passenger_identity,$reference_node)

        $reference_node = "Destination_Flagg";
        clea_r_e_rror_count($driver_Identity, $reference_node);

        $reference_node = "Amount_Flagg";
        clea_r_e_rror_count($driver_Identity, $reference_node);

        $text = "Enter Destination. Click messaging box for normal keyboard: \r\n \r\nOR \r\n\r\nSelect Exit from your keyboard to cancel";
        $buttons = [['Exit']];
        sendMessage($GLOBALS['message_url'], $driver_Identity, $text, $buttons);
    } else if ($driver_message == "FINISH") {
        driver_exiit($driver_Identity);
        trip_details($driver_Identity);

        //now we flagg this into db to indicate 
        $ref = "Already_Travelling_Taxi";
        $already_flagg = find_and_flagg($driver_Identity, $ref);

        if ($already_flagg == true) {
            echo ("The driver was already logged");
        } else {
            $data = [
                'tele_id' => $driver_Identity
            ];
            $post_result = $GLOBALS['database']->getReference($ref)->push($data);
        }
    } elseif ($driver_message == "CANCEL TRIP") {
        $ref = 'travelling_taxis';
        //clea_r_e_rror_count($driver_Identity,$ref);

        cancel_trip_addition($driver_Identity, $ref);

        driver_exiit($driver_Identity);
        $text = "You have successfully cancelled your trip. \r\n\r\nJust say \"Hi\" if you need anything else. \r\nGoodbye!!!";
        $buttons = [["Say Hi"]];
        sendMessage($GLOBALS['message_url'], $driver_Identity, $text, $buttons);
    } else {
        $text = "Driver said $driver_message";
        $buttons = null;
        sendMessage($GLOBALS['message_url'], $driver_Identity, $text, $buttons);
    }
}

function add_to_direct_node($tele_identity, $node, $variable, $data_to_post)
{
    $GLOBALS['add_to_indicator_key_2'] = find_and_key($tele_identity, $node);

    $data =  $GLOBALS['database']->getReference($node)->getValue();
    $child_keys = $GLOBALS['database']->getReference($node)->getChildKeys();

    $kkey = $GLOBALS['add_to_indicator_key_2'];
    $ref = $node . '/' . $kkey;

    $postdata = [
        $variable => $data_to_post
    ];
    $post = $GLOBALS['database']->getReference($ref)->update($postdata);
}

function cancel_trip_addition($identity, $reference_node)
{
    $cancel_key = find_and_key($identity, $reference_node);

    $count = number_of_counts($identity, $reference_node);

    if ($count <= 1) {
        clea_r_e_rror_count($identity, $reference_node);
    } else {
        $del_ref = $reference_node . '/' . $cancel_key . '/' . $count;
        $deleting = $GLOBALS['database']->getReference($del_ref)->remove();
        $update_count = $count - 1;
        $db_variable = "count";
        add_to_direct_node($identity, $reference_node, $db_variable, $update_count);
    }
}

function Already_Travelling_Options($driver_identity, $driver_message)
{
    if ($driver_message == 'CHECK PAYMENTS') {
        //When passengers pay, they have to be logged to a node with the taxi id number
        //this will be the node that we search when we want to check payments
        $text = "Here we list the people that have paid so far";
        $buttons = null;
        sendMessage($GLOBALS['message_url'], $driver_identity, $text, $buttons);
        $num = "one";
        Check_Payments($driver_identity,$num);
    } elseif ($driver_message == 'ADD STOP') {
        //here I must raise the falggs like before
        //these are the flaggs which allow a stop to be added
        //this means the already travelling flag may have to be deleted ?

        $ref = 'Entered_Correct_Taxi_ID';
        $postdata = [
            'tele_id' => $driver_identity
        ];
        $post = $GLOBALS['database']->getReference($ref)->push($postdata);

        //flag up driver_is_going_flagg 
        $postdata_driver_is_going = [
            'tele_id' => $driver_identity
        ];
        $ref_Drivergoing_flagging = 'DriverGoing_Flagg';
        $driverpost = $GLOBALS['database']->getReference($ref_Drivergoing_flagging)->push($postdata_driver_is_going);

        Add_Trip_Option($driver_identity, $driver_message);
    } elseif ($driver_message == 'END TRIP') {
        //this is when the already+travelling_taxi flagg in db is deleted
        //the travelling taxi data must also be shifted to another node and deleted from current node

        $text = "Trip Ended\r\n\r\nJust \"Say Hi\" if you need anything else:";
        //$buttons = [["Say Hi"]];
        sendMessage($GLOBALS['message_url'], $driver_identity, $text, $buttons);
        $num = "one";
        Check_Payments($driver_identity,$num);

        $ref = 'travelling_taxis';
        clea_r_e_rror_count($driver_identity, $ref);

        $ref = 'Already_Travelling_Taxi';
        clea_r_e_rror_count($driver_identity, $ref);

        $ref = "travelling_taxis";
        $k_ey = find_and_key($driver_identity, $ref);

        $data =  $GLOBALS['database']->getReference($ref)->getValue();
        //$child_keys = $GLOBALS['database']->getReference($ref)->getChildKeys();

        $postdata = $data[$k_ey];
        $r_ef = "Trips_Complete";
        $post = $GLOBALS['database']->getReference($r_ef)->push($postdata);
    }
    elseif($driver_message == "ADD FUEL/Cashout")
    {
        Fuel_Option($driver_identity);
    }
    elseif ($driver_message == 'EXIT') {
        $text = "Just say \"Hi\" if you need anything else. \r\n\r\nGoodbye!!!";
        $buttons = [["Say Hi"]];
        sendMessage($GLOBALS['message_url'], $driver_identity, $text, $buttons);
    } else {
        trip_details($driver_identity);

        $text = "This taxi is already travelling. Select an option from your keyboard below:";
        $buttons = [['CHECK PAYMENTS'],["ADD FUEL/Cashout"], ['ADD STOP'], ['END TRIP'], ['EXIT']];
        sendMessage($GLOBALS['message_url'], $driver_identity, $text, $buttons);
    }
}

function Fuel_Option($dr_iver_identity)
{
    $fuel_trasaction_code = bin2hex(random_bytes(5));
    
    date_default_timezone_set('Africa/Johannesburg');
    $d = date('y-m-d');
    $t = date('G:i:s');
    $time = $d.",".$t;
                
    $postdata = [
        'tele_id' => $dr_iver_identity,
        'time' => $time,
        'transaction_code' => $fuel_trasaction_code
    ];
    $ref = 'Driver_Fueling';
    $driverpost = $GLOBALS['database']->getReference($ref)->push($postdata);

    $text = "The transaction code is $fuel_trasaction_code \r\n\r\nNow wait for your pin to be prompted for payment.\r\nYou can select Cancel below to cancel transaction";
    $buttons = [['Cancel']];
    sendMessage($GLOBALS['message_url'], $dr_iver_identity, $text, $buttons);        
}

function check_driver_pin($t_identity,$tele_message)
{
    $D1ref = "Drivers";
    $reg_driver_key = find_and_key($t_identity, $D1ref);
    $driver_data =  $GLOBALS['database']->getReference($D1ref)->getValue();
    $pin = $driver_data[$reg_driver_key]["pin"];
            
    if($tele_message == $pin)
    {
    
        $ref = "Driver_to_Pay";
        $pay_amount_key = find_and_key($t_identity, $ref);
        $Amount_data =  $GLOBALS['database']->getReference($ref)->getValue();
        $amount = $Amount_data[$pay_amount_key]['Amount'];

        $driver_balance = $driver_data[$reg_driver_key]['Balance'];

        $ref = 'Driver_Pin_Error';
        clea_r_e_rror_count($t_identity, $ref);
        
        if($driver_balance >= $amount)
        {
            $transaction_confirmation_code = bin2hex(random_bytes(6));
            //update the driver bal
            $new_driver_balance = $driver_balance-$amount;

            $D2ref = $D1ref . '/' . $reg_driver_key;

            $p_data = [
                'Balance' => $new_driver_balance
            ];
            $post = $GLOBALS['database']->getReference($D2ref)->update($p_data);

            //now send message of success to driver
            $display_amount_paid = number_format((float)$amount, 2, '.', '');
            $display_bal = number_format((float)$new_driver_balance, 2, '.', '');

            $text = "You have successfully paid ZAR$display_amount_paid for fuel/cashout. \r\nConfirmation code is $transaction_confirmation_code\r\n\r\nYour new wallet balance is ZAR$display_bal";
            $buttons = null;
            sendMessage($GLOBALS['message_url'], $t_identity, $text, $buttons);  

            //update the agent balance
            $ref_node = "Agent_and_Driver_2";
            $var = "Customer_id";
            $idd = null;
            $key_to_agent_id = find_and_key_2($idd, $t_identity, $ref_node, $var);
            $Agent_Driver_data =  $GLOBALS['database']->getReference($ref_node)->getValue();
            $Agent_id = $Agent_Driver_data[$key_to_agent_id]['tele_id'];

            $ref = "Registered_Agent";
            $agent_key = find_and_key($Agent_id, $ref);
            $Agents_data = $GLOBALS['database']->getReference($ref)->getValue();
            $Agent_old_bal = $Agents_data[$agent_key]['Balance'];

            $new_Agent_bal = $Agent_old_bal + $amount;
            $Aref = $ref . '/' . $agent_key;

            $postdata = [
                'Balance' => $new_Agent_bal
            ];
            $post = $GLOBALS['database']->getReference($Aref)->update($postdata);

            $display_amount_received = number_format((float)$amount, 2, '.', '');
            $display_bal = number_format((float)$new_Agent_bal, 2, '.', '');

            // $ref = 'Agent_Fuel_Sell';
            // clea_r_e_rror_count($Agent_id, $ref);

            $text = "You have received ZAR$display_amount_received for fuel/cashout. \r\nConfirmation code is $transaction_confirmation_code\r\n\r\nYour new wallet balance is ZAR$display_bal";
            $buttons = [["Say Hi"]];
            sendMessage($GLOBALS['message_url'], $Agent_id, $text, $buttons);  
    
            $ref = 'Driver_Fueling';
            clea_r_e_rror_count($t_identity, $ref);
                    
            $ref = 'Driver_to_Pay';
            clea_r_e_rror_count($t_identity, $ref);
                    
            $ref = "Agent_and_Driver_2";
            $deleting_ref = $ref.'/'. $key_to_agent_id;
            $deleting = $GLOBALS['database']->getReference($deleting_ref)->remove();   
        }

        else{
            $text = "Failed!! \r\n \r\nYou have an inssuficient balance";
            $buttons = null;
            sendMessage($GLOBALS['message_url'], $t_identity, $text, $buttons);  
        }
    }
    else{
        //pin is not corerect
        $text = "pin is incorrect";
        $buttons = null;
        sendMessage($GLOBALS['message_url'], $t_identity, $text, $buttons);

        $error_ref = 'Driver_Pin_Error';
        $act_ref = "null";
        Check_Input_Error($t_identity, $error_ref,$act_ref);    
    }
 
}

function Show_Driver_Balance($t_identity,$tele_message)
{
    $D1ref = "Drivers";
    $reg_driver_key = find_and_key($t_identity, $D1ref);
    $driver_data =  $GLOBALS['database']->getReference($D1ref)->getValue();
    $pin = $driver_data[$reg_driver_key]["pin"];
            
    if($tele_message == $pin)
    {
        $ref = 'Driver_Pin_Error';
        clea_r_e_rror_count($t_identity, $ref);
        
        $ref = 'Driver_Wants_Wallet';
        clea_r_e_rror_count($t_identity, $ref);
        
        $driver_balance = $driver_data[$reg_driver_key]['Balance'];
        $display_bal = number_format((float)$driver_balance, 2, '.', '');
        
        $text = "Your wallet balance is $display_bal";
        $buttons = null;
        sendMessage($GLOBALS['message_url'], $t_identity, $text, $buttons);
    }
    
    else if($tele_message == "Exit")
    {
        $ref = 'Driver_Wants_Wallet';
        clea_r_e_rror_count($t_identity, $ref);
        
        $ref = 'Driver_Pin_Error';
        clea_r_e_rror_count($t_identity, $ref);
        
        $text = "Just say \"Hi\" if you need anything else. \r\n\r\nGoodbye!!!";
        $buttons = [["Say Hi"]];
        sendMessage($GLOBALS['message_url'], $t_identity, $text, $buttons);
        
    }

    else{
        //pin is not corerect
        $text = "pin is incorrect. \r\n\r\nSelect Exit to cancel";
        $buttons = [["Exit"]];
        sendMessage($GLOBALS['message_url'], $t_identity, $text, $buttons);

        $error_ref = 'Driver_Pin_Error';
        $act_ref = "null";
        Check_Input_Error($t_identity, $error_ref,$act_ref);    
    }
     
}
?>