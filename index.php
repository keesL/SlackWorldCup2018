<?php
include 'settings.php';

function send($msg) {
    global $channel;

    $data = json_encode(array(
       'channel' => $channel,
       'text' => $msg,
       'reply_broadcast' => TRUE
    ));
    $url = 'https://slack.com/api/chat.postMessage';
    exec("curl -X POST -H 'Authorization: Bearer $token' -H 'Content-type: application/json; charset=utf-8' --data '$data' $url", $ret);
}



$last=json_decode(file_get_contents('state'), $assoc=TRUE);
$data=json_decode(file_get_contents('http://worldcup.sfg.io/matches/current'));


foreach ($data as $game) {
    $id = $game->fifa_id;
    if (trim($id) == '') continue;
    $scorestr = $game->home_team->goals .   ' : '  .  $game->away_team->goals ;


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

    // Home team events
    $msg='';
    foreach ($game->home_team_events as $event) {
        if (!array_key_exists('event', $last[$id])) 
            $last[$id]['event'] = array();
   
        if (!array_key_exists($event->id, $last[$id]['event'])) {
            array_push($last[$id]['event'], $event->id);
            $msg .= '    '.str_replace("'", "min", $event->time) . ': ' . 
                $event->type_of_event. ' by '.
                $event->player. "\n";
        }
    }
    if (strlen($msg) > 0) {
        $msg = "\n". $game->home_team->country."\n".$msg;
    }

    // Away team events
    $msg2='';
    foreach ($game->away_team_events as $event) {
        if (!array_key_exists('event', $last[$id])) 
            $last[$id]['event'] = array();
   
        if (!array_key_exists($event->id, $last[$id]['event'])) {
            array_push($last[$id]['event'], $event->id);
            $msg2 .= '    '.str_replace("'", "min", $event->time) . ': ' . 
                $event->type_of_event. ' by '.
                $event->player. "\n";
       }
    }
   if (strlen($msg2) > 0) {
       $msg2 = "\n" . $game->away_team->country."\n".$msg2;
       $msg = $msg . $msg2;
   } 

   send($msg);
}

$f = fopen('state', 'w');
fputs($f, json_encode($last));
fclose($f);
?>
