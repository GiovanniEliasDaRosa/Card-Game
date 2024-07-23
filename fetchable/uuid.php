<?php
$response = array();
$response["ok"] = false;
$response["type"] = 'not ok';
$type = null;

// if ($_SERVER['REQUEST_METHOD'] != 'POST') {
//   header("Location: index.php");
//   exit();
// }

// if (!isset($_POST['uuid'])) {
//   $response["type"] = 'No uuid informed';
//   header("Content-Type: application/json");
//   echo json_encode($response);
//   exit();
// }
if (isset($_POST['type'])) {
  $type = $_POST['type'];
}

$testing = false;
if (isset($_POST['testing'])) {
  $testing = $_POST['testing'];
}

$testuuid = $_POST['uuid'];
$validuuid = true;
$validname = true;
$lines = 0;
$resulsts = array();
$invalidLine = null;
$myfile = fopen("../api/users.txt", "r");

while (($buffer = fgets($myfile, 4096)) !== false && $validuuid && $validname) {
  $explodedLine = explode(';', $buffer);

  if ($explodedLine[0] == $testuuid) {
    $validuuid = false;
    $invalidLine = $explodedLine;
  } else if ($type == 'saveifneeded') {
    if (str_replace("\r\n", "", $explodedLine[1]) == $_POST['name']) {
      $validname = false;
      $invalidLine = $explodedLine;
    }
  }
}
// if (!feof($myfile)) {
//   fclose($myfile);
//   $response["type"] = 'Error reading other uuids';
//   header("Content-Type: application/json");
//   echo json_encode($response);
//   exit();
// }
fclose($myfile);

// availableuuid
// saveifneeded

if (!$validuuid) {
  if ($type == 'saveifneeded') {
    $testname = $_POST['name'];
    if (str_replace("\r\n", "", $invalidLine[1]) == $testname) {
      $response["ok"] = true;
      $response["type"] = 'user login';
      header("Content-Type: application/json");
      echo json_encode($response);
      exit();
    } else {
      $response["type"] = 'uuid invalid';
      header("Content-Type: application/json");
      echo json_encode($response);
      exit();
    }
  } else if ($type == 'availableuuid' && $testing) {
    $response["ok"] = true;
    $response["type"] = 'ok';
    $response["name"] = str_replace("\r\n", "", $invalidLine[1]);
    header("Content-Type: application/json");
    echo json_encode($response);
    exit();
  } else {
    $response["type"] = 'uuid invalid';
    header("Content-Type: application/json");
    echo json_encode($response);
    exit();
  }
} else if ($type == 'saveifneeded') {
  if (!$validname) {
    $response["type"] = 'name invalid';
    header("Content-Type: application/json");
    echo json_encode($response);
    exit();
    $response["ok"] = true;
    $response["type"] = 'ok';

    header("Content-Type: application/json");
    echo json_encode($response);
  }
  $myfile = fopen("../api/users.txt", "a");
  $result = "$testuuid;" . $_POST['name'] . "\r\n";
  fwrite($myfile,  $result);
  fclose($myfile);

  $response["ok"] = true;
  $response["type"] = 'user login';
  header("Content-Type: application/json");
  echo json_encode($response);
  exit();
}

$response["ok"] = true;
$response["type"] = 'ok';

header("Content-Type: application/json");
echo json_encode($response);