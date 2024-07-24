<?php

namespace Api\WebSocket;

use Exception;
use Ratchet\ConnectionInterface;
// use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use stdClass;

// use stdClass;

$GLOBALS['searchname'] = '';
$GLOBALS['usersonline'] = array();
$GLOBALS['turnhasplayed'] = false;
$GLOBALS['turnhasgotnewcard'] = false;
$GLOBALS['turn'] = 'giovanni';

$GLOBALS['deck'] = generateNewDeck();

$validcardgot = false;
while (!$validcardgot) {
  $pos = random_int(0, (count($GLOBALS['deck']) - 1));
  $cardgot = $GLOBALS['deck'][$pos];
  $cardgotvalue = $cardgot->value;

  if (
    $cardgotvalue != "skip" &&
    $cardgotvalue != "reverse" &&
    $cardgotvalue != "draw2" &&
    $cardgotvalue != "wild" &&
    $cardgotvalue != "wilddrawfour"
  ) {
    $cardgot = array_splice($GLOBALS['deck'], $pos, 1);
    $GLOBALS['tablecard'] =  $cardgot[0];
    $validcardgot = true;
  }
}

// $handle = fopen("game.json", "r");
// while (($buffer = fgets($handle, 4096)) !== false) {
//   $lines .= str_replace("\r\n", '', $buffer);
// }
// if (!feof($handle)) {
//   $goterror = true;
//   return;
// }
// fclose($handle);

$randomcards = array();

for ($i = 0; $i < 2; $i++) {
  array_push($randomcards, getOneCardFromDeck());
}

$GLOBALS['game'] = array();

$newUser = new stdClass();
$newUser->user = "giovanni";
$newUser->cards = $randomcards;

array_push(
  $GLOBALS['game'],
  $newUser
);

$randomcards = [];

for ($i = 0; $i < 2; $i++) {
  array_push($randomcards, getOneCardFromDeck());
}

$newUser = new stdClass();
$newUser->user = "firefox";
$newUser->cards = $randomcards;

array_push(
  $GLOBALS['game'],
  $newUser
);


// $json = '{
//   "user": "giovanni",
//   "cards": [
//     {"value": "0", "color": "red" },
//     {"value": "1", "color": "yellow" },
//     {"value": "2", "color": "green" },
//     {"value": "3", "color": "blue" },
//     {"value": "4", "color": "red" },
//     {"value": "5", "color": "yellow" },
//     {"value": "6", "color": "green" },
//     {"value": "7", "color": "blue" },
//     {"value": "8", "color": "red" },
//     {"value": "9", "color": "yellow" },
//     {"value": "skip", "color": "green" },
//     {"value": "reverse", "color": "blue" },
//     {"value": "draw2", "color": "red" },
//     {"value": "wild", "color": "black" },
//     {"value": "wilddrawfour", "color": "black" }
//   ]
// }';
// array_push($GLOBALS['game'], json_decode($json));

class system implements MessageComponentInterface
{
  protected $cliente;

  public function __construct()
  {
    // Iniciar o objeto que deve armazenar os clientes conectados
    $this->cliente = new \SplObjectStorage;
  }

  public function onOpen(ConnectionInterface $conn)
  {
    $this->cliente->attach($conn);
  }

