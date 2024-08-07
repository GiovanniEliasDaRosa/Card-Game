<?php

namespace Api\WebSocket;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use stdClass;

$GLOBALS['usersonline'] = array();
resetGame(true, true);

class system implements MessageComponentInterface
{
  protected $client;

  public function __construct()
  {
    $this->client = new \SplObjectStorage;
  }

  public function onOpen(ConnectionInterface $conn)
  {
    $this->client->attach($conn);
  }

  public function onMessage(ConnectionInterface $from, $msg)
  {
    $decodeJSON = json_decode($msg);
    $type = $decodeJSON->type;
    $content = $decodeJSON->content;

    // null type or content
    if (!isset($decodeJSON->type) || !isset($decodeJSON->content)) return;

    /******VALIDATE USER*****/
    // null id or name
    if (!isset($content->id) || !isset($content->user)) return;

    $userId = $content->id;
    $userName = $content->user;

    $invalidUser = true;
    $myfile = fopen("users.txt", "r");
    while (($buffer = fgets($myfile, 4096)) !== false && $invalidUser) {
      $explodedLine = str_replace("\r\n", "", explode(';', $buffer));

      if ($explodedLine[0] == $userId && $explodedLine[1] == $userName) {
        $invalidUser = false;
        break;
      }
    }
    fclose($myfile);

    if ($invalidUser) return;

    if ($type == 'message') {
      // $user = $content->user;
      $message = $content->message;

      $sendbackcontent = "<p>" . date('y/m/d h:i:s a') . " | <span class='user'>$userName</span>: $message</p>";
      $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';

      foreach ($this->client as $client) {
        // if ($from !== $client)
        $client->send($sendback);
      }

      saveChat($sendbackcontent);
    } else if ($type == 'server') {
      $contenttype = $content->type; // type
      // $contentPass = $content->content; // content
      // $name = ------------------------------

      if ($contenttype == 'connected') {
        $lines = "";
        $goterror = false;

        $sendbackcontent = "<p><span class='server'>Server</span>: <span class='user'>$userName</span> connected</p>";

        array_push($GLOBALS['usersonline'], $userName);
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

        $lines .= $sendbackcontent;

        $sendback = '{"type": "message","content":"' . $lines . '"}';
        if ($goterror) {
          $sendback = '{"type": "message","content":"An error occure while trying to read filedata"}';
        }

        $from->send($sendback);

        saveChat($sendbackcontent);

        $sendbackallconected = '{"type": "message","content":"' . $sendbackcontent . '"}';

        foreach ($this->client as $client) {
          if ($from != $client) {
            $client->send($sendbackallconected);
          }
        }

        if ($GLOBALS['gamestarted']) {
          $sendbackgamestarted = '{"type":"game", "started": true}';
          $from->send($sendbackgamestarted);
        }
      } else {
        $sendbackcontent = "<p><span class='server'>Server</span>: <span class='user'>$userName</span> disconnected</p>";
        $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
        saveChat($sendbackcontent);

        foreach ($this->client as $client) {
          $client->send($sendback);
        }

        if (count($GLOBALS['usersonline']) > 0) {
          $GLOBALS['searchname'] = $userName;
          $GLOBALS['usersonline'] = array_values(array_filter($GLOBALS['usersonline'], "filternameout"));
        }
        $GLOBALS['searchname'] = '';

        $myfile = fopen("usersonline.txt", "w");
        fwrite($myfile, json_encode($GLOBALS['usersonline']));
        fclose($myfile);

        $this->sendAll(getGameInfo());
      }
    } else if ($type == 'game') {
      $contenttype = $content->type; // type

      if ($contenttype == 'startgame') {
        if (count($GLOBALS['usersonline']) < 2) {
          $sendbackcontent = "<p><span class='game'>Game</span>: Somente você está ativo, é necessário de pelo menos 2 pessoas para iniciar</p>";
          $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
          $from->send($sendback);
          return;
        }

        // Reset all game, but leave the gameendend as it is
        resetGame(true, false);

        $GLOBALS['gamestarted'] = true;

        [$gameFileResult, $goterror] = loadGame();

        // Can't load a game Or this game endend
        if ($gameFileResult == "" || $GLOBALS['gameendend']) {
          // Clear old chat
          $myfile = fopen("chat.txt", "w");
          fclose($myfile);

          $GLOBALS['gameendend'] = false;
          $cardcount = 7;

          echo " \e[41m No game found, creating a new one \e[49m\n";
          $quantusers =  count($GLOBALS['usersonline']) * $cardcount;
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
            for ($card = 0; $card < $cardcount; $card++) {
              array_push($randomcards, getOneCardFromDeck());
            }
            $newUser = new stdClass();
            $newUser->user = $GLOBALS['usersonline'][$i];
            usort($randomcards, 'reorderCards');
            $newUser->cards = $randomcards;
            array_push(
              $GLOBALS['game'],
              $newUser
            );
          }

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
              $GLOBALS['tablecard'] = $cardgot[0];
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

          $sendbackcontent = "<p><span class='game'>Game</span>: <span class='user'>$userName</span> Começou o jogo</p>";
          $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
          saveChat($sendbackcontent);
          $sendbackgamestarted = '{"type":"game", "started": true}';

          foreach ($this->client as $client) {
            $client->send($sendback);
            $client->send($sendbackgamestarted);
          }
        } else {
          echo " \e[44m Game found, loading it... \e[49m\n";
          saveLoadedGameToServer(json_decode($gameFileResult));

          $sendbackcontent = "<p><span class='game'>Game</span>: <span class='user'>$userName</span> Continuou o jogo</p>";
          $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
          saveChat($sendbackcontent);
          $this->sendAll($sendback);

          $sendbackgamestarted = '{"type":"game", "started": true}';

          foreach ($this->client as $client) {
            $client->send($sendback);
            $client->send($sendbackgamestarted);
          }
        }
      }

      if ($GLOBALS['gameendend']) {
        $this->sendAll(getGameInfo());
        $this->gameEndend();
        return;
      };

      if (!$GLOBALS['gamestarted']) {
        $sendbackcontent = "<p><span class='game'>Game</span>: O jogo ainda não iniciou</p>";
        $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
        $from->send($sendback);
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

        if ($GLOBALS['turn'] != $userName) return;
        if ($GLOBALS['turnhasplayed']) return;
        if ($GLOBALS['turnhasgotnewcard']) {
          $this->sendAll(passTurn());
          return;
        }
        if ($GLOBALS['getcardcount'] > 0) {
          return;
        }

        $gotcard = false;

        for ($i = 0; $i < count($GLOBALS['game']); $i++) {
          $current = $GLOBALS['game'][$i];
          $currentname = $current->user;
          // $currentcards = $current->cards;

          if ($currentname == $userName) {
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
            usort($GLOBALS['game'][$i]->cards, 'reorderCards');
            $cards = $GLOBALS['game'][$i]->cards;

            // Send user his cards with value and color
            $usercards = json_encode($cards);
            $sendback = '{"type":"game", "who":"' . $userName . '", "content":' . $usercards . '}';
            $from->send($sendback);

            $GLOBALS['turnhasgotnewcard'] = true;
            break;
          }
        }

        if (!$gotcard) return;

        // $sendbackcontent = "<p><span class='game'>Game</span>: <span class='user'>$currentname</span> Pescou uma carta</p>";
        // $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
        // $myfile = fopen("chat.txt", "a");
        // fwrite($myfile, $sendbackcontent . "\r\n");
        // fclose($myfile);
        // $client->send($sendback);

        $sendbackuser = getGameInfo();

        foreach ($this->client as $client) {
          $client->send($sendbackuser);
        }

        saveGame();
        return;
      }

      if ($contenttype == 'wanttoplaycard') {
        $canplay = false;
        $playerpos = 0;
        $playedColorSelector = false;
        $value = $content->value;
        $color = $content->color;

        for ($i = 0; $i < count($GLOBALS['game']); $i++) {
          $current = $GLOBALS['game'][$i];
          $currentname = $current->user;
          $currentcards = $current->cards;
          if ($GLOBALS['turn'] != $userName) break;
          if ($GLOBALS['turnhasplayed']) break;

          if ($currentname == $userName) {
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
                  if ($currentcardvalue == "wild") {
                    if ($GLOBALS['getcardcount'] > 0) {
                      // if table card is a special card is on the table
                      break;
                    }
                  }
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
                  $GLOBALS['whoisselecting'] = $userName;
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

                  if ($GLOBALS['getcardcount'] > 0) {
                    if ($currentcardvalue == "skip" || $currentcardvalue == "reverse") {
                      break;
                    }
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

                  if ($GLOBALS['getcardcount'] > 0) {
                    // if table card is a special card is on the table
                    if (
                      $cardontablevalue == "wild" ||
                      $cardontablevalue == "wilddrawfour" ||
                      $cardontablevalue == "skip" ||
                      $cardontablevalue == "reverse" ||
                      $cardontablevalue == "draw2"
                    ) {
                      break;
                    }
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

        $currentcardvalue = $GLOBALS['tablecard']->value;
        $currentcardcolor = $GLOBALS['tablecard']->color;

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
            $this->gameEndend();
          }
          saveGame();
          return;
        }

        $current = $GLOBALS['game'][$playerpos];
        $name = $current->user;
        $cards = $current->cards;

        usort($GLOBALS['game'][$playerpos]->cards, 'reorderCards');

        $usercards = json_encode($cards);

        $sendback = '{"type":"game", "who":"' . $name . '", "content":' . $usercards . '}';
        $from->send($sendback);

        if ($GLOBALS['selectedacolor']) {
          $GLOBALS['whoisselecting'] = "";
          $GLOBALS['selectedcolor'] = "";
          $GLOBALS['selectedacolor'] = false;
          $GLOBALS['selectcolor'] = false;
          $sendback = '{"type": "selectcolor","content":"close"}';
          $from->send($sendback);
        }

        if ($playedColorSelector) {
          $this->sendAll(getGameInfo());
        } else {
          $this->sendAll(passTurn());
        }

        saveGame();
        return;
      }

      if ($contenttype == 'selectedcolor' && $GLOBALS['selectcolor']) {
        if (!isset($content->colorid)) {
          echo "Cannot acess colorID " . json_encode($content) . "\n";
          return;
        }
        $colorid = $content->colorid;

        $colors = ['red', 'blue', 'yellow', 'green'];
        $color = $colors[$colorid];
        $GLOBALS['selectedcolor'] = $color;
        $GLOBALS['selectedacolor'] = true;
        $GLOBALS['selectcolor'] = false;
        $sendback = '{"type": "selectcolor","content":"close"}';
        $from->send($sendback);
        $this->sendAll(passTurn());
      }

      if ($GLOBALS['whoisselecting'] == $userName && !$GLOBALS['selectedacolor'] && $GLOBALS['selectcolor']) {
        $sendback = '{"type": "selectcolor","content":"select"}';
        $from->send($sendback);
        $playedColorSelector = true;
      }

      // Send user his cards with value and color
      for ($i = 0; $i < count($GLOBALS['game']); $i++) {
        $current = $GLOBALS['game'][$i];
        $name = $current->user;
        $cards = $current->cards;

        if ($name == $userName) {
          $cardCount = count($cards);
          $usercards = json_encode($cards);
          $sendback = '{"type":"game", "who":"' . $userName . '", "content":' . $usercards . '}';
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

      foreach ($this->client as $client) {
        $client->send($sendbackuser);
      }
      // saveGame();
    }
  }

  public function onClose(ConnectionInterface $conn)
  {
    $this->client->detach($conn);
  }

  public function onError(ConnectionInterface $conn, Exception $e)
  {
    $conn->close();
  }

  public function sendAll($message)
  {
    foreach ($this->client as $client) {
      $client->send($message);
    }
  }

  public function gameEndend()
  {

    $winners = "";
    $winnerCount = count($GLOBALS['usersthatwon']);

    for ($i = 0; $i < $winnerCount; $i++) {
      $winners .= $GLOBALS['usersthatwon'][$i];
      if ($i < $winnerCount - 1) {
        $winners .= " | ";
      }
    }

    $GLOBALS['gameendend'] = true;
    $sendbackgame = '{"type": "game", "whowon":' . json_encode($GLOBALS['usersthatwon']) . ', "gameendend": true}';

    $loser = $GLOBALS['game'][0]->user;

    $text = "os ganhadores foram";
    if ($winnerCount == 1) {
      $text = "o ganhador foi";
    }

    $sendbackcontent = "<p><span class='game'>Game</span>: O jogo acabou, $text $winners e $loser perdeu</p>";
    $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';
    saveChat($sendbackcontent);

    foreach ($this->client as $client) {
      $client->send($sendback);
      $client->send($sendbackgame);
    }
  }
}
