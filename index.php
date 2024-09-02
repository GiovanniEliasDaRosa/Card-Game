<?php
session_start();
ob_start();

$hassession = false;
$hascookie = false;

if (isset($_SESSION["username"])) {
  $hassession = true;
}

if (isset($_COOKIE["username"])) {
  $hascookie = true;
}

$username = "";
$hideTheWarning = false;
$usernameInputIsDisabled = "";
$warningIsDisabled = "";

if ($hassession) {
  $hideTheWarning = true;
  $username = $_SESSION["username"];
} else if ($hascookie) {
  $hideTheWarning = true;
  $username = $_COOKIE["username"];
}

$disable = 'aria-disabled="true" disabled';
$hideElem = 'style="display: none"' . $disable;
if ($hideTheWarning) {
  $usernameInputIsDisabled = $disable;
  $warningIsDisabled = $hideElem;
}

$errorDisabled = $hideElem;
$errorContent = "";

if (isset($_GET["e"])) {
  if ($_GET["e"] != '' && $_GET["v"] != '') {
    $ePassed = $_GET["e"];
    $namePassed = $_GET["v"];
    if ($ePassed == 'invalidname') {
      $errorDisabled = "";
      $username = $namePassed;
      $errorContent = "O nome \"$namePassed\" inválido";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Acessar Uno</title>
  <link rel="icon" href="img/favicon_16x16.png" sizes="16x16" />

  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/menu.css" />

  <script src="js/functions.js" defer></script>
  <script src="js/menu.js" defer></script>
</head>

<body>
  <main>
    <form action="login.php" method="POST" enctype="multipart/form-data">
      <div id="headerTitle">
        <img src="img/favicon_16x16.png" alt="Card image" id="cardImage" />
        <h1>Acessar Uno</h1>
      </div>
      <label for="username">Nome</label>
      <input type="text" name="username" id="username" placeholder="Digite o nome" required value="<?= $username ?>" <?= $usernameInputIsDisabled ?> />
      <p class="warn" id="error" <?= $errorDisabled ?>><?= $errorContent ?></p>
      <p class="warn" id="warntext" <?= $warningIsDisabled ?>>
        <strong>Atenção</strong>: Uma vez criado um nome, ele <strong>não poderá ser alterado</strong>.<br />
        Escolha com cuidado, pois será <strong>permanente</strong>.
      </p>
      <input type="submit" name="acess" value="Acessar" class="button" id="acessButton" />
    </form>
  </main>
</body>

</html>