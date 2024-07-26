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

  // $specialCards = ['skip', 'reverse', 'draw2'];

  // // create 2x 'special cards' each color
  // for ($currentcolor = 0; $currentcolor < 4; $currentcolor++) {
  //   for ($currentSpecialCard = 0; $currentSpecialCard < 3; $currentSpecialCard++) {
  //     for ($quant = 0; $quant < 2; $quant++) {
  //       $value = $specialCards[$currentSpecialCard];
  //       $color = $colors[$currentcolor];
  //       $card = '{"value": "' . $value . '","color": "' . $color . '"}';
  //       array_push($deck, json_encode($card));
  //     }
  //   }
  // }

  // $blackCards = ['wild', 'wilddrawfour'];

  // // create 4x 'black cards'
  // for ($currentBlackCard = 0; $currentBlackCard < 2; $currentBlackCard++) {
  //   for ($quant = 0; $quant < 4; $quant++) {
  //     $value = $blackCards[$currentBlackCard];
  //     $color = 'black';
  //       $card = '{"value": "' . $value . '","color": "' . $color . '"}';
  //     array_push($deck, json_encode($card));
  //   }
  // }
  return $deck;
}

function getGameInfo()
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
  $sendbackuser = '{"type": "users","usersactive":"' . count($GLOBALS['usersonline']) . '", "theircards":"' . $theircards . '", "turn":"' . $GLOBALS['turn'] . '", "tablecard":' . json_encode($GLOBALS['tablecard']) . ', "turnhasgotnewcard": ' . $turnhasgotnewcard . '}';
  return $sendbackuser;
}

function saveGame()
{
  $gamefile = fopen("game.json", "w");
  $encodedGame = json_encode($GLOBALS['game']);
  fwrite($gamefile, $encodedGame . "\r\n");
  fwrite($gamefile, $GLOBALS['turnhasplayed'] . "\r\n");
  fwrite($gamefile, $GLOBALS['turn'] . "\r\n");
  fwrite($gamefile, json_encode($GLOBALS['deck']) . "\r\n");
  fwrite($gamefile, json_encode($GLOBALS['tablecard']) . "\r\n");
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

function passTurn()
{
  $nextturn = -1;

  for ($i = 0; $i < count($GLOBALS['game']); $i++) {
    $currentuser = $GLOBALS['game'][$i];

    if ($nextturn == $i) {
      $GLOBALS['turnhasplayed'] = false;
      $GLOBALS['turnhasgotnewcard'] = false;
      $GLOBALS['turn'] = $currentuser->user;
      break;
    }

    if ($currentuser->user == $GLOBALS['turn']) {
      $nextturn = $i + 1;

      //passed the array
      if ($nextturn > count($GLOBALS['game']) - 1) {
        $nextturn = 0;

        $GLOBALS['turnhasplayed'] = false;
        $GLOBALS['turnhasgotnewcard'] = false;
        $GLOBALS['turn'] = $GLOBALS['game'][0]->user;
        break;
      }
    }
  }

  return getGameInfo();
}

// $server: variável que irá armazenar a instância do servidor WebSocket.
// IoServer::factory(): método para criar uma instância do servidor WebSocket.
// new HttpServer(): classe que fornece funcionalidades de comunicação HTTP básicas.
// new WsServer(): classe responsável por fornecer funcionalidades WebSocket adicionais, permitindo a comunicação bidirecional em tempo real.
// new SistemaChat(): classe criada para lidar com eventos relacionados ao WebSocket, como receber mensagens, abrir conexões, fechar conexões, entre outras funcionalidades
// 8080: porta em que o servidor WebSocket será executado

$myfile = fopen("chat.txt", "w");
fclose($myfile);

echo "Server running...\n";
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