<?php

include 'db_connect.php';

$update   = json_decode(file_get_contents("php://input"), true);
$chatId   = $update["message"]["chat"]["id"];
$userId   = $update["message"]["from"]["id"];
$username = $update["message"]["from"]["username"] ?? "";
$text     = trim($update["message"]["text"]);


// $chat_file = __DIR__."/session_$chatId.json";
// file_put_contents($chat_file, json_encode($update));

function sendMessage($chatId, $text, $apiURL) {
    // file_get_contents($apiURL . "sendMessage?chat_id=$chatId&text=" . urlencode($text));
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
}

function sendDoc($chatId, $filename, $apiURL) {

    $post = [
        'chat_id' => $chatId,
        'document' => new CURLFile(realpath($filename))
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiURL . "sendDocument");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "cURL error: " . curl_error($ch);
    }
    curl_close($ch);
}

if(in_array($chatId, $allow_cmd_chat))
{

    $res = $conn->query("SELECT * FROM $query_table WHERE chat_id=$chatId AND user_id=$userId AND tg_session_status='active' ORDER BY id DESC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $state = json_decode($row["query_data"], true);
        $sessionId = $row["id"];
    } else {
        $state = ["step" => "start"];
        $sessionId = null;
    }

    switch (true) {
        case ($text == "/help" || strtolower($text) == "hi"):
            if ($sessionId) {
                $conn->query("UPDATE $query_table SET tg_session_status='end' WHERE id=$sessionId");
            } 
            $helpText = "📖 Available Commands:\n".
                        "1. /start - Create new report\n".
                        "2. /getall - Get all existing reports\n".
                        "3. /pause <report_id> - Pause a specific report\n".
                        "4. /help - Show this help message\n".
                        "5. /end - End any running operation";
            $helpText = htmlspecialchars($helpText, ENT_QUOTES, 'UTF-8');

            sendMessage($chatId, $helpText, $apiURL);

            break;
        
        case ($text == "/getall"):
            $sql = "SELECT id, report_id, query_type, query_data FROM $query_table WHERE query_status='A' AND tg_session_status='done'";
            $result_all = $conn->query($sql);

            if ($result_all->num_rows > 0) {
                $filename = "reports_" . date("Ymd_His") . ".csv";
                $fp = fopen($filename, 'w');

                fputcsv($fp, ["ID", "Report ID", "Report Type", "Filter IDs"]);
                while ($row = $result_all->fetch_assoc()) {
                    $filterData = json_decode($row["query_data"], true);

                    $filter_by = $filterData["filter_by"] ?? "";
                    fputcsv($fp, [
                        $row['id'],
                        $row["report_id"],
                        $row["query_type"],
                        $filter_by
                    ]);
                }

                fclose($fp);

                sendDoc($chatId, $filename, $apiURL);

            } else {
                sendMessage($chatId, "No Records Found", $apiURL);
            }
            
            break;
        case ($text == "/pause"):
            sendMessage($chatId, "@$username Please enter ID from exported sheet which one you want to pause ", $apiURL);
            $state = ["step" => "report_pause"];
            $conn->query("INSERT INTO $query_table (chat_id, user_id, username, query_data) 
            VALUES ($chatId, $userId, '$username', '" . $conn->real_escape_string(json_encode($state)) . "')");
            $sessionId = $conn->insert_id;
            break;
        case ($state["step"] == "report_pause"):
            if (is_numeric($text)) {
                $conn->query("UPDATE $query_table SET tg_session_status='end' WHERE id=$sessionId");
                $pause_query = "UPDATE $query_table SET query_status='I' WHERE id=$text";
                $state['report_auto_id'] = $text;
                if ($conn->query($pause_query) === TRUE) {
                    if ($conn->affected_rows > 0) {
                        sendMessage($chatId, "@$username Record updated successfully", $apiURL);
                    } else {
                        sendMessage($chatId, "@$username No record found with the given ID", $apiURL);
                    }
                }
                
            } else {
                sendMessage($chatId, "@$username Invalid option. Please enter correct numeric value.", $apiURL);
            }
            break;

        case ($text == "/start"):
            sendMessage($chatId, "@$username What type of report you want to generate?\n1. Sales\n2. LTV", $apiURL);
            $state = ["step" => "report_type"];
            $conn->query("INSERT INTO $query_table (chat_id, user_id, username, query_data) 
                            VALUES ($chatId, $userId, '$username', '" . $conn->real_escape_string(json_encode($state)) . "')");
            $sessionId = $conn->insert_id;
            break;

        case ($text == "/end"):
            if ($sessionId) {
                $conn->query("UPDATE $query_table SET tg_session_status='end' WHERE id=$sessionId");
                sendMessage($chatId, "@$username Your session has been ended manually.", $apiURL);
            } else {
                sendMessage($chatId, "@$username You don’t have an active session.", $apiURL);
            }
            break;

        case ($state["step"] == "report_type"):
            if ($text == "1" || $text == "2") {
                $state["report_type"] = ($text == "1") ? "sales" : "ltv";
                $state["step"] = "report_id";
                sendMessage($chatId, "@$username Enter report ID:", $apiURL);
            } else {
                sendMessage($chatId, "@$username Invalid option. Please choose 1 or 2.", $apiURL);
            }
            break;

        case ($state["step"] == "report_id"):
            if (is_numeric($text)) {
                $state["report_id"] = $text;
                $state["step"] = "filter_id";
                sendMessage($chatId, "@$username Enter filter ID:", $apiURL);
            } else {
                sendMessage($chatId, "@$username Please enter a valid numeric report ID.", $apiURL);
            }
            break;
        case ($state["step"] == "filter_id"):
            if (preg_match('/^(\d+,)*\d+$/', $text)) {
                $state["filter_by"] =  $text; 
                $state["step"] = "ruleset_id";

                sendMessage($chatId, "@$username Enter ruleset ID:", $apiURL);
            } else {
                // sendMessage($chatId, "@$username Please enter valid filter IDs as comma-separated numbers (e.g., 23,45,67).", $apiURL);
                sendMessage($chatId, "@$username Please enter a valid numeric ruleset ID.", $apiURL);
            }
            break;
        case ($state["step"] == "ruleset_id"):
            if (is_numeric($text)) {
                $state["ruleset_id"] =  $text; 
                $state["step"] = "done";

                $conn->query("UPDATE $query_table 
                                SET query_data='" . $conn->real_escape_string(json_encode($state)) . "', 
                                    tg_session_status='done' 
                                WHERE id=$sessionId");
        
                sendMessage($chatId, "@$username Thanks! Report created successfully with filters: " . implode(", ", $filters), $apiURL);
            } else {
                sendMessage($chatId, "@$username Please enter valid filter IDs as comma-separated numbers (e.g., 23,45,67).", $apiURL);
            }
            break;
    }



    // --- Save state if active ---
    if ($sessionId && $state["step"] != "done") {
        if(!empty($state['report_id']))
        {
            $conn->query("UPDATE $query_table 
                        SET report_id=".$state['report_id'].", query_data='" . $conn->real_escape_string(json_encode($state)) . "' 
                        WHERE id=$sessionId");
        }
        if(!empty($state['report_type']))
        {
            $conn->query("UPDATE $query_table 
                        SET query_type='".$state['report_type']."', query_data='" . $conn->real_escape_string(json_encode($state)) . "' 
                        WHERE id=$sessionId");
        }
        $conn->query("UPDATE $query_table 
                        SET query_data='" . $conn->real_escape_string(json_encode($state)) . "' 
                        WHERE id=$sessionId");
    }


}
else
{
    echo 'no';
    sendMessage($chatId, "Your chat id is: $chatId", $apiURL);
}
?>