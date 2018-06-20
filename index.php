<?php
include 'settings.php';
include 'getflag.php';
/* Send to Slack using a shelled out curl. 
 * Needs to be improved, but since my hoster doesn't have
 * libcurl extensions loaded, this is what it is for now
 */
function send($msg) {
    global $channel;
    global $token;

    $data = json_encode(array(
       'channel' => $channel,
       'text' => $msg,
       'reply_broadcast' => TRUE
    ));
    $url = 'https://slack.com/api/chat.postMessage';
    exec("curl -X POST -H 'Authorization: Bearer $token' -H 'Content-type: application/json; charset=utf-8' --data '".escapeshellcmd($data)."' ".$url, $ret);
}



$last=json_decode(file_get_contents('state'), $assoc=TRUE);
$data=json_decode(file_get_contents('http://worldcup.sfg.io/matches/current'));

foreach ($data as $game) {
    $id = $game->fifa_id;
    if (trim($id) == '') continue;
    $scorestr = $game->home_team->goals.' : ' .$game->away_team->goals ;


    // Get current score
    if (array_key_exists($id, $last)) {
        if ($scorestr != $last[$id]['score']) {
            $last[$id]['score'] = $scorestr;

            send($game->home_team->country . ' vs ' . 
                $game->away_team->country . ' = '. $scorestr);
        }
    } else {
        $last[$id]['score'] = '0:0';
    } 

    if (!array_key_exists('event', $last[$id])) 
        $last[$id]['event'] = array();

    // Home team events
    $msg='';
    foreach ($game->home_team_events as $event) {
        $icon='';
        if (!in_array($event->id, $last[$id]['event'])) {
            array_push($last[$id]['event'], $event->id);
            if ($event == 'yellow-card') 
                $icon=':yellowcard:';
            elseif ($event == 'red-card') 
                $icon = ':redcard:';
            $msg .= '    '.str_replace("'", " min", $event->time) . ': ' . 
                $event->type_of_event. ' by '.
                $event->player. "$icon\n";
        }
    }
    if (strlen($msg) > 0) {
        $msg = "\n". $game->home_team->country." ".
            get2($game->home_team->country). "\n".$msg;
    }

    // Away team events
    $msg2='';
    foreach ($game->away_team_events as $event) {
        $icon='';
        if (!in_array($event->id, $last[$id]['event'])) {
            array_push($last[$id]['event'], $event->id);
            if ($event == 'yellow-card') 
                $icon=':yellowcard:';
            elseif ($event == 'red-card') 
                $icon = ':redcard:';
            $msg2 .= '    '.str_replace("'", " min", $event->time) . ': ' . 
                $event->type_of_event. ' by '.
                $event->player. "$icon\n";
        }
    }
    if (strlen($msg2) > 0) {
        $msg2 = "\n" . $game->away_team->country." ".
            get2($game->away_team->country). "\n".$msg2;
        $msg = $msg . $msg2;
    } 

    send($msg);
}

$f = fopen('state', 'w');
fputs($f, json_encode($last));
fclose($f);
?>
