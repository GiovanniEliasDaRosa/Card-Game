<?php
// Import user validation, and if necessary redirect to home
include('login.php');
// Import custom dumper made by Giovanni Elias da Rosa
include('dumpper/dumpper.php');

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Uno</title>
  <link rel="icon" href="img/favicon_16x16.png" sizes="16x16" />

  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/icons.css" />
  <link rel="stylesheet" href="css/chat.css" />
  <link rel="stylesheet" href="css/uno.css" />

  <script src="js/functions.js" defer></script>
  <script src="js/main.js" defer></script>
</head>

<body>
  <p id="serverADDR" style="display: none;" aria-disabled="true"><?= $_SERVER['SERVER_ADDR']; ?></p>
  <p id="id" style="display: none;" aria-disabled="true"><?= $GLOBALS['id'] ?></p>
  <main>
    <div id="uno">
      <div id="otherplayers"></div>

      <div id="table">
        <button class="button" id="getMoreCard">Pescar</button>
        <div id="currenttablecard"></div>
        <button class="button" id="playCard">Jogar carta</button>
        <div id="popupselectcolor">
          <button class="popupselectcolor__buttons red" data-id="0"></button>
          <button class="popupselectcolor__buttons blue" data-id="1"></button>
          <button class="popupselectcolor__buttons yellow" data-id="2"></button>
          <button class="popupselectcolor__buttons green" data-id="3"></button>
          <button class="button span2" id="popupselectcolorPlaySelected">Play selected</button>
        </div>
      </div>
      <div id="hand"></div>
    </div>
    <div id="chat">
      <div id="header">
        <h2>Chat</h2>
        <p id="userNameP">Bem vindo <span id="userName"><?= $GLOBALS['username'] ?></span></p>
        <div id="testHUD">
          <p id="testHUDplayers" class="testHUDicon icons user">0</p>
          <p id="testHUDup" class="testHUDicon icons nomargin up"></p>
          <p id="testHUDdown" class="testHUDicon icons nomargin down"></p>
        </div>
      </div>

      <div id="chatMessage">
        <div class="loadingspinner"></div>
      </div>

      <div id="newmessage">
        <input type="text" name="message" id="message" placeholder="Digite a mensagem..." />

        <input type="button" value="Enviar" class="button" id="sendmessage" />
      </div>
    </div>
  </main>
</body>

</html>