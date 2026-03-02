<?php
include "config.php";

$token = (file_exists($tokenFile)) ? file_get_contents($tokenFile) : generate_token();

function fetchStreamUrl($channelId, $currentToken) {
    global $host, $mac, $tokenFile;
    $url = "http://$host/stalker_portal/server/load.php?type=itv&action=create_link&cmd=ffrt%20http://localhost/ch/$channelId&JsHttpRequest=1-xml";
    $headers = [
        "Authorization: Bearer $currentToken",
        "Cookie: mac=$mac",
        "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3"
    ];

    $res = Info($url, $headers);
    $data = json_decode($res["Info_arr"]["data"], true);

    if (!isset($data['js']['cmd'])) {
        $newToken = generate_token();
        $headers[0] = "Authorization: Bearer $newToken";
        $res = Info($url, $headers);
        $data = json_decode($res["Info_arr"]["data"], true);
    }
    return $data['js']['cmd'] ?? false;
}

if (!empty($_GET['id'])) {
    $stream = fetchStreamUrl($_GET['id'], $token);
    if ($stream) {
        header("Location: $stream");
        exit;
    }
}
die("Error: Link generation failed.");
?>
