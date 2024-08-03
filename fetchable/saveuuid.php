<?php
$response = array();
$response["ok"] = false;
$response["type"] = 'not ok';


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

$testuuid = $_POST['uuid'];
$testname = $_POST['name'];
$valid =  true;
$lines = 0;
$resulsts = array();

$invalidLine = null;
$myfile = fopen("../api/users.txt", "r+");

while (($buffer = fgets($myfile, 4096)) !== false && $valid) {
  if (explode(';', $buffer)[0] == $testuuid) {
    $valid = false;
    $invalidLine = explode(';', $buffer);
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

if (!$valid) {
  if ($invalidLine[1] != $testname) {
    $response["type"] = 'uuid invalid';
    header("Content-Type: application/json");
    echo json_encode($response);
    exit();
  }
}

$response["ok"] = true;
$response["type"] = 'ok';
$_SESSION['uuid'] = $testuuid;
$_SESSION['name'] = $testname;
session_start();
ob_start();

header("Content-Type: application/json");
echo json_encode($response);
