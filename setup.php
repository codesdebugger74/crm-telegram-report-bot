<?php

include 'db_connect.php';

$tbl_report_query = "
CREATE TABLE IF NOT EXISTS $query_table (
  id int(255) NOT NULL AUTO_INCREMENT,
  query_data longtext DEFAULT NULL,
  report_id bigint(20) DEFAULT NULL,
  query_status enum('A','I','D') DEFAULT 'A',
  query_type enum('sales','ltv') DEFAULT NULL,
  created_date datetime DEFAULT current_timestamp(),
  updated_date datetime NOT NULL DEFAULT current_timestamp(),
  check_query_status int(255) DEFAULT 0,
  last_sent_at date DEFAULT NULL,
  chat_id bigint(20) DEFAULT NULL,
  user_id bigint(20) DEFAULT NULL,
  username varchar(255) DEFAULT NULL,
  tg_session_status enum('active','done','end') DEFAULT 'active',
  created_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
";

if ($conn->query($tbl_report_query) === TRUE) {
    echo '<br>';
    echo "Table $query_table is ready.";
} else {
    echo '<br>';
    echo "Error creating table: " . $conn->error;
}


$tbl_report_data = "
CREATE TABLE IF NOT EXISTS $report_table (
  `auto_report_id` int NOT NULL AUTO_INCREMENT,
  `report_date` date DEFAULT NULL,
  `date_range` varchar(255) DEFAULT NULL,
  `check_status` int DEFAULT 0,
  `query_id` int DEFAULT NULL,
  `crm_payload` longtext DEFAULT NULL,
  `created_date` datetime DEFAULT current_timestamp(),
  `updated_date` datetime DEFAULT NULL,
  `crm_response` longtext DEFAULT NULL,
  `final_data` longtext DEFAULT NULL,
  `tg_response` longtext DEFAULT NULL,
  PRIMARY KEY (`auto_report_id`),
  UNIQUE KEY `unique_report` (`query_id`,`report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
";

// Execute query
if ($conn->query($tbl_report_data) === TRUE) {
    echo '<br>';
    echo "Table $report_table is ready";
} else {
    echo '<br>';
    echo "Error creating table: " . $conn->error;
}

// Close connection
$conn->close();

echo '<br>';
echo '<br>';
// $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$scheme = "https";
$currentUrl = $scheme . "://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
$baseUrl = rtrim(dirname($currentUrl), '/\\');
// echo "Current URL: " . $currentUrl . "<br>";
// echo "Base URL: " . $baseUrl;
echo '<br>';
$final_bot_link = $apiURL."setWebhook?url=".$baseUrl."/bot_cmd.php";
// header("Location: ".$final_bot_link);
echo "<a href='$final_bot_link' target='_blank'>Click here to set webhook</a>";

echo "<script type='text/javascript'>
        window.open('$final_bot_link', '_blank');
      </script>";