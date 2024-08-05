<?php

use Api\WebSocket\system;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

require __DIR__ . '/vendor/autoload.php';

include_once('functions.php');
echo "\n";

/* <start of start configs> */
// Create needed files
$wantedfiles = ['chat.txt', 'debug.json', 'game.json', 'users.txt', 'usersonline.txt'];
$createdfiles = 0;
foreach ($wantedfiles as $file) {
  if (!file_exists($file)) {
    $myfile = fopen("$file", "w");
    fclose($myfile);
    $createdfiles++;
  }
}
if ($createdfiles > 0) {
  echo " Files created \n\n";
}

[$gameFileResult, $goterror] = loadGame();
if ($gameFileResult == "") {
  // Clear old chat
  $myfile = fopen("chat.txt", "w");
  fclose($myfile);
}
// Remove all active users
$myfile = fopen("usersonline.txt", "w");
fwrite($myfile, "[]");
fclose($myfile);
/* </End of start configs> */

echo " Server running | \e[44m[ Ctrl + C\e[44m ]\e[0m to stop\n\n";

$server = IoServer::factory(
  new HttpServer(
    new WsServer(
      new system
    )
  ),
  8080
);

// Start server and connections
$server->run();
