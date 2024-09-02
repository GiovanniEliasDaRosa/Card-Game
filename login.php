<?php
session_start();
ob_start();

function setNewCookie($name, $value)
{
  setcookie(
    $name,
    $value,
    time() + 60 * 60 * 24 * 30,
    false,
    false,
    true,
    true,
  );
}

function redirect($cause, $return = null)
{
  if ($return != null) {
    header("Location: index.php?$return");
  } else {
    header("Location: index.php");
  }
  exit();
}

function createID()
{
  $characthers = ["a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "z", "x", "y", "1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
  $result = "";
  for ($i = 0; $i < 10; $i++) {
    $result .= $characthers[random_int(0, count($characthers) - 1)];
  }
  return $result;
}

function serachUsers($type, $value, $path = '../')
{
  if ($type == 'id') {
    $valueSearched = $value;
  } else {
    $valueSearched = strtolower($value);
  }
  $valid = true;
  $invalidLine = null;
  $linepos = 0;
  $myfile = fopen($path . "api/users.txt", "r");
  while (($buffer = fgets($myfile, 4096)) !== false && $valid) {
    $explodedLine = str_replace("\r\n", "", explode(';', $buffer));

    if ($type == 'id') {
      if ($explodedLine[0] == $valueSearched) {
        $valid = false;
        $invalidLine = $explodedLine;
        break;
      }
    } else {
      if (strtolower($explodedLine[1]) == $valueSearched) {
        $valid = false;
        $invalidLine = $explodedLine;
        break;
      }
    }
    $linepos++;
  }
  fclose($myfile);

  return [$valid, $invalidLine, $linepos];
}

function resetSave()
{
  session_destroy();
  if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach ($cookies as $cookie) {
      $parts = explode('=', $cookie);
      $name = trim($parts[0]);
      setcookie($name, '', time() - 1000);
      setcookie($name, '', time() - 1000, '/');
    }
  }
}

function save($id, $username, $saveonfile = true, $linepos = null)
{
  $thistime = time();

  // Save as session
  $_SESSION["id"] = $id;
  $_SESSION["username"] = $username;
  $_SESSION["last_save"] = $thistime;

  // Save as cookie
  setNewCookie("id", $id);
  setNewCookie("username", $username);
  setNewCookie("last_save", $thistime);

  $updatedUser = "$id;$username;$thistime\r\n";
  // Save on file
  if ($saveonfile) {
    $myfile = fopen("api/users.txt", "a");
    fwrite($myfile,  $updatedUser);
    fclose($myfile);
  } else {
    $filename = "api/users.txt";
    $fileContent = file($filename);

    // Modify the desired line
    $fileContent[$linepos] = $updatedUser;

    // Write the modified content back to the file
    file_put_contents($filename, implode("", $fileContent));
  }
}

function validateUser($name, $fromUno)
{
  $hassession = true;
  $hascookie = true;
  if (!isset($_SESSION["username"])) {
    $hassession = false;
  }
  if (!isset($_COOKIE["username"])) {
    $hascookie = false;
  }

  $noSessionNoCookie = (!$hassession && !$hascookie);

  if ($fromUno && $noSessionNoCookie) {
    echo "<h2>INVALID USER</h2>\n";
    redirect('INVALID USER');
  }

  $namePassed = $name;
  [$validname, $invalidLine] = serachUsers("name", $namePassed, '');

  if ($noSessionNoCookie && $validname) {
    $valid = false;
    while (!$valid) {
      $newId = createID();
      [$validname, $invalidLine] = serachUsers("id", $newId, '');
      // run for all users, and find a id that dont repeat
      if ($validname) {
        $valid = true;
      }
    }

    // ID and Name are valid
    save($newId, $namePassed);

    // Valid, going to cardgame.php
    header("Location: cardgame.php");
    exit();
  } else {
    // NOT a valid name || OR hasSession/hasCookie
    if ($noSessionNoCookie) {
      redirect('No session and no cookie / not a valid name', "e=invalidname&v=$name");
    }

    if (!$hassession) {
      // no session geting the cookie value
      $_SESSION["id"] = $_COOKIE["id"];
      $_SESSION["username"] = $_COOKIE["username"];
      $_SESSION["last_save"] = $_COOKIE["last_save"];
    }
    // Check if ID's of this user and the file match, and if yes, send to cardgame.php
    $userIsSame = false;
    [$validid, $invalidLine, $linepos] = serachUsers("id", $_SESSION["id"], '');

    // Names match
    if ($invalidLine == null) {
      resetSave();
      redirect("User saved isn't on file anymore");
    }

    if ($invalidLine[0] == $_SESSION["id"] && $invalidLine[1] == $_SESSION["username"]) {
      $userIsSame = true;
    }

    // Check if session or cookie has a name saved
    if ($userIsSame) {
      // Is same user

      // ID and Name are valid, so resave them and don't save a new line on file
      save($_SESSION["id"], $_SESSION["username"], false, $linepos);

      if ($fromUno) {
        $GLOBALS['id'] = $_SESSION["id"];
        $GLOBALS['username'] = $_SESSION["username"];
        return;
      }

      // Valid, going to cardgame.php
      header("Location: cardgame.php");
      exit();
    } else {
      redirect("trying to acess as another user");
    }
  }
}

$GLOBALS['id'] = 'undefined';
$GLOBALS['username'] = 'undefined';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  validateUser("", true);
  return;
} else if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  // Loaded not from index.php neither cardgame.php, user trying to break system
  redirect('NO POST');
}

if (!isset($_POST['username']) || trim($_POST['username']) == '') {
  redirect('No username informed or Username empty');
}

validateUser(trim($_POST['username']), false);
