<?php
include "config.php";
$id = $_GET['id'] ?? die("No ID");
$token = file_exists($tokenFile) ? file_get_contents($tokenFile) : generate_token();

function getLink($id, $tk) {
    global $host, $mac;
    $u = "http://$host/stalker_portal/server/load.php?type=itv&action=create_link&cmd=ffrt%20http://localhost/ch/$id&JsHttpRequest=1-xml";
    $r = Info($u, ["Authorization: Bearer $tk", "Cookie: mac=$mac"]);
    return json_decode($r["Info_arr"]["data"], true);
}

$res = getLink($id, $token);
if (!isset($res['js']['cmd'])) {
    $token = generate_token();
    $res = getLink($id, $token);
}

if ($res['js']['cmd']) {
    header("Location: " . $res['js']['cmd']);
} else {
    echo "Stream error.";
}
?>
