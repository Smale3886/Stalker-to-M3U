<?php
include "config.php";
$playlist_path = $directories["playlist"];
$host_file = "$playlist_path/$host.m3u";

if (!file_exists($host_file)) {
    $token = generate_token();
    $url_ch = "http://$host/stalker_portal/server/load.php?type=itv&action=get_all_channels&JsHttpRequest=1-xml";
    $res = Info($url_ch, ["Authorization: Bearer $token"]);
    $data = json_decode($res["Info_arr"]["data"], true);
    
    $cats = group_title();
    if (!empty($data["js"]["data"])) {
        $m3u = "#EXTM3U\n";
        foreach ($data["js"]["data"] as $ch) {
            $catName = $cats[$ch['tv_genre_id']] ?? "General";
            [span_2](start_span)// Actual Channel Logo[span_2](end_span)
            $logo = "http://$host/stalker_portal/misc/logos/320/" . $ch['logo'];
            $id = str_replace(['ffrt http://localhost/ch/', ' '], '', $ch['cmd']);
            $play = "http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/play.php?id=".$id;
            
            $m3u .= '#EXTINF:-1 tvg-logo="'.$logo.'" group-title="'.$catName.'",'.$ch['name']."\n".$play."\n\n";
        }
        file_put_contents($host_file, $m3u);
    }
}
header('Content-Type: audio/x-mpegurl');
echo file_exists($host_file) ? file_get_contents($host_file) : "Error: No channels found.";
?>
