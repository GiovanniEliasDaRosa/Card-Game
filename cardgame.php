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
  <link rel="stylesheet" href="css/cards.css" />
  <link rel="stylesheet" href="css/hambuger.css" />

  <script src="js/functions.js" defer></script>
  <script src="js/visualvalidation.js" defer></script>
  <script src="js/main.js" defer></script>
  <script src="js/eventmanager.js" defer></script>
</head>

<body>
  <script>
    const serverADDR = "<?= $_SERVER['SERVER_ADDR']; ?>";
    const userName = "<?= $GLOBALS['username'] ?>";
    const userID = "<?= $GLOBALS['id'] ?>";
  </script>

  <main>
    <div id="uno">
      <p id="getcardcount">0</p>
      <div id="otherplayers"></div>
      <p id="gamedirection" class="icons nomargin rightarrow"></p>
      <div id="table">
        <button class="button" id="getMoreCard">Pescar</button>
        <div id="currenttablecard">
          <button data-value="loading" class="card loading currenttablecard" aria-disabled="true" disabled=""></button>
        </div>
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
        <p id="userNameP"><span id="userName"><?= $GLOBALS['username'] ?></span></p>
        <p id="activePlayers" class="icons user">0</p>
      </div>

      <div id="chatMessage" data-loading="true">
        <div class="loadingspinner"></div>
      </div>

      <div id="newmessage">
        <input type="text" name="message" id="message" placeholder="Digite a mensagem..." />

        <input type="button" value="Enviar" class="button" id="sendmessage" />
      </div>
    </div>
    <button class="button" id="menu">
      <div class="menu__item" id="menu__item1"></div>
      <div class="menu__item" id="menu__item2"></div>
      <div class="menu__item" id="menu__item3"></div>
      <p id="menu__notifications">0</p>
    </button>
  </main>
</body>

</html>