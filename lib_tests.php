<?php
include "lib.php";
$tg = new TG("494619184:AAGgqciTKBa4nIs2QmpxX4ZXdqTJp8EmTdQ");
//$r = $tg->SendSimpleMessage(190257574, "Hello");
//$r = $tg->SendPromptMessage(190257574, "Prompt message", 86);
$r = $tg->SendPromptWithButtonsInColumn(190257574, "Buttons keyboard", 86, array("button1", "button2"));
//$r = $tg->SendRemoveKeyboardMessage(190257574, "remove keyboard message", 86);
header("content-type: application/json");
$decoded = json_decode($r);
echo json_encode($decoded, JSON_PRETTY_PRINT);

 
/*
$buttons = array(
    "/join_popup JeffP7",
    "/join_popup Some other popup"
);
$keyboarReplyMessage = new MessageWithButtons("-1001212888265","Select popup to join:",405,$buttons);

echo json_encode($keyboarReplyMessage);
*/
/*
$msg = array(
    "chat_id" => 334454,
    "text" => "remove keyboar message",
    "reply_to_message_id" => 15,
    "reply_markup" => array(
    	"remove_keyboard" => true,
    	"selective" => true
    )
);

echo json_encode($msg, JSON_PRETTY_PRINT);
*/
