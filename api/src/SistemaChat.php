<?php

namespace Api\WebSocket;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use stdClass;

$GLOBALS['game'] = array();
$json = "{
  'user': 'giovanni',
  'cards': [
    { 'value': '1', 'color': 'blue' },
    { 'value': '2', 'color': 'red' },
    { 'value': '4', 'color': 'yellow' },
    { 'value': '+2', 'color': 'red' },
    { 'value': '+4', 'color': 'black' },
    { 'value': 'changecolor', 'color': 'black' }
  ]
}";
array_push($GLOBALS['game'], json_encode($json));

class SistemaChat implements MessageComponentInterface
{
  protected $cliente;

  public function __construct()
  {
    // Iniciar o objeto que deve armazenar os clientes conectados
    $this->cliente = new \SplObjectStorage;
  }

  // Abrir conexão para o novo cliente
  public function onOpen(ConnectionInterface $conn)
  {
    // Adicionar o cliente na lista
    $this->cliente->attach($conn);
  }

  // Enviar mensagem para todos os usuários conectados
  public function onMessage(ConnectionInterface $from, $msg)
  {
    // echo "Usuário {$from->resourceId} enviou  uma mensagem. '$msg'\n\n";
    $decodeJSON = json_decode($msg);
    $type = $decodeJSON->type;
    $content = $decodeJSON->content;

    if ($type == 'message') {
      // Percorrer a lista de usuários conectados
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

      $myfile = fopen("chat.txt", "a");
      fwrite($myfile, $sendbackcontent . "\r\n");
      fclose($myfile);
    } else if ($type == 'server') {
      $contenttype = $content->type;
      $contentPass = $content->content;

      if ($contenttype == 'connected') {
        $lines = "";
        $goterror = false;

        $sendbackcontent = "<p><span class='server'>Server</span>: <span class='user'>$contentPass</span> $contenttype</p>";

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
        if ($goterror) {
          $sendback = '{"type": "message","content":"An error occure while trying to read filedata"}';
        } else {
          $sendback = '{"type": "message","content":"' . $lines . '"}';
        }

        $from->send($sendback);
      }

      $sendbackcontent = "<p><span class='server'>Server</span>: <span class='user'>$contentPass</span> $contenttype</p>";
      $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';

      $myfile = fopen("chat.txt", "a");
      fwrite($myfile, $sendbackcontent . "\r\n");
      fclose($myfile);

      // if (count($this->cliente) > 0) {
      foreach ($this->cliente as $cliente) {
        $cliente->send($sendback);
      }
      // }
    } else if ($type == 'game') {
      $contenttype = $content->type;
      $contentPass = $content->content;

      $sendbackcontent = "<p><span class='game'>Game</span>: <span class='user'>$contentPass</span> $contenttype</p>";
      $sendback = '{"type": "message","content":"' . $sendbackcontent . '"}';

      $myfile = fopen("chat.txt", "a");
      fwrite($myfile, $sendbackcontent . "\r\n");
      fclose($myfile);

      $gamefile = fopen("game.json", "w");
      $encodedGame = json_encode($GLOBALS['game']);
      $encodedGame = str_replace('\\\r\\\n', "\r\n", $encodedGame);
      $encodedGame = str_replace('["\"{', "[\r\n{", $encodedGame);
      $encodedGame = str_replace('}\""]', "}\r\n]", $encodedGame);
      fwrite($gamefile, $encodedGame);
      fclose($gamefile);

      // if (count($this->cliente) > 0) {
      foreach ($this->cliente as $cliente) {
        $cliente->send($sendback);
      }
    }
  }

  // Desconectar o cliente do websocket
  public function onClose(ConnectionInterface $conn)
  {
    // Fechar a conecxão e retirar o cliente da lista
    $this->cliente->detach($conn);

    // $myfile = fopen("chat.txt", "a");
    // fwrite($myfile, "Alguem desconectou | Usuários agora (" . count($this->cliente) . ")\r\n");
    // fclose($myfile);
    // echo "Usuário {$conn->resourceId} desconectou.\n\n";
  }

  // Função que será chamada caso ocorra algum erro no websocket
  public function onError(ConnectionInterface $conn, Exception $e)
  {
    // Fechar a conexão do cliente
    $conn->close();

    // echo "Ocorreu um erro {$e->getMessage()}.\n\n";
  }
}
