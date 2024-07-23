<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>

  <link rel="stylesheet" href="dumpper/dumpper.css">
  <link rel="stylesheet" href="css/style.css">
  <script src="dumpper/dumpper.js" defer></script>
</head>


<?php
function dump($code)
{

  $trace = debug_backtrace()[0];

  $file = str_replace('C:\xampp\htdocs\\', '', $trace['file']);
  $line = $trace["line"];

  echo "<div class='dumpper__div'>
    <span class='dumpper__header'>$file | $line</span>
    <pre class='dumpper'>";
  var_dump($code);
  echo "</pre></div>";
}
?>