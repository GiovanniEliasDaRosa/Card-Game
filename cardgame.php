<?php
// composer dumpautoload
include('dumpper/dumpper.php');

$possiblecards = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'skip', 'reverse', 'draw2', 'wild', 'wilddrawfour'];
$colors = ['red', 'yellow', 'green', 'blue'];
$GLOBALS['deck'] = array();
$deck = array();
$coloridx = 0;

// create 4x '0' cards, each color
for ($coloridx = 0; $coloridx < 4; $coloridx++) {
  $type = '0';
  $color = $colors[$coloridx];

  $card = "{'type': '$type','color': '$color'}";

  array_push($deck, json_encode($card));
}

// create 2x 1-9 cards, 2 each color
$coloridx = 0;
for ($currentcolor = 0; $currentcolor < 4; $currentcolor++) {
  for ($i = 1; $i < 10; $i++) {
    for ($quant = 0; $quant < 2; $quant++) {
      $type = $i;
      $color = $colors[$coloridx];

      $card = "{'type': '$type','color': '$color'}";

      array_push($deck, json_encode($card));
    }
  }
  $coloridx++;
}

// $specialCards = ['skip', 'reverse', 'draw2'];

// // create 2x 'special cards' each color
// for ($currentcolor = 0; $currentcolor < 4; $currentcolor++) {
//   for ($currentSpecialCard = 0; $currentSpecialCard < 3; $currentSpecialCard++) {
//     for ($quant = 0; $quant < 2; $quant++) {
//       $type = $specialCards[$currentSpecialCard];
//       $color = $colors[$currentcolor];

//       $card = "{'type': '$type','color': '$color'}";

//       array_push($deck, json_encode($card));
//     }
//   }
// }

// $blackCards = ['wild', 'wilddrawfour'];

// // create 4x 'black cards'
// for ($currentBlackCard = 0; $currentBlackCard < 2; $currentBlackCard++) {
//   for ($quant = 0; $quant < 4; $quant++) {
//     $type = $blackCards[$currentBlackCard];
//     $color = 'black';

//     $card = "{'type': '$type','color': '$color'}";

//     array_push($deck, json_encode($card));
//   }
// }
$GLOBALS['deck'] = $deck;

// $totaldecks = 1;
// while (count($stack) != 0) {
//   $pos = random_int(0, (count($stack) - 1));
//   $cardgot = array_splice($stack, $pos, 1);

//   echo "<pre>";
//   echo $pos . "-";
//   echo $cardgot[0] . "\n";

//   if (count($stack) == 0 && $totaldecks < 2) {
//     echo "<hr>";
//     $totaldecks++;
//     $stack = $deck;
//   }
// }

dump(count($GLOBALS['deck']));

function getOneCardFromDeck()
{
  $pos = random_int(0, (count($GLOBALS['deck']) - 1));
  $cardgot = array_splice($GLOBALS['deck'], $pos, 1);
  return $cardgot[0];
}

$randomcards = array();

for ($i = 0; $i < 7; $i++) {
  array_push($randomcards, getOneCardFromDeck());
}


$GLOBALS['game'] = array();
$json = '{
  "user": "giovanni",
  "cards": ' . json_encode($randomcards) . '
}';

echo $json . "\n\n";
echo json_encode($randomcards) . "\n\n";

dump(json_encode($json));
dump(json_encode($randomcards));
dump(json_decode($json));
// $json = '{