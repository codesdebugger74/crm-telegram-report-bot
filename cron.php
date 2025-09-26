<?php
require_once('vendor/autoload.php');

include 'db_connect.php';
$client = new \GuzzleHttp\Client();
use GuzzleHttp\Exception\ClientException;

// echo date('Y-m-d H:i:s'); 
$yesterday = date("Y-m-d", strtotime("-1 day"));

$currentHour = (int)date('H'); 


function formatReportHeader($data, $report_date_range) {
    $text = "📈 LTV Report Summary for Warp Speed (140)\n\n";
    $text .= "🗓 Report Date: $report_date_range (EST)\n\n";
    $text .= "🔍 Filtered Tracking IDs\n";
    $text .= "📝 Filtered By: {$data['tracking_type']}\n";
    $text .= "💰 Avg LTV : $ {$data['avgLTV']}\n";
    $text .= "👥 Total Customers: {$data['total_customers']}\n";
    $text .= "📊 Avg Rev per Customer : $ {$data['avgRev']}\n\n";
    return $text;
}

function formatClient($client) {
    $text = "------------------------\n";
    $text .= "🆔 Tracking ID: {$client['client_id']}\n";
    $text .= "📦 Sales Volume: {$client['sales_volume']}\n";
    $text .= "💵 LTV: $" . number_format($client['ltv'],2) . "\n";
    $text .= "------------------------\n";
    return $text;
}


// Send message in chunks to avoid 4096 char limit
function sendLongTelegramMessage($chatId, $data, $report_date_range, $apiURL, $enableLog) {
    // echo 'in tg fun';
    $header = formatReportHeader($data, $report_date_range);
    $messages = [];
    $currentMessage = $header;
    // echo $currentMessage;

    foreach ($data['filteredClients'] as $client) {
        $clientText = formatClient($client);

        // If adding this client exceeds Telegram limit, start new message
        if (strlen($currentMessage . $clientText) > 4000) { // leave small buffer
            $messages[] = $currentMessage;
            $currentMessage = $clientText;
        } else {
            $currentMessage .= $clientText;
        }
    }
    // Add remaining
    if (!empty($currentMessage)) {
        $messages[] = $currentMessage;
    }
    // Send all chunks
    $url = $apiURL."sendMessage";
    $tg_response = [];
    foreach ($messages as $msg) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'HTML' // optional
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        // Decode JSON response
        $result = json_decode($response, true);

        if($enableLog)
        {
    
            if ($response === FALSE) {
                $tg_response = [
                    "success" => false,
                    "message" => "Failed to contact Telegram API"
                ];
            }
        
            $result = json_decode($response, true);
        
            if (isset($result['ok']) && $result['ok'] === true) {
                $tg_response = [
                    "success" => true,
                    "message" => "Message sent successfully",
                    "telegram_response" => $result
                ];
            } else {
                $error = isset($result['description']) ? $result['description'] : "Unknown error";
                $tg_response = [
                    "success" => false,
                    "message" => "Failed to send message",
                    "error"   => $error,
                    "telegram_response" => $result
                ];
            }
        }

        
    }

    return $tg_response;


}


function sendMessage($chatId, $text, $apiURL, $enableLog) {
    // $response = file_get_contents($apiURL . "sendMessage?chat_id=$chatId&text=" . urlencode($text));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiURL.'sendMessage');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML' // optional
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    // Decode JSON response
    $result = json_decode($response, true);

    if($enableLog)
    {

        if ($response === FALSE) {
            return [
                "success" => false,
                "message" => "Failed to contact Telegram API"
            ];
        }
    
        $result = json_decode($response, true);
    
        if (isset($result['ok']) && $result['ok'] === true) {
            return [
                "success" => true,
                "message" => "Message sent successfully",
                "telegram_response" => $result
            ];
        } else {
            $error = isset($result['description']) ? $result['description'] : "Unknown error";
            return [
                "success" => false,
                "message" => "Failed to send message",
                "error"   => $error,
                "telegram_response" => $result
            ];
        }
    }
}

if ($currentHour < $alert_time_for_generate_repot) {
    echo 'time not come';
    exit;
}

