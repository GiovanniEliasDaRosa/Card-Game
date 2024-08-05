<?php

function filternameout($name)
{
  return $name != $GLOBALS['searchname'];
}

function generateNewDeck()
{
  // $possiblecards = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'skip', 'reverse', 'draw2', 'wild', 'wilddrawfour'];
  $colors = ['red', 'yellow', 'green', 'blue'];
  $deck = array();
  $coloridx = 0;

  // create 4x '0' cards, each color
  for ($coloridx = 0; $coloridx < 4; $coloridx++) {
    $value = '0';
    $color = $colors[$coloridx];
    $card = '{"value": "' . $value . '","color": "' . $color . '"}';
    array_push($deck, json_decode($card));
  }

  // create 2x 1-9 cards, 2 each color
  $coloridx = 0;
  for ($currentcolor = 0; $currentcolor < 4; $currentcolor++) {
    for ($i = 1; $i < 10; $i++) {
      for ($quant = 0; $quant < 2; $quant++) {
        $value = $i;
        $color = $colors[$coloridx];
        $card = '{"value": "' . $value . '","color": "' . $color . '"}';
        array_push($deck, json_decode($card));
      }
    }
    $coloridx++;
  }

  // create 2x 'special cards' each color
  $specialCards = ['skip', 'reverse', 'draw2'];
  for ($currentcolor = 0; $currentcolor < 4; $currentcolor++) {
    for ($currentSpecialCard = 0; $currentSpecialCard < 3; $currentSpecialCard++) {
      for ($quant = 0; $quant < 2; $quant++) {
        $value = $specialCards[$currentSpecialCard];
        $color = $colors[$currentcolor];
        $card = '{"value": "' . $value . '","color": "' . $color . '"}';
        array_push($deck, json_decode($card));
      }
    }
  }

  // create 4x 'black cards'
  $blackCards = ['wild', 'wilddrawfour'];
  for ($currentBlackCard = 0; $currentBlackCard < 2; $currentBlackCard++) {
    for ($quant = 0; $quant < 4; $quant++) {
      $value = $blackCards[$currentBlackCard];
      $color = 'black';
      $card = '{"value": "' . $value . '","color": "' . $color . '"}';
      array_push($deck, json_decode($card));
    }
  }
  return $deck;
}

function getGameInfo($type = null)
{
  $theircardsarray = array();

  // Users that won
  for ($i = 0; $i < count($GLOBALS['usersthatwon']); $i++) {
    $result = [$GLOBALS['usersthatwon'][$i], 0];
    array_push($theircardsarray, $result);
  }

  for ($i = 0; $i < count($GLOBALS['game']); $i++) {
    $currentUser = $GLOBALS['game'][$i];
    $result = array();
    $result = [$currentUser->user, count($currentUser->cards)];
    array_push($theircardsarray, $result);
  }
  $turnhasgotnewcard = $GLOBALS['turnhasgotnewcard'] == true ? "true" : "false";
  $theircards = str_replace('"', '\"', json_encode($theircardsarray));
  $sendbackuser = '{"type": "users","usersactive":"' . count($GLOBALS['usersonline']) . '", "theircards":"' . $theircards . '", "turn":"' . $GLOBALS['turn'] . '", "tablecard":' . json_encode($GLOBALS['tablecard']) . ', "turnhasgotnewcard": ' . $turnhasgotnewcard . ', "selectedcolor": "' . $GLOBALS['selectedcolor'] . '"';

  if ($type == 'userloaded') {
    $sendbackuser .= ', "userloaded": true}';
  } else {
    $sendbackuser .= '}';
  }

  return $sendbackuser;
}

function loadGame()
{
  $goterror = false;
  $gamefile = fopen("game.json", "r");
  $gameFileResult = "";
  if ($gamefile) {
    while (($buffer = fgets($gamefile, 4096)) !== false) {
      $gameFileResult .= str_replace("\r\n", '', $buffer);
    }
    if (!feof($gamefile)) {
      $goterror = true;
      return;
    }
    fclose($gamefile);
  }
  return [$gameFileResult, $goterror];
}

function saveGame()
{
  $gameFile = new stdClass();
  $gameFile->gamestarted = $GLOBALS['gamestarted'];
  $gameFile->gameendend = $GLOBALS['gameendend'];
  $gameFile->usersthatwon = json_encode($GLOBALS['usersthatwon']);
  $gameFile->turnhasplayed = $GLOBALS['turnhasplayed'];
  $gameFile->turnhasgotnewcard = $GLOBALS['turnhasgotnewcard'];
  $gameFile->turn = $GLOBALS['turn'];
  $gameFile->direction = $GLOBALS['direction'];
  $gameFile->getcardcount = $GLOBALS['getcardcount'];
  $gameFile->selectcolor = $GLOBALS['selectcolor'];
  $gameFile->whoisselecting = $GLOBALS['whoisselecting'];
  $gameFile->selectedcolor = $GLOBALS['selectedcolor'];
  $gameFile->selectedacolor = $GLOBALS['selectedacolor'];
  $gameFile->selectcolor = $GLOBALS['selectcolor'];
  $gameFile->deck = json_encode($GLOBALS['deck']);
  $gameFile->tablecard = json_encode($GLOBALS['tablecard']);
  $gameFile->game = json_encode($GLOBALS['game']);

  $gamefile = fopen("game.json", "w");
  fwrite($gamefile, json_encode($gameFile));
  fclose($gamefile);
}

function saveChat($sendbackcontent)
{
  $myfile = fopen("chat.txt", "a");
  fwrite($myfile, $sendbackcontent . "\r\n");
  fclose($myfile);
}

