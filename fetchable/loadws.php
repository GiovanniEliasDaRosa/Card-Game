<?php
$response = array();
$response["ok"] = false;
$response["serverADDR"] = 'null';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  header("Location: index.php");
  exit();
}

$response["ok"] = true;
$response["serverADDR"] = $_SERVER['SERVER_ADDR'];

header("Content-Type: application/json");
echo json_encode($response);
