<?php
include "lib.php";
$tg = new TG("494619184:AAGgqciTKBa4nIs2QmpxX4ZXdqTJp8EmTdQ");


$buttons = array(
    "/join_popup JeffP7",
    "/join_popup Some other popup"
);
$keyboarReplyMessage = new MessageWithButtons("-1001212888265","Select popup to join:",405,$buttons);

echo json_encode($keyboarReplyMessage);