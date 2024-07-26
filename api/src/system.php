<?php

namespace Api\WebSocket;

use Exception;
use Ratchet\ConnectionInterface;
// use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use stdClass;

// use stdClass;

$GLOBALS['gameendend'] = false;
$GLOBALS['gamestarted'] = false;
$GLOBALS['searchname'] = '';
$GLOBALS['usersonline'] = array();
$GLOBALS['usersthatwon'] = array();
$GLOBALS['turnhasplayed'] = false;
$GLOBALS['turnhasgotnewcard'] = false;
$GLOBALS['turn'] = 'giovanni';

$GLOBALS['deck'] = generateNewDeck();
$GLOBALS['tablecard'] =  $GLOBALS['deck'][0];

// $handle = fopen("game.json", "r");
// while (($buffer = fgets($handle, 4096)) !== false) {
//   $lines .= str_replace("\r\n", '', $buffer);
// }
// if (!feof($handle)) {
//   $goterror = true;
//   return;
// }
// fclose($handle);


$GLOBALS['game'] = array();

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
        $myfile = fopen("usersonline.txt", "w");
        fwrite($myfile, json_encode($GLOBALS['usersonline']));
        fclose($myfile);

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

        $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
        $myfile = fopen("chat.txt", "a");
        fwrite($myfile, $sendbackcontent . "\r\n");
        fclose($myfile);
        $this->sendAll($sendback);

        if ($GLOBALS['gamestarted']) {
          $sendbackgamestarted = '{"type":"game", "started": true}';
          $from->send($sendbackgamestarted);
        }
      }

      $sendbackcontent = "<p><span class='server'>Server</span>: <span class='user'>$contentPass</span> $contenttype</p>";
      $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';

      if ($contenttype == 'disconnected') {
        if (count($GLOBALS['usersonline']) > 0) {
          $GLOBALS['searchname'] = $contentPass;
          $GLOBALS['usersonline'] = array_filter($GLOBALS['usersonline'], "filternameout");
        }

        $myfile = fopen("usersonline.txt", "w");
        fwrite($myfile, json_encode($GLOBALS['usersonline']));
        fclose($myfile);

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

      if ($contenttype == 'startgame') {
        $GLOBALS['gamestarted'] = true;
        echo "Starting game for: " . json_encode($GLOBALS['usersonline']) .  "-(" . count($GLOBALS['usersonline']) . " users)\n";

        for ($i = 0; $i < count($GLOBALS['usersonline']); $i++) {
          $randomcards = [];
          for ($card = 0; $card < 7; $card++) {
            array_push($randomcards, getOneCardFromDeck());
          }
          $newUser = new stdClass();
          $newUser->user = $GLOBALS['usersonline'][$i];
          $newUser->cards = $randomcards;
          array_push(
            $GLOBALS['game'],
            $newUser
          );
        }

        $GLOBALS['turnhasplayed'] = false;
        $GLOBALS['turnhasgotnewcard'] = false;
        $GLOBALS['turn'] = $GLOBALS['usersonline'][0];

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

        $user = $contentPass->user;
        $sendbackcontent = "<p><span class='game'>Game</span>: <span class='user'>$user</span> Começou o jogo</p>";
        $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
        $myfile = fopen("chat.txt", "a");
        fwrite($myfile, $sendbackcontent . "\r\n");
        fclose($myfile);
        $this->sendAll($sendback);

        $sendbackgamestarted = '{"type":"game", "started": "true"}';

        foreach ($this->cliente as $cliente) {
          $cliente->send($sendback);
          $cliente->send($sendbackgamestarted);
        }

        saveGame();
      }

      if ($GLOBALS['gameendend']) {
        $this->sendAll(getGameInfo());

        $sendbackcontent = "<p><span class='game'>Game</span>: O jogo já acabou</p>";
        $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
        $myfile = fopen("chat.txt", "a");
        fwrite($myfile, $sendbackcontent . "\r\n");
        fclose($myfile);
        $this->sendAll($sendback);

        $GLOBALS['gameendend'] = true;
        $sendbackcontent = "<p><span class='game'>Game</span>: JOGO FINALIZADO</p>";
        $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
        $sendbackgame = '{"type": "game", "whowon":' . json_encode($GLOBALS['usersthatwon']) . ', "gameendend": true}';
        $myfile = fopen("chat.txt", "a");
        fwrite($myfile, $sendbackcontent . "\r\n");
        fclose($myfile);

        $this->sendAll($sendback);
        $this->sendAll($sendbackgame);
        return;
      };

      if (!$GLOBALS['gamestarted']) {
        $sendbackcontent = "<p><span class='game'>Game</span>: O jogo ainda não iniciou</p>";
        $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
        $myfile = fopen("chat.txt", "a");
        fwrite($myfile, $sendbackcontent . "\r\n");
        fclose($myfile);
        $this->sendAll($sendback);
      }

      if ($contenttype == 'wanttogetcard') {
        $user = $contentPass->user;

        if ($GLOBALS['turn'] != $user) return;
        if ($GLOBALS['turnhasplayed']) return;
        if ($GLOBALS['turnhasgotnewcard']) {
          $this->sendAll(passTurn());
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

        $this->sendAll($sendback);

        if (!$canplay) return;

        if (count($GLOBALS['game'][$playerpos]->cards) == 0) {
          $this->sendAll(passTurn());

          $currentuser = $GLOBALS['game'][$playerpos]->user;
          array_push($GLOBALS['usersthatwon'], $currentuser);

          $cardplayed = array_splice($GLOBALS['game'], $playerpos, 1);

          $sendbackcontent = "<p><span class='game'>Game</span>: <span class='user'>$currentuser</span> Zerou</p>";
          $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
          $myfile = fopen("chat.txt", "a");
          fwrite($myfile, $sendbackcontent . "\r\n");
          fclose($myfile);
          $this->sendAll($sendback);

          if (count($GLOBALS['game']) == 1) {
            $GLOBALS['gameendend'] = true;
            $sendbackcontent = "<p><span class='game'>Game</span>: JOGO FINALIZADO</p>";
            $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
            $sendbackgame = '{"type": "game", "whowon":' . json_encode($GLOBALS['usersthatwon']) . ', "gameendend": true}';
            $myfile = fopen("chat.txt", "a");
            fwrite($myfile, $sendbackcontent . "\r\n");
            fclose($myfile);

            $this->sendAll($sendback);
            $this->sendAll($sendbackgame);
          }
          saveGame();
          return;
        }

        $current = $GLOBALS['game'][$playerpos];
        $name = $current->user;
        $cards = $current->cards;

        $cardCount = count($cards);
        $usercards = json_encode($cards);

        $sendback = '{"type":"game", "who":"' . $name . '", "content":' . $usercards . '}';
        $from->send($sendback);

        $this->sendAll(passTurn());
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
          $this->sendAll($sendback);
        }
      }

      $sendbackuser = getGameInfo();

      foreach ($this->cliente as $cliente) {
        $cliente->send($sendbackuser);
      }

      saveGame();
    }
  }

  public function onClose(ConnectionInterface $conn)
  {
    $this->cliente->detach($conn);
  }

  public function onError(ConnectionInterface $conn, Exception $e)
  {
    $conn->close();
  }

  public function sendAll($message)
  {
    foreach ($this->cliente as $cliente) {
      $cliente->send($message);
    }
  }
}