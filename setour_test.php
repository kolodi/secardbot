<?php

$token = "494619184:AAGgqciTKBa4nIs2QmpxX4ZXdqTJp8EmTdQ";

include "lib.php";

$tg = new TG($token);

$msg = new TextMessage("190257574", "Hello");
$msg_string = json_encode($msg);

echo($tg->SendMessage($msg_string));

