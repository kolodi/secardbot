<?php

include "lib.php";
include "db.php";
include "process_query.php";



$q = strtolower($_GET["q"]);

$db = new DB();
$responce = GetInlineAnswer($q, $db);

header('Content-Type: application/json');
exit(json_encode($responce, JSON_PRETTY_PRINT));
