<?php
date_default_timezone_set('America/New_York');
define('API_KEY', 'VRIO API Key');
define('API_URL', 'https://api.vrio.app');

$time_zone   = "EST";
$query_table = 'tbl_report_query';
$report_table = 'tbl_report_data';

// Step 1
$servername = "";
$username   = "";     
$password   = "";    
$database   = "";   

$base_url   = "Full URL Where have upload file";
$botToken = "Your Bot Token";
$apiURL   = "https://api.telegram.org/bot$botToken/";

// Step 2
$report_chat_id   = "Your chat id"; //Ignore for initial setup will add later
$allow_cmd_chat   = []; // Add multiple chat ids from where you can add report ids


?>