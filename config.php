<?php
error_reporting(0);
$directories = ["data" => "data", "filter" => "data/filter", "playlist" => "data/playlist"];
foreach ($directories as $dir) { if (!is_dir($dir)) mkdir($dir, 0777, true); }

$tokenFile = $directories["data"] . "/token.txt";
$jsonFile = $directories["data"] . "/data.json";
date_default_timezone_set("Asia/Kolkata");

if (file_exists($jsonFile)) {
    $data = json_decode(file_get_contents($jsonFile), true);
    $url = $data["url"]; $mac = $data["mac"]; $sn = $data["serial_number"];
    $device_id_1 = $data["device_id_1"]; $device_id_2 = $data["device_id_2"]; $sig = $data["signature"];
}
$host = parse_url($url)["host"];

function handshake() { 
    global $host;
    $Xurl = "http://$host/stalker_portal/server/load.php?type=stb&action=handshake&token=&JsHttpRequest=1-xml";
    $res = Info($Xurl, []);
    $json = json_decode($res["Info_arr"]["data"], true);
    return ["token" => $json["js"]["token"], "random" => $json["js"]["random"]];
}

function generate_token() {
    global $tokenFile, $host;
    $h = handshake();
    $Xurl = "http://$host/stalker_portal/server/load.php?type=stb&action=handshake&token=".$h['token']."&JsHttpRequest=1-xml";
    $res = Info($Xurl, []);
    $final_token = json_decode($res["Info_arr"]["data"], true)["js"]["token"];
    get_profile($final_token);
    file_put_contents($tokenFile, $final_token);  
    return $final_token;
}

function get_profile($token) {
    global $host, $sn, $device_id_1, $device_id_2, $sig, $mac;
    $h = handshake();
    $Xurl = "http://$host/stalker_portal/server/load.php?type=stb&action=get_profile&sn=$sn&stb_type=MAG250&device_id=$device_id_1&device_id2=$device_id_2&signature=$sig&metrics=%7B%22mac%22%3A%22$mac%22%2C%22random%22%3A%22".$h['random']."%22%7D&JsHttpRequest=1-xml";
    Info($Xurl, ["Authorization: Bearer $token"]);
}

function Info($url, $hed) {
    global $mac;
    $h = array_merge(["User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3"], $hed);
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => "mac=$mac", CURLOPT_ENCODING => 'gzip', CURLOPT_TIMEOUT => 20]);
    $d = curl_exec($ch); curl_close($ch);
    return ["Info_arr" => ["data" => $d]];
}

function group_title($all = false) {
    global $host, $directories;
    $file = $directories["filter"] . "/$host.json";
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        return array_column($all ? $data : array_filter($data, fn($i) => $i['filter']), 'title', 'id');
    }
    return [];
}
?>
