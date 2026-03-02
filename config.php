<?php
error_reporting(0);
$directories = [
  "data" => "data",
  "filter" => "data/filter",
  "playlist" => "data/playlist"
];

foreach ($directories as $key => $dir_path) {
  if (!is_dir($dir_path)) {
      mkdir($dir_path, 0777, true);
  }
}

$tokenFile = $directories["data"] . "/token.txt";
date_default_timezone_set("Asia/Kolkata");

$url = $mac = $sn = $device_id_1 = $device_id_2 = $sig = "";
$jsonFile = $directories["data"] . "/data.json";

if (file_exists($jsonFile)) {
    $jsonData = file_get_contents($jsonFile);
    $data = json_decode($jsonData, true);
    if ($data !== null) {
        $url = $data["url"] ?? "";
        $mac = $data["mac"] ?? "";
        $sn = $data["serial_number"] ?? "";
        $device_id_1 = $data["device_id_1"] ?? "";
        $device_id_2 = $data["device_id_2"] ?? "";
        $sig = $data["signature"] ?? "";
    }
}

$api = "263";
$host = parse_url($url)["host"];

function handshake() { 
  global $host;
  $Xurl = "http://$host/stalker_portal/server/load.php?type=stb&action=handshake&token=&JsHttpRequest=1-xml";
  $HED = [
    'User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3',
    'X-User-Agent: Model: MAG250; Link: WiFi',
    "Referer: http://$host/stalker_portal/c/",
    "Host: $host",
  ];
  $Info_Data = Info($Xurl,$HED);
  $json = json_decode($Info_Data["Info_arr"]["data"], true);
  return [
    "token" => $json["js"]["token"] ?? "",
    "random" => $json["js"]["random"] ?? ""
  ];
}

function generate_token() {
  global $tokenFile, $host, $mac;
  $handshake = handshake();
  $token = $handshake["token"];
  
  $Xurl = "http://$host/stalker_portal/server/load.php?type=stb&action=handshake&token=$token&JsHttpRequest=1-xml";
  $HED = ['User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3'];
  $res = Info($Xurl, $HED);
  $final_token = json_decode($res["Info_arr"]["data"], true)["js"]["token"];
  
  get_profile($final_token);
  file_put_contents($tokenFile, $final_token);  
  return $final_token;
}

function get_profile($Bearer_token) {
  global $host, $sn, $device_id_1, $device_id_2, $sig, $api, $mac;
  $timestamp = time();
  $h = handshake();
  $Xurl = "http://$host/stalker_portal/server/load.php?type=stb&action=get_profile&hd=1&sn=$sn&stb_type=MAG250&device_id=$device_id_1&device_id2=$device_id_2&signature=$sig&auth_second_step=1&timestamp=$timestamp&api_signature=$api&metrics=%7B%22mac%22%3A%22$mac%22%2C%22random%22%3A%22".$h['random']."%22%7D&JsHttpRequest=1-xml";
  $HED = ["Authorization: Bearer $Bearer_token", "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3"];
  Info($Xurl, $HED);
}

function Info($Xurl, $HED) {
  global $mac;
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => $Xurl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $HED,
    CURLOPT_COOKIE => "mac=$mac; stb_lang=en; timezone=GMT",
    CURLOPT_ENCODING => 'gzip',
  ]);
  $data = curl_exec($ch);
  $status = curl_getinfo($ch);
  curl_close($ch);
  return ["Info_arr" => ["data" => $data, "info" => $status]];
}

function group_title($all = false) {
  global $host, $directories;
  $filter_file = $directories["filter"] . "/$host.json";
  if (file_exists($filter_file)) {
      $json_data = json_decode(file_get_contents($filter_file), true);
      if (!empty($json_data)) {
          if ($all) return array_column($json_data, 'title', 'id');
          return array_column(array_filter($json_data, function ($item) { return $item['filter'] === true; }), 'title', 'id');
      }
  }
  return []; 
}
?>