$check = $conn->query("SELECT COUNT(*) as cnt FROM $report_table WHERE report_date = '$yesterday'");
$row   = $check->fetch_assoc();

if($row['cnt'] == 0)
{
    $sql = "INSERT INTO $report_table (query_id, report_date)
    SELECT id, '$yesterday'
    FROM $query_table
    WHERE query_status='A' AND tg_session_status='done'";
    if ($conn->query($sql) === TRUE) {
        echo "Report generated for $yesterday";
    } else {
        echo "Error: " . $conn->error;
    }
}
else{

    $sql = "SELECT *
        FROM $query_table
        INNER JOIN $report_table ON $query_table.id = $report_table.query_id
        WHERE $report_table.check_status = 0
        LIMIT 1";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            // CRM Check      
            $check_crm_api = false;
            $report_date_range = '';
            if($row['query_type'] == 'sales')
            {
                $query_data = json_decode($row['query_data'], true);
                $report_id = $query_data['report_id'];
                $crm_payload = [
                    'filter_by' => $query_data['filter_by'],
                    'ruleset_id'=>$query_data['ruleset_id']
                ];
                $crm_payload['date_begin'] = $row['report_date']." 00:00:00";
                $crm_payload['date_end'] = $row['report_date']." 00:00:00";

                $check_crm_api = true;
                $report_date_range = date('d/m/Y', strtotime($row['report_date']));;
            } 


            if($row['query_type'] == 'ltv')
            {
                echo '<br>';
                echo 'ltv';

                $lastSent = $row['last_sent_at'];

                if (is_null($lastSent) || strtotime($lastSent) <= strtotime("-14 days")) {

                    $query_data = json_decode($row['query_data'], true);
                    $report_id = $query_data['report_id'];
                    $crm_payload = [
                        'filter_by' => $query_data['filter_by'],
                        'ruleset_id'=>$query_data['ruleset_id']
                    ];

                    $ydate = $row['report_date']." 00:00:00";
                    $before14 = date('Y-m-d H:i:s', strtotime($ydate . ' -19 days'));
                    $report_date_start = date('d/m/Y', strtotime($ydate . ' -19 days'));
                    $crm_payload['date_begin'] = $before14;

                    $before6 = date('Y-m-d H:i:s', strtotime($ydate . ' -5 days'));
                    $report_date_end = date('d/m/Y', strtotime($ydate . ' -5 days'));
                    $crm_payload['date_end'] = $before6;

                    $conn->query("UPDATE $query_table SET last_sent_at='".$row['report_date']."' WHERE id=".$row['id']);
                    $check_crm_api = true;
                    $report_date_range = $report_date_start.' - '.$report_date_end;
                }
                else
                {
                    $conn->query("UPDATE $report_table SET check_status=99 WHERE auto_report_id=".$row['auto_report_id']);
                }


            }  
             
            if($check_crm_api == true)
            {
                try {
                    $response = $client->request('GET', API_URL.'/reports/'.$report_id.'/data?'.http_build_query($crm_payload), [
                    'headers' => [
                        'X-Api-Key' => API_KEY,
                        'accept' => 'application/json',
                    ],
                    ]);
                    
                    $conn->query("UPDATE $report_table SET check_status=1, crm_payload='".$conn->real_escape_string(json_encode($crm_payload))."', crm_response='".$conn->real_escape_string($response->getBody())."', date_range='".$report_date_range."' WHERE auto_report_id=".$row['auto_report_id']);
    
                } catch (ClientException $e) {
                    $response = $e->getResponse();
                    $errorBody = $response ? $response->getBody()->getContents() : $e->getMessage();
                    
                    // echo $errorBody;
                    $conn->query("UPDATE $report_table SET check_status=2, crm_payload='".$conn->real_escape_string(json_encode($crm_payload))."',crm_response='".$conn->real_escape_string($errorBody)."' WHERE auto_report_id=".$row['auto_report_id']);
                }
            }
        }
    } else {
        // Ready Data for Sending Alert
        $sql_ready = "SELECT *
        FROM $query_table
        INNER JOIN $report_table ON $query_table.id = $report_table.query_id
        WHERE $report_table.check_status = 1
        LIMIT 1";
        
        $result_ready = $conn->query($sql_ready);
        if ($result_ready && $result_ready->num_rows > 0) {
            while ($row_ready = $result_ready->fetch_assoc()) {
                if($row_ready['query_type'] == 'sales')
                {
                    echo 'sales data';
                    $crm_responsev = json_decode($row_ready['crm_response'], true);

                    $final_data = [];

                    foreach ($crm_responsev['data'] as $key => $value) {
                        echo $value['filters']['Initial Charge Primary Offer Category'];
                        if($value['filters']['Initial Charge Primary Offer Category'] == 'Step 1')
                        {
                            echo "s1";   
                            foreach ($value['columns'] as $key1 => $value1) {
                                if($value1['key'] == '_stock_plc_captures')
                                {
                                    $final_data['s1'] = (float) str_replace([',', '$'], '', $value1['value']);
                                }
                            } 
                        }
                        if($value['filters']['Initial Charge Primary Offer Category'] == 'Step 2')
                        {
                            echo "s2";   
                            foreach ($value['columns'] as $key1 => $value1) {
                                if($value1['key'] == '_stock_plc_captures')
                                {
                                    $final_data['s2'] = (float) str_replace([',', '$'], '', $value1['value']);
                                }
                            } 
                        }
                    }
                    $final_data['take_rate'] = number_format(($final_data['s2']/$final_data['s1'])*100,2)."%";


                    if(!empty($final_data))
                    {
                        $conn->query("UPDATE $report_table SET check_status=3, final_data='".$conn->real_escape_string(json_encode($final_data))."' WHERE auto_report_id=".$row_ready['auto_report_id']);
                    }
                    else
                    {
                        sendMessage($report_chat_id, "No data found", $apiURL, $enableLog);
                        $conn->query("UPDATE $report_table SET check_status=4, final_data='No data found' WHERE auto_report_id=".$row_ready['auto_report_id']);
                    }
                }
                if($row_ready['query_type'] == 'ltv')
                {
                    // echo 'ltv data';
                    $crm_responsev = json_decode($row_ready['crm_response'], true);


                    $final_data = [];
                    $last_final_data = [];
                    $check_data_count = 0;

                    foreach ($crm_responsev['data'] as $key => $value) {
                        $local_arr = [];

                        $track_id = reset($value['filters']);
                        $local_arr['client_id'] = $track_id;

                        $data_key = array_key_first($value['filters']); 
                        if (preg_match('/<track>(.*?)<\/track>/', $data_key, $matches) && $check_data_count==0) {
                            $track_type = $matches[1];
                            echo "track_type=" . $track_type; 
                            $last_final_data['tracking_type'] = $track_type;
                            $check_data_count++;
                        }

                        foreach ($value['columns'] as $key1 => $value1) {
                            if($value1['key'] == '_stock_saleltv_orders')
                            {
                                $local_arr['sales_volume'] = (int) str_replace([',', '$'], '', $value1['value']);
                            }
                            if($value1['key'] == '_stock_saleltv_cart_rev')
                            {
                                $local_arr['ltv'] = (float) str_replace([',', '$'], '', $value1['value']);
                            }

                            // get all initial customer
                            if($value1['key'] == '_stock_saleltv_new_cus')
                            {
                                $local_arr['new_cust'] = (int) str_replace([',', '$'], '', $value1['value']);
                            }
                            if($value1['key'] == '_stock_saleltv_init_rev')
                            {
                                $local_arr['total_initial_rev'] = (float) str_replace([',', '$'], '', $value1['value']);
                            }
                            if($value1['key'] == '_stock_saleltv_init_rev_po')
                            {
                                $local_arr['ref_per_cust'] = (float) str_replace([',', '$'], '', $value1['value']);
                            }

                        }


                        $final_data[] = $local_arr;
                    }

                    foreach ($crm_responsev['total_data']['columns'] as $key => $value) {

                        if($value['key'] == '_stock_saleltv_new_cus')
                        {
                            $last_final_data['total_customers'] = (int) str_replace([',', '$'], '', $value['value']);
                        }
                        if($value['key'] == '_stock_saleltv_rev_pc')
                        {
                            $last_final_data['avgRev'] = (float) str_replace([',', '$'], '', $value['value']);
                        }
                    }

                    $clients = $final_data;
                    
                    $totalLTV = 0;
                    foreach ($clients as $c) {
                        $totalLTV += $c['ltv'];
                    }
                    $avgLTV = number_format($totalLTV / count($clients),2);

                    $minVolume = 0; // Need to ask client

                    $filteredClients = [];
                    foreach ($clients as $c) {
                        if ($c['sales_volume'] >= $minVolume) {
                            if ($c['ltv'] >= $avgLTV) {
                                $c['status'] = 'Above Average LTV';
                            } else {
                                $c['status'] = 'Below Average LTV';
                            }
                            $filteredClients[] = $c;
                        }

                    }


                    $last_final_data['avgLTV'] = $avgLTV;
                    $last_final_data['filteredClients'] = $filteredClients;

                    if(!empty($last_final_data))
                    {
                        $conn->query("UPDATE $report_table SET check_status=3, final_data='".$conn->real_escape_string(json_encode($last_final_data))."' WHERE auto_report_id=".$row_ready['auto_report_id']);
                    }
                    else
                    {
                        sendMessage($report_chat_id, "No data found", $apiURL, $enableLog);
                        $conn->query("UPDATE $report_table SET check_status=4, final_data='No data found' WHERE auto_report_id=".$row_ready['auto_report_id']);
                    }


                    
                }
            }
        }
        else{
            if ($currentHour < $alert_time_for_send_repot) {
                echo 'alert time not come';
                exit;
            }
            echo "now send report to tg";
            $sql_tg = "SELECT *
            FROM $query_table
            INNER JOIN $report_table ON $query_table.id = $report_table.query_id
            WHERE $report_table.check_status = 3
            LIMIT 1";
            $result_tg = $conn->query($sql_tg);
            if ($result_tg && $result_tg->num_rows > 0) {
                while ($row_tg = $result_tg->fetch_assoc()) {
                    
                    $final_data = json_decode($row_tg['final_data'], true);
                    if(json_last_error() === JSON_ERROR_NONE)
                    {
                        
                        if($row_tg['query_type'] == 'sales')
                        {
                            $message = "📈 Sales Report Summary for Warp Speed (".$row_tg['report_id'].")\n\n";
                            $message .= "🗓 Report Date: ". $row_tg['date_range']." (".$time_zone.")"."\n";
                            $message .= "🆕 Overall New S1: ". $final_data['s1']."\n";
                            $message .= "🔼 Upsells: ". $final_data['s2']."\n";
                            $message .= "💵 Take Rate: ". $final_data['take_rate'];
                            $tg_response = sendMessage($report_chat_id, $message, $apiURL, $enableLog=true);
                            
                            if($tg_response['success'] == true)
                            {
                                $conn->query("UPDATE $report_table SET check_status=5, tg_response='".$conn->real_escape_string(json_encode($tg_response))."' WHERE auto_report_id=".$row_tg['auto_report_id']);
                            }
                            else
                            {
                                $conn->query("UPDATE $report_table SET check_status=6, tg_response='".$conn->real_escape_string(json_encode($tg_response))."' WHERE auto_report_id=".$row_tg['auto_report_id']);
                            }

                        }
                        else if($row_tg['query_type'] == 'ltv')
                        {

                            // echo '<pre>';print_r($final_data);
                            // echo 'in';

                            // formatReportHeader($final_data, $report_date_range);

                            $tg_response = sendLongTelegramMessage($report_chat_id, $final_data, $row_tg['date_range'], $apiURL, $enableLog=true);
                            if($tg_response['success'] == true)
                            {
                                $conn->query("UPDATE $report_table SET check_status=5, tg_response='".$conn->real_escape_string(json_encode($tg_response))."' WHERE auto_report_id=".$row_tg['auto_report_id']);
                            }
                            else
                            {
                                $conn->query("UPDATE $report_table SET check_status=6, tg_response='".$conn->real_escape_string(json_encode($tg_response))."' WHERE auto_report_id=".$row_tg['auto_report_id']);
                            }

                            // $message = "📊 LTV Report Summary for Warp Speed (".$row_tg['report_id'].")\n\n";
                            // $message .= "🆕 Date: ". $row_tg['date_range']." (".$time_zone.")"."\n";
                            // $message .= "Filtered Clients:\n";
                            // $message .= "📊 Filtered By: " . $final_data["tracking_type"] . "\n";
                            // $message .= "📊 Avg LTV: " . $final_data["avgLTV"] . "\n";
                            // $message .= "📊 Total Customers: " . $final_data["total_customers"] . "\n";
                            // $message .= "📊 Avg Rev per Customer: " . number_format($final_data["avgRev"],2) . "\n\n";
                            // $message .= "------------------------\n";
                            
                            // foreach ($final_data['filteredClients'] as $client) {
                            //     $message .= "🆔 Tracking ID: " . $client["client_id"] . "\n";
                            //     $message .= "📦 Sales Volume: " . $client["sales_volume"] . "\n";
                            //     $message .= "💰 LTV: " . number_format($client["ltv"],2) . "\n";
                            //     $message .= "------------------------\n";
                            // }
                            // $tg_response = sendMessage($report_chat_id, $message, $apiURL, $enableLog=true);

                            // if($tg_response['success'] == true)
                            // {
                            //     $conn->query("UPDATE $report_table SET check_status=5, tg_response='".$conn->real_escape_string(json_encode($tg_response))."' WHERE auto_report_id=".$row_tg['auto_report_id']);
                            // }
                            // else
                            // {
                            //     $conn->query("UPDATE $report_table SET check_status=6, tg_response='".$conn->real_escape_string(json_encode($tg_response))."' WHERE auto_report_id=".$row_tg['auto_report_id']);
                            // }
                        }
                    }
                    else
                    {
                        $tg_response = sendMessage($report_chat_id, $row_tg['final_data'], $apiURL, $enableLog=true);

                        if($tg_response['success'] == true)
                        {
                            $conn->query("UPDATE $report_table SET check_status=7, tg_response='".$conn->real_escape_string(json_encode($tg_response))."' WHERE auto_report_id=".$row_tg['auto_report_id']);
                        }
                        else
                        {
                            $conn->query("UPDATE $report_table SET check_status=8, tg_response='".$conn->real_escape_string(json_encode($tg_response))."' WHERE auto_report_id=".$row_tg['auto_report_id']);
                        }
                        return false;
                    }
                    
                }

            }
            else
            {
                echo '<br>';
                echo 'other tasks';
                $conn->query("DELETE FROM $query_table WHERE created_date LIKE '%". $yesterday."%' AND (tg_session_status='end' OR tg_session_status='active') LIMIT 10");
                $conn->query("DELETE FROM $report_table WHERE created_date LIKE '%". $yesterday."%' AND check_status=99 LIMIT 10");

                // echo '<br>';
                // echo $base_url.'url_check.php';

                // $curl = curl_init();
                // curl_setopt_array($curl, array(
                //     CURLOPT_URL => $base_url.'url_check.php',
                //     CURLOPT_RETURNTRANSFER => true,
                //     CURLOPT_ENCODING => '',
                //     CURLOPT_MAXREDIRS => 10,
                //     CURLOPT_TIMEOUT => 0,
                //     CURLOPT_FOLLOWLOCATION => true,
                //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                //     CURLOPT_CUSTOMREQUEST => 'GET',
                // ));

                // $response = curl_exec($curl);
                // curl_close($curl);
                // echo $response;

                $url = $base_url.'url_check.php';             // <- URL to monitor
                $method = 'GET';                           // 'HEAD' or 'GET' (use GET if server blocks HEAD)
                $timeout = 8;                              // seconds for connect+response (tune as needed)
                $retries = 2;                              // number of quick retries before declaring down
                $stateFile = __DIR__ . '/check_url.state';// state file to avoid duplicate alerts
                $lockFile = __DIR__ . '/check_url.lock';  // lock file to prevent parallel runs
                
                // Telegram config:
                $token = $botToken; // -> replace with your bot token
                $chat_id = $report_chat_id;                               // -> replace with your chat id (group or user)
                
                // ------------ helpers -------------
                function sendTelegram($token, $chat_id, $text) {
                    $text = trim($text);
                    $url = "https://api.telegram.org/bot{$token}/sendMessage";
                    $data = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML'];
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    $resp = curl_exec($ch);
                    $err = curl_error($ch);
                    $info = curl_getinfo($ch);
                    curl_close($ch);
                    return ['resp' => $resp, 'err' => $err, 'info' => $info];
                }
                
                function urlIsUp($url, $method = 'GET', $timeout = 8) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_NOBODY, $method === 'HEAD');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, max(2, (int)$timeout/2));
                    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
                    // Optional: skip verifying cert if you have self-signed certs (not recommended)
                    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $response = curl_exec($ch);
                    $errno = curl_errno($ch);
                    $http_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                    curl_close($ch);
                
                    if ($errno) {
                        return ['up' => false, 'reason' => "cURL error {$errno}"];
                    }
                
                    // consider 200-399 as "up" (treat redirects OK)
                    $up = ($http_code >= 200 && $http_code < 400);
                    return ['up' => $up, 'http_code' => $http_code];
                }
                
                // ------------ lock (prevents concurrent runs) -------------
                $lockFp = fopen($lockFile, 'c');
                if (!$lockFp) {
                    // can't open lock, proceed but warn
                    error_log("Warning: cannot open lock file {$lockFile}");
                } else {
                    if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
                        // another process is running
                        // exit silently (or uncomment next line if you want logging)
                        // error_log("Another check is running. Exiting.");
                        exit;
                    }
                }
                
                // ------------ main logic -------------
                $downCount = 0;
                $finalResult = null;
                for ($i = 0; $i <= $retries; $i++) {
                    $res = urlIsUp($url, $method, $timeout);
                    if ($res['up']) {
                        $finalResult = $res;
                        break;
                    } else {
                        $downCount++;
                        // small sleep between retries (only if we will retry)
                        if ($i < $retries) usleep(300000); // 300ms
                        $finalResult = $res;
                    }
                }
                
                // read previous state
                $prevState = 'unknown';
                if (file_exists($stateFile)) {
                    $prevState = trim(@file_get_contents($stateFile));
                }
                
                // determine current state
                $currentState = ($finalResult['up'] ?? false) ? 'up' : 'down';
                
                // if state changed, send telegram
                if ($currentState !== $prevState) {
                    if ($currentState === 'down') {
                        $msg = "<b>ALERT:</b> {$url} is DOWN.\n";
                        if (isset($finalResult['http_code'])) {
                            $msg .= "HTTP code: " . ($finalResult['http_code'] ?: 'N/A') . "\n";
                        }
                        if (isset($finalResult['reason'])) {
                            $msg .= "Reason: " . $finalResult['reason'] . "\n";
                        }
                        $msg .= "Checked at: " . date('Y-m-d H:i:s');
                        sendTelegram($token, $chat_id, $msg);
                    } else {
                        $msg = "<b>RECOVERY:</b> {$url} is UP again.\nChecked at: " . date('Y-m-d H:i:s');
                        sendTelegram($token, $chat_id, $msg);
                    }
                    // write new state
                    @file_put_contents($stateFile, $currentState, LOCK_EX);
                }
                
                // If you want, you can log a local message every run (optional):
                $logLine = date('Y-m-d H:i:s') . " - {$url} - state={$currentState}";
                if (isset($finalResult['http_code'])) $logLine .= " http={$finalResult['http_code']}";
                if (isset($finalResult['reason'])) $logLine .= " reason={$finalResult['reason']}";
                $logLine .= PHP_EOL;
                @file_put_contents(__DIR__ . '/check_url.log', $logLine, FILE_APPEND | LOCK_EX);
                
                // release lock
                if ($lockFp) {
                    flock($lockFp, LOCK_UN);
                    fclose($lockFp);
                }

            }
        }

    }


}


$conn->close();
?>

