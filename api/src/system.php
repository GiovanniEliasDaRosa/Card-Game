<?php

namespace Api\WebSocket;

use Exception;
use Ratchet\ConnectionInterface;
// use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use stdClass;

$GLOBALS['gameendend'] = false;
$GLOBALS['gamestarted'] = false;
$GLOBALS['searchname'] = '';
$GLOBALS['usersonline'] = array();
$GLOBALS['usersthatwon'] = array();
$GLOBALS['turnhasplayed'] = false;
$GLOBALS['turnhasgotnewcard'] = false;
$GLOBALS['turn'] = 'giovanni';
$GLOBALS['direction'] = 1;
$GLOBALS['getcardcount'] = 0;
$GLOBALS['whoisselecting'] = '';
$GLOBALS['selectedcolor'] = '';
$GLOBALS['selectedacolor'] = false;
$GLOBALS['selectcolor'] = false;

$GLOBALS['deck'] = generateNewDeck();

$tablecarload = $GLOBALS['deck'][0];
$tablecarload->value = 'loading';
$tablecarload->color = 'loading';

$GLOBALS['tablecard'] =  $tablecarload;
$GLOBALS['game'] = array();

// $handle = fopen("game.json", "r");
// while (($buffer = fgets($handle, 4096)) !== false) {
//   $lines .= str_replace("\r\n", '', $buffer);
// }
// if (!feof($handle)) {
//   $goterror = true;
//   return;
// }
// fclose($handle);

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
        saveChat($sendbackcontent);

        foreach ($this->cliente as $cliente) {
          $cliente->send($sendback);
        }

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
    } else if ($type == 'game') {
      $contenttype = $content->type; // type
      $contentPass = $content->content; // content

      if ($contenttype == 'startgame') {
        $user = $contentPass->user;
        if (count($GLOBALS['usersonline']) < 2) {
          $sendbackcontent = "<p><span class='game'>Game</span>: Somente o usuário <span class='user'>" . $GLOBALS['usersonline'][0] . "</span> está ativo, é necessário de pelo menos 2 pessoas para iniciar</p>";
          $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
          $from->send($sendback);
          return;
        }

        $GLOBALS['gamestarted'] = true;
        $GLOBALS['searchname'] = '';
        $GLOBALS['usersthatwon'] = array();
        $GLOBALS['turnhasplayed'] = false;
        $GLOBALS['turnhasgotnewcard'] = false;
        $GLOBALS['turn'] = 'giovanni';
        $GLOBALS['direction'] = 1;
        $GLOBALS['getcardcount'] = 0;
        $GLOBALS['selectcolor'] = false;
        $GLOBALS['whoisselecting'] = "";
        $GLOBALS['selectedcolor'] = "";
        $GLOBALS['selectedacolor'] = false;
        $GLOBALS['selectcolor'] = false;
        $GLOBALS['deck'] = generateNewDeck();
        $GLOBALS['tablecard'] = null;
        $GLOBALS['game'] = array();

        [$gameFileResult, $goterror] = loadGame();

        // Can't load a game, so continue to creation
        if ($gameFileResult == "" || $GLOBALS['gameendend']) {
          $GLOBALS['gameendend'] = false;

          echo "\e[41mNo game found, creating a new one\e[49m\n";
          $quantusers =  count($GLOBALS['usersonline']) * 2;
          if ((count($GLOBALS['deck']) - $quantusers) < 2) {
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
            $this->sendAll($sendback);
          }

          echo "Starting new game for: " . json_encode($GLOBALS['usersonline']) .  "-(" . count($GLOBALS['usersonline']) . " users) '" . count($GLOBALS['deck']) . "'cards\n";

          for ($i = 0; $i < count($GLOBALS['usersonline']); $i++) {
            $randomcards = [];
            for ($card = 0; $card < 2; $card++) {
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
          $trys = 0;
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

            $trys++;
            if ($trys == 50) {
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
              $this->sendAll($sendback);
            }
          }

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
        } else {
          echo "\e[44mGame found, loading it...\e[49m\n";

          $gameLoaded = json_decode($gameFileResult);
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

          $sendbackcontent = "<p><span class='game'>Game</span>: <span class='user'>$user</span> Continuou o jogo</p>";
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
        }
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
        if (
          ($GLOBALS['tablecard']->value == 'draw2' || $GLOBALS['tablecard']->value == 'wilddrawfour') &&
          ($GLOBALS['turnhasplayed'] && $GLOBALS['selectedacolor'])
        ) {
          $sendbackcontent = "<p><span class='game'>Game</span>: Você não consegue pescar agora, jogue uma carta</p>";
          $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
          $from->send($sendback);
          return;
        }
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
              $this->sendAll($sendback);
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
        $playedColorSelector = false;
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
                  $currentcardvalue == "wild" ||
                  $currentcardvalue == "wilddrawfour"
                ) {
                  // User played black cards
                  // Check if card on table is different than special cards( check if's a number basically),
                  $canplay = true;
                  $playerpos = $i;
                  $cardplayed = array_splice($GLOBALS['game'][$i]->cards, $card, 1);
                  $GLOBALS['turnhasplayed'] = true;
                  array_push($GLOBALS['deck'], $GLOBALS['tablecard']);

                  $GLOBALS['tablecard'] = $cardplayed[0];

                  $sendback = '{"type": "selectcolor","content":"select"}';
                  $from->send($sendback);
                  $GLOBALS['whoisselecting'] = $user;
                  $GLOBALS['selectcolor'] = true;
                  $GLOBALS['selectedacolor'] = false;
                  $playedColorSelector = true;

                  if ($currentcardvalue == 'wilddrawfour') {
                    $GLOBALS['getcardcount'] += 4;
                  }
                  break;
                } else if (
                  $currentcardvalue == "skip" ||
                  $currentcardvalue == "reverse" ||
                  $currentcardvalue == "draw2"
                ) {
                  if (!$GLOBALS['selectedacolor']) {
                    // Check if card on table is different than special cards( check if's a number basically),
                    // and if the color is different then break and user can't play
                    if (
                      $cardontablevalue != "skip" &&
                      $cardontablevalue != "reverse" &&
                      $cardontablevalue != "draw2"
                    ) {
                      if ($currentcardcolor != $cardontablecolor) break;
                    } else {
                      // Check if card on table is a special card, and if they are diffent types ,like skip and draw2
                      // and if the color is different then break and user can't play
                      if (
                        $currentcardvalue != $cardontablevalue && $currentcardcolor != $cardontablecolor
                      ) {
                        break;
                      }
                    }
                  } else {
                    if ($currentcardcolor != $GLOBALS['selectedcolor']) break;
                  }

                  $canplay = true;
                  $playerpos = $i;
                  $cardplayed = array_splice($GLOBALS['game'][$i]->cards, $card, 1);
                  $GLOBALS['turnhasplayed'] = true;
                  array_push($GLOBALS['deck'], $GLOBALS['tablecard']);
                  $GLOBALS['tablecard'] = $cardplayed[0];

                  if ($currentcardvalue == "skip") {
                    $this->sendAll(passTurn());
                  } else if ($currentcardvalue == "reverse") {
                    $GLOBALS['direction'] = $GLOBALS['direction'] == 1 ? -1 : 1;
                  } else {
                    $GLOBALS['getcardcount'] += 2;
                  }
                  break;
                } else {
                  if (!$GLOBALS['selectedacolor']) {
                    if ($currentcardcolor != $cardontablecolor && $currentcardvalue != $cardontablevalue) break;
                  } else {
                    if ($currentcardcolor != $GLOBALS['selectedcolor']) break;
                  }

                  $canplay = true;
                  $playerpos = $i;
                  $cardplayed = array_splice($GLOBALS['game'][$i]->cards, $card, 1);
                  $GLOBALS['turnhasplayed'] = true;
                  array_push($GLOBALS['deck'], $GLOBALS['tablecard']);

                  $GLOBALS['tablecard'] = $cardplayed[0];
                  break;
                }
              }
            }

            if ($canplay) {
              break;
            }
          }
        }

        $myfile = fopen("debug.json", "a");
        fwrite($myfile, "{'tablecard': '" . json_encode($GLOBALS['tablecard']) . "', ");
        fwrite($myfile, "'isnull': '" . ($GLOBALS['tablecard'] == null) . "', ");
        fwrite($myfile, "'value': '" . $GLOBALS['tablecard']->value . "', ");
        fwrite($myfile, "'color': '" . $GLOBALS['tablecard']->color . "'},\r\n");
        fwrite($myfile, "'playerposcards': '" . json_encode($GLOBALS['game'][$playerpos]->cards) . "'},\r\n");
        fclose($myfile);

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
        if ($GLOBALS['selectedacolor'] && $GLOBALS['tablecard']->value == 'wild') {
          $GLOBALS['whoisselecting'] = '';
          $GLOBALS['selectedcolor'] = '';
          $GLOBALS['selectedacolor'] = false;
          $GLOBALS['selectcolor'] = false;
        }

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

        if ($playedColorSelector) {
          $this->sendAll(getGameInfo());
        } else {
          $this->sendAll(passTurn());
        }

        saveGame();
        return;
      }

      if ($contenttype == 'selectedcolor' && $GLOBALS['selectcolor']) {
        $colors = ['red', 'blue', 'yellow', 'green'];
        $color = $colors[$contentPass];
        $GLOBALS['selectedcolor'] = $color;
        $GLOBALS['selectedacolor'] = true;
        $GLOBALS['selectcolor'] = false;
        $sendback = '{"type": "selectcolor","content":"close"}';
        $from->send($sendback);
        $this->sendAll(passTurn());
      }

      if ($GLOBALS['whoisselecting'] == $contentPass && !$GLOBALS['selectedacolor'] && $GLOBALS['selectcolor']) {
        $sendback = '{"type": "selectcolor","content":"select"}';
        $from->send($sendback);
        $playedColorSelector = true;
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

          // $sendbackcontent = "<p><span class='game'>Game</span>: <span class='user'>$contentPass</span> Loadded $cardCount cards</p>";
          // $myfile = fopen("chat.txt", "a");
          // fwrite($myfile, $sendbackcontent . "\r\n");
          // fclose($myfile);
          // $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
          // $this->sendAll($sendback);
        }
      }

      $sendbackuser = getGameInfo('userloaded');

      foreach ($this->cliente as $cliente) {
        $cliente->send($sendbackuser);
      }
      // saveGame();
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
