<?php

use Api\WebSocket\system;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

// Incluir o Composer
require __DIR__ . '/vendor/autoload.php';

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
  // $coloridx = 0;
  // for ($currentcolor = 0; $currentcolor < 4; $currentcolor++) {
  //   for ($i = 1; $i < 10; $i++) {
  //     for ($quant = 0; $quant < 2; $quant++) {
  //       $value = $i;
  //       $color = $colors[$coloridx];
  //       $card = '{"value": "' . $value . '","color": "' . $color . '"}';
  //       array_push($deck, json_decode($card));
  //     }
  //   }
  //   $coloridx++;
  // }

  // create 2x 'special cards' each color
  // $specialCards = ['skip', 'reverse', 'draw2'];
  $specialCards = ['draw2'];
  for ($currentcolor = 0; $currentcolor < 4; $currentcolor++) {
    for ($currentSpecialCard = 0; $currentSpecialCard < 1; $currentSpecialCard++) {
      for ($quant = 0; $quant < 2; $quant++) {
        $value = $specialCards[$currentSpecialCard];
        $color = $colors[$currentcolor];
        $card = '{"value": "' . $value . '","color": "' . $color . '"}';
        array_push($deck, json_decode($card));
      }
    }
  }

  // $blackCards = ['wild', 'wilddrawfour'];
  $blackCards = ['wilddrawfour'];

  // create 4x 'black cards'
  for ($currentBlackCard = 0; $currentBlackCard < 1; $currentBlackCard++) {
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

function saveGame()
{
  $gameFile = new stdClass();
  $gameFile->game = json_encode($GLOBALS['game']);
  $gameFile->turn = $GLOBALS['turn'];
  $gameFile->deck = json_encode($GLOBALS['deck']);
  $gameFile->tablecard = json_encode($GLOBALS['tablecard']);
  $gameFile->gamestarted = json_encode($GLOBALS['gamestarted']);
  $gameFile->gameendend = json_encode($GLOBALS['gameendend']);
  $gameFile->usersthatwon = json_encode($GLOBALS['usersthatwon']);

  $gameFile->turnhasplayed = $GLOBALS['turnhasplayed'];
  $gameFile->turnhasgotnewcard = $GLOBALS['turnhasgotnewcard'];
  $gameFile->turn = $GLOBALS['turn'];
  $gameFile->direction = $GLOBALS['direction'];

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

// $server: variável que irá armazenar a instância do servidor WebSocket.
// IoServer::factory(): método para criar uma instância do servidor WebSocket.
// new HttpServer(): classe que fornece funcionalidades de comunicação HTTP básicas.
// new WsServer(): classe responsável por fornecer funcionalidades WebSocket adicionais, permitindo a comunicação bidirecional em tempo real.
// new SistemaChat(): classe criada para lidar com eventos relacionados ao WebSocket, como receber mensagens, abrir conexões, fechar conexões, entre outras funcionalidades
// 8080: porta em que o servidor WebSocket será executado

// Create needed files
$wantedfiles = ['chat.txt', 'debug.json', 'game.json', 'users.txt', 'usersonline.txt'];
$createdfiles = 0;
foreach ($wantedfiles as $file) {
  if (!file_exists($file)) {
    $createdfiles++;
    if ($createdfiles == 1) {
      echo "---\nCreating Files | ";
    }
    echo "'$file', ";
    $myfile = fopen("$file", "w");
    fclose($myfile);
  }
}
if ($createdfiles > 0) {
  echo "\n---\n";
}

$myfile = fopen("chat.txt", "w");
fclose($myfile);

echo "Server running... \e[44m \e[4mhttp://127.0.0.1:81/Uno/uno.html\e[0m\e[44m \e[0m\n";
echo "[Ctrl+C] to stop\n";

$server = IoServer::factory(
  new HttpServer(
    new WsServer(
      new system
    )
  ),
  8080
);

// Iniciar o servido e começar a executar as conexões.
$server->run();
