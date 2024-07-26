<?php
include('dumpper/dumpper.php');
$GLOBALS['direction'] = 1;

dump($GLOBALS['direction']);
$GLOBALS['direction'] = $GLOBALS['direction'] == 1 ? -1 : 1;
dump($GLOBALS['direction']);
$GLOBALS['direction'] = $GLOBALS['direction'] == 1 ? -1 : 1;
dump($GLOBALS['direction']);

exit();

$GLOBALS['game'] = array();
$json = '{
  "user": "giovanni",
  "cards": [
    {"value": "0", "color": "red" },
    {"value": "1", "color": "yellow" },
    {"value": "2", "color": "green" },
    {"value": "3", "color": "blue" },
    {"value": "4", "color": "red" },
    {"value": "5", "color": "yellow" },
    {"value": "6", "color": "green" },
    {"value": "7", "color": "blue" },
    {"value": "8", "color": "red" },
    {"value": "9", "color": "yellow" },
    {"value": "skip", "color": "green" },
    {"value": "reverse", "color": "blue" },
    {"value": "draw2", "color": "red" },
    {"value": "wild", "color": "black" },
    {"value": "wilddrawfour", "color": "black" }
  ]
}';

array_push($GLOBALS['game'], json_decode($json));

$json = '{
  "user": "Celular",
  "cards": [
    {"value": "0", "color": "red" },
    {"value": "1", "color": "yellow" },
    {"value": "2", "color": "green" },
    {"value": "3", "color": "blue" },
    {"value": "4", "color": "red" },
    {"value": "5", "color": "yellow" },
    {"value": "6", "color": "green" },
    {"value": "7", "color": "blue" },
    {"value": "8", "color": "red" },
    {"value": "9", "color": "yellow" },
    {"value": "skip", "color": "green" },
    {"value": "reverse", "color": "blue" },
    {"value": "draw2", "color": "red" },
    {"value": "wild", "color": "black" },
    {"value": "wilddrawfour", "color": "black" }
  ]
}';
array_push($GLOBALS['game'], json_decode($json));

$json = '{
  "user": "firefox",
  "cards": [
    {"value": "8", "color": "red" },
    {"value": "9", "color": "blue" }
  ]
}';

array_push($GLOBALS['game'], json_decode($json));

$GLOBALS['usersonline'] = ['giovanni', 'Celular', 'firefox'];
$GLOBALS['turnhasplayed'] = true;
$GLOBALS['turn'] = 'firefox';

if ($GLOBALS['turnhasplayed']) {
  $nextturn = -1;
  echo "Has played, pass turn";

  for ($i = 0; $i < count($GLOBALS['game']); $i++) {
    $currentuser = $GLOBALS['game'][$i];

    if ($nextturn == $i) {
      $GLOBALS['turnhasplayed'] = false;
      $GLOBALS['turn'] = $currentuser->user;
      break;
    }

    if ($currentuser->user == $GLOBALS['turn']) {
      $nextturn = $i + 1;

      //passed the array
      if ($nextturn > count($GLOBALS['game']) - 1) {
        $nextturn = 0;

        $GLOBALS['turnhasplayed'] = false;
        $GLOBALS['turn'] = $GLOBALS['game'][0]->user;
        break;
      }
    }
  }
}

dump($GLOBALS['turnhasplayed']);
dump($GLOBALS['turn']);