  public function onMessage(ConnectionInterface $from, $msg)
  {
    $decodeJSON = json_decode($msg);
    $type = $decodeJSON->type;
    $content = $decodeJSON->content;

    if ($type == 'message') {
      $user = $content->user;
      $message = $content->message;

      $sendbackcontent = "<p>" . date('y/m/d h:i:s a') . " | <span class='user'>$user</span>: $message</p>";
      $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';

      foreach ($this->cliente as $cliente) {
        // Não enviar a mensagem para o usuário que enviou a mensagem
        // if ($from !== $cliente) {
        $cliente->send($sendback);
        // }
      }

      saveChat($sendbackcontent);
    } else if ($type == 'server') {
      $contenttype = $content->type; // type
      $contentPass = $content->content; // content

      if ($contenttype == 'connected') {
        $lines = "";
        $goterror = false;

        $sendbackcontent = "<p><span class='server'>Server</span>: <span class='user'>$contentPass</span> $contenttype</p>";

        array_push($GLOBALS['usersonline'], $contentPass);

        $handle = fopen("chat.txt", "r");
        if ($handle) {
          while (($buffer = fgets($handle, 4096)) !== false) {
            $lines .= str_replace("\r\n", '', $buffer);
          }
          if (!feof($handle)) {
            $goterror = true;
            return;
          }
          fclose($handle);
        }

        $sendback = '{"type": "message","content":"' . $lines . '"}';
        if ($goterror) {
          $sendback = '{"type": "message","content":"An error occure while trying to read filedata"}';
        }

        $from->send($sendback);

        $sendbackuser = getGameInfo();
        foreach ($this->cliente as $cliente) {
          $cliente->send($sendbackuser);
        }
      }

      $sendbackcontent = "<p><span class='server'>Server</span>: <span class='user'>$contentPass</span> $contenttype</p>";
      $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';

      if ($contenttype == 'disconnected') {
        if (count($GLOBALS['usersonline']) > 0) {
          $GLOBALS['searchname'] = $contentPass;
          $GLOBALS['usersonline'] = array_filter($GLOBALS['usersonline'], "filternameout");
        }
        $GLOBALS['searchname'] = '';

        $sendbackuser = getGameInfo();
        foreach ($this->cliente as $cliente) {
          $cliente->send($sendbackuser);
        }
      }

      saveChat($sendbackcontent);

      foreach ($this->cliente as $cliente) {
        $cliente->send($sendback);
      }
    } else if ($type == 'game') {
      $contenttype = $content->type; // type
      $contentPass = $content->content; // content

      if ($contenttype == 'wanttogetcard') {
        $user = $contentPass->user;

        if ($GLOBALS['turn'] != $user) return;
        if ($GLOBALS['turnhasplayed']) return;
        if ($GLOBALS['turnhasgotnewcard']) {
          $sendbackuser = passTurn();
          foreach ($this->cliente as $cliente) {
            $cliente->send($sendbackuser);
          }
          return;
        };

        $gotcard = false;

        for ($i = 0; $i < count($GLOBALS['game']); $i++) {
          $current = $GLOBALS['game'][$i];
          $currentname = $current->user;
          // $currentcards = $current->cards;

          if ($currentname == $user) {
            $gotcard = true;

            if (count($GLOBALS['deck']) < 2) {
              $olddeck = $GLOBALS['deck'];
              $GLOBALS['deck'] = generateNewDeck();
              for ($olddeckpos = 0; $olddeckpos < count($olddeck); $olddeckpos++) {
                array_push($GLOBALS['deck'], $olddeck[$olddeckpos]);
              }

              $sendbackcontent = "<p><span class='game'>Game</span>: ADDED NEW CARDS!</p>";
              $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
              $myfile = fopen("chat.txt", "a");
              fwrite($myfile, $sendbackcontent . "\r\n");
              fclose($myfile);

              foreach ($this->cliente as $cliente) {
                $cliente->send($sendback);
              }
            }

            array_push($GLOBALS['game'][$i]->cards, getOneCardFromDeck());
            $cards = $GLOBALS['game'][$i]->cards;

            // Send user his cards with value and color
            $cardCount = count($cards);
            $usercards = json_encode($cards);
            $sendback = '{"type":"game", "who":"' . $user . '", "content":' . $usercards . '}';
            $from->send($sendback);

            $sendbackcontent = "<p><span class='game'>Game</span>: <span class='user'>$user</span> Loadded $cardCount cards</p>";
            $myfile = fopen("chat.txt", "a");
            fwrite($myfile, $sendbackcontent . "\r\n");
            fclose($myfile);

            $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
            foreach ($this->cliente as $cliente) {
              $cliente->send($sendback);
            }

            $GLOBALS['turnhasgotnewcard'] = true;
            break;
          }
        }

        if (!$gotcard) return;

        $sendbackcontent = "<p><span class='game'>Game</span>: <span class='user'>$currentname</span> Pescou uma carta</p>";
        $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
        $myfile = fopen("chat.txt", "a");
        fwrite($myfile, $sendbackcontent . "\r\n");
        fclose($myfile);

        foreach ($this->cliente as $cliente) {
          $cliente->send($sendback);
        }

        $sendbackuser = getGameInfo();

        foreach ($this->cliente as $cliente) {
          $cliente->send($sendback);
          $cliente->send($sendbackuser);
        }

        saveGame();
        return;
      }

      if ($contenttype == 'wanttoplaycard') {
        $canplay = false;
        $playerpos = 0;
        $user = $contentPass->user;
        $value = $contentPass->value;
        $color = $contentPass->color;

        for ($i = 0; $i < count($GLOBALS['game']); $i++) {
          $current = $GLOBALS['game'][$i];
          $currentname = $current->user;
          $currentcards = $current->cards;
          if ($GLOBALS['turn'] != $user) break;
          if ($GLOBALS['turnhasplayed']) break;

          if ($currentname == $user) {
            for ($card = 0; $card < count($currentcards); $card++) {
              $currentCard = $currentcards[$card];
              if ($currentCard->value == $value && $currentCard->color == $color) {
                $currentcardvalue = $currentCard->value;
                $currentcardcolor = $currentCard->color;
                $cardontablevalue = $GLOBALS['tablecard']->value;
                $cardontablecolor = $GLOBALS['tablecard']->color;
                if (
                  $cardontablevalue == "skip" ||
                  $cardontablevalue == "reverse" ||
                  $cardontablevalue == "draw2" ||
                  $cardontablevalue == "wild" ||
                  $cardontablevalue == "wilddrawfour"
                ) {
                  // special cards ONTABLE
                } else if (
                  $currentcardvalue == "skip" ||
                  $currentcardvalue == "reverse" ||
                  $currentcardvalue == "draw2" ||
                  $currentcardvalue == "wild" ||
                  $currentcardvalue == "wilddrawfour"
                ) {
                  // special cards on hand
                } else {
                  // $currentcardvalue ||
                  // $currentcardcolor ||
                  // $cardontablevalue ||
                  // $cardontablecolor ||
                  if ($currentcardcolor != $cardontablecolor && $currentcardvalue != $cardontablevalue) {
                    break;
                  }
                  // nubmers
                  $canplay = true;
                  $playerpos = $i;
                  $cardplayed = array_splice($GLOBALS['game'][$i]->cards, $card, 1);
                  $GLOBALS['turnhasplayed'] = true;
                  array_push($GLOBALS['deck'], $GLOBALS['tablecard']);

                  echo json_encode($cardplayed);
                  $GLOBALS['tablecard'] = $cardplayed[0];
                }
              }
            }
          }
        }

        $canheplay = $canplay == true ? 'YES' : 'NO';
        $currentcardvalue = $GLOBALS['tablecard']->value;
        $currentcardcolor = $GLOBALS['tablecard']->color;

        // $sendbackcontent = "<p><span class='game'>Game</span>: <span class='user'>$user</span> $contenttype user='$user' | type='$type' | color='$color' || canheplay='$canheplay' || currentcardvalue='$currentcardvalue' && currentcardcolor='$currentcardcolor'</p>";
        $sendbackcontent = "<p><span class='game'>Game</span>: <span class='user'>$user</span> turn='" . $GLOBALS['turn'] . "' | turnhasplayed='" . $GLOBALS['turnhasplayed'] . "' || canheplay='$canheplay' || deckcount='" . count($GLOBALS['deck']) . "'</p>";
        $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
        $myfile = fopen("chat.txt", "a");
        fwrite($myfile, $sendbackcontent . "\r\n");
        fclose($myfile);

        foreach ($this->cliente as $cliente) {
          $cliente->send($sendback);
        }

        if (!$canplay) return;

        $current = $GLOBALS['game'][$playerpos];
        $name = $current->user;
        $cards = $current->cards;

        $cardCount = count($cards);
        $usercards = json_encode($cards);

        $sendback = '{"type":"game", "who":"' . $name . '", "content":' . $usercards . '}';
        $from->send($sendback);

        $sendbackuser = passTurn();
        foreach ($this->cliente as $cliente) {
          $cliente->send($sendbackuser);
        }

        saveGame();
        return;
      }

      // Send user his cards with value and color
      for ($i = 0; $i < count($GLOBALS['game']); $i++) {
        $current = $GLOBALS['game'][$i];
        $name = $current->user;
        $cards = $current->cards;

        if ($name == $contentPass) {
          $cardCount = count($cards);
          $usercards = json_encode($cards);
          $sendback = '{"type":"game", "who":"' . $contentPass . '", "content":' . $usercards . '}';
          $from->send($sendback);

          $sendbackcontent = "<p><span class='game'>Game</span>: <span class='user'>$contentPass</span> Loadded $cardCount cards</p>";
          $myfile = fopen("chat.txt", "a");
          fwrite($myfile, $sendbackcontent . "\r\n");
          fclose($myfile);

          $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
          foreach ($this->cliente as $cliente) {
            $cliente->send($sendback);
          }
        }
      }

      saveGame();
    }
    // else if ($type == 'users') {
    //   $currentusers = str_replace('"', '\"', json_encode($GLOBALS['usersonline']));
    //   $sendbackcontent = "<p><span class='debug game'>Get users</span>: Users are: $currentusers</p>";
    //   $myfile = fopen("chat.txt", "a");
    //   fwrite($myfile, $sendbackcontent . "\r\n");
    //   fclose($myfile);

    //   $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
    //   foreach ($this->cliente as $cliente) {
    //     $cliente->send($sendback);
    //   }

    //   $sendback = '{"type": "users","content":"' . count($GLOBALS['usersonline']) . '"}';
    //   foreach ($this->cliente as $cliente) {
    //     $cliente->send($sendback);
    //   }
    // } else if ($type == 'gamenow') {
    //   $gamenow = str_replace('"', '\"', json_encode($GLOBALS['game']));
    //   $sendbackcontent = "<p><span class='debug game'>Game now</span>: $gamenow</p>";
    //   $myfile = fopen("chat.txt", "a");
    //   fwrite($myfile, $sendbackcontent . "\r\n");
    //   fclose($myfile);

    //   $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
    //   foreach ($this->cliente as $cliente) {
    //     $cliente->send($sendback);
    //   }
    // }
  }

  public function onClose(ConnectionInterface $conn)
  {
    $this->cliente->detach($conn);
  }

  public function onError(ConnectionInterface $conn, Exception $e)
  {
    $conn->close();
  }
}
