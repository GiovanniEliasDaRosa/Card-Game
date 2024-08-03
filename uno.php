<?php
session_start();
ob_start();

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
  <?php
  $serverADDR = $_SERVER['SERVER_ADDR'];

  function redirect()
  {
    session_destroy();
    header("Location: index.php");
    exit();
  }

  if (count($_SESSION) == 0) {
    // No session
    redirect();
  }

  if ($_SESSION['uuid'] == null || $_SESSION['name'] == null) {
    // No session ( no uuid or name )
    redirect();
  }

  $sessionUuid = $_SESSION['uuid'];
  $sessionName = $_SESSION['name'];

  $validuuid = true;
  $validname = true;
  $invalidLine = null;
  $myfile = fopen("api/users.txt", "r");
  while (($buffer = fgets($myfile, 4096)) !== false && $validuuid && $validname) {
    $explodedLine = explode(';', $buffer);

    if ($explodedLine[0] == $sessionUuid) {
      $validuuid = false;
      $invalidLine = $explodedLine;
    }
  }
  fclose($myfile);


  // Found uuid in file
  // if (!$validuuid) {
  // Check name on file and this user name
  if (str_replace("\r\n", "", $invalidLine[1]) != $sessionName) {
    // Check name on file and this user name is different end session and redirect
    redirect();
  }
  // }

  ?>
  <p id="serverADDR" style="display: none;" aria-disabled="true"><?= $serverADDR ?></p>
  <p id="sessionUuid" style="display: none;" aria-disabled="true"><?= $sessionUuid ?></p>
  <p id="sessionName" style="display: none;" aria-disabled="true"><?= $sessionName ?></p>
  <script>
    let userName = localStorage.getItem("username");
    let userUuid = localStorage.getItem("userid");

    if (userUuid == null || userName == null) {
      <?= redirect(); ?>
    }

    if (
      userUuid != document.querySelector("#sessionUuid").innerText ||
      userName != document.querySelector("#sessionName").innerText
    ) {
      <?= redirect(); ?>
    }
  </script>

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
        <p id="userNameP">Bem vindo <span id="userName">UNDEFINED</span></p>
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