function getOneCardFromDeck()
{
  $pos = random_int(0, (count($GLOBALS['deck']) - 1));
  $cardgot = array_splice($GLOBALS['deck'], $pos, 1);
  return $cardgot[0];
}

function reallyPassTurn()
{
  $nextturn = -3;
  for ($i = 0; $i < count($GLOBALS['game']); $i++) {
    $currentuser = $GLOBALS['game'][$i];

    if ($currentuser->user == $GLOBALS['turn']) {
      $nextturn = $i + $GLOBALS['direction'];

      if ($nextturn > count($GLOBALS['game']) - 1) {
        $nextturn = 0;
        break;
      } else if ($nextturn < 0) {
        $nextturn = count($GLOBALS['game']) - 1;
        break;
      }
    }
  }

  return $nextturn;
}

function passTurn()
{
  $nextturn = reallyPassTurn();

  if ($GLOBALS['getcardcount'] > 0) {
    $canIncreaseCardGetCount = false;
    $nextTurnCards = $GLOBALS['game'][$nextturn]->cards;

    for ($i = 0; $i < count($nextTurnCards); $i++) {
      $currentCard = $nextTurnCards[$i];
      if ($currentCard->value == 'wilddrawfour') {
        $canIncreaseCardGetCount = true;
        break;
      } else if ($currentCard->value == 'draw2') {
        if ($GLOBALS['selectedacolor']) {
          if ($GLOBALS['selectedcolor'] == $currentCard->color) {
            $canIncreaseCardGetCount = true;
            break;
          }
        } else {
          $canIncreaseCardGetCount = true;
          break;
        }
      }
    }

    if (!$canIncreaseCardGetCount) {
      if (count($GLOBALS['deck']) - $GLOBALS['getcardcount'] < 2) {
        $olddeck = $GLOBALS['deck'];
        $GLOBALS['deck'] = generateNewDeck();
        for ($olddeckpos = 0; $olddeckpos < count($olddeck); $olddeckpos++) {
          array_push($GLOBALS['deck'], $olddeck[$olddeckpos]);
        }

        // $sendbackcontent = "<p><span class='game'>Game</span>: ADDED NEW CARDS!</p>";
        // $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
        // $myfile = fopen("chat.txt", "a");
        // fwrite($myfile, $sendbackcontent . "\r\n");
        // fclose($myfile);

        // foreach ($this->cliente as $cliente) {
        //   $cliente->send($sendback);
        // }
      }

      for ($i = 0; $i < $GLOBALS['getcardcount']; $i++) {
        array_push($GLOBALS['game'][$nextturn]->cards, getOneCardFromDeck());
      }

      $GLOBALS['getcardcount'] = 0;
      $GLOBALS['turnhasplayed'] = false;
      $GLOBALS['turnhasgotnewcard'] = false;
      $GLOBALS['turn'] = $GLOBALS['game'][$nextturn]->user;
      $nextturn = reallyPassTurn();
    }
  }

  $GLOBALS['turnhasplayed'] = false;
  $GLOBALS['turnhasgotnewcard'] = false;
  $GLOBALS['turn'] = $GLOBALS['game'][$nextturn]->user;

  return getGameInfo('userloaded');
}

function resetGame($tableCardToo = false, $gameEndedToo = false)
{
  $GLOBALS['gamestarted'] = false;
  if ($gameEndedToo) {
    $GLOBALS['gameendend'] = false;
  }

  $GLOBALS['searchname'] = '';
  $GLOBALS['usersthatwon'] = array();
  $GLOBALS['turnhasplayed'] = false;
  $GLOBALS['turnhasgotnewcard'] = false;
  $GLOBALS['turn'] = 'none';
  $GLOBALS['direction'] = 1;
  $GLOBALS['getcardcount'] = 0;
  $GLOBALS['whoisselecting'] = "";
  $GLOBALS['selectedcolor'] = "";
  $GLOBALS['selectedacolor'] = false;
  $GLOBALS['selectcolor'] = false;
  $GLOBALS['deck'] = generateNewDeck();
  $GLOBALS['tablecard'] = null;
  $GLOBALS['game'] = array();

  if ($tableCardToo) {
    // Set a defaut cart like loading so users know a new game is about to start
    $tablecarload = $GLOBALS['deck'][0];
    $tablecarload->value = 'loading';
    $tablecarload->color = 'loading';
    $GLOBALS['tablecard'] =  $tablecarload;
  }
}

function saveLoadedGameToServer($gameLoaded)
{
  $GLOBALS['gamestarted'] = $gameLoaded->gamestarted;
  $GLOBALS['gameendend'] = $gameLoaded->gameendend;
  $GLOBALS['usersthatwon'] = json_decode($gameLoaded->usersthatwon);
  $GLOBALS['turnhasplayed'] = $gameLoaded->turnhasplayed;
  $GLOBALS['turnhasgotnewcard'] = $gameLoaded->turnhasgotnewcard;
  $GLOBALS['turn'] = $gameLoaded->turn;
  $GLOBALS['direction'] = $gameLoaded->direction;
  $GLOBALS['getcardcount'] = $gameLoaded->getcardcount;
  $GLOBALS['selectcolor'] = $gameLoaded->selectcolor;
  $GLOBALS['whoisselecting'] = $gameLoaded->whoisselecting;
  $GLOBALS['selectedcolor'] = $gameLoaded->selectedcolor;
  $GLOBALS['selectedacolor'] = $gameLoaded->selectedacolor;
  $GLOBALS['selectcolor'] = $gameLoaded->selectcolor;
  $GLOBALS['deck'] = json_decode($gameLoaded->deck);
  $GLOBALS['tablecard'] = json_decode($gameLoaded->tablecard);
  $GLOBALS['game'] = json_decode($gameLoaded->game);
}
