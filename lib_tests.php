<?php
include "lib.php";
$tg = new TG("494619184:AAGgqciTKBa4nIs2QmpxX4ZXdqTJp8EmTdQ");
//$r = $tg->SendSimpleMessage(190257574, "Hello");
//$r = $tg->SendPromptMessage(190257574, "Prompt message", 86);
//$r = $tg->SendPromptWithButtonsInColumn(190257574, "Buttons keyboard", 86, array("button1", "button2"));
//$r = $tg->SendRemoveKeyboardMessage(190257574, "remove keyboard message", 86);
header("content-type: application/json");
//$decoded = json_decode($r);

include "challonge/challonge.class.php";
$challonge_token = "iWTgKx1WNQ48AJ77JMZNSHHfiil64WA7tMCsb0oC"; //Kolodi
$challongeAPI = new ChallongeAPI($challonge_token);
$decoded = $challongeAPI->GetTournamentsJSON(array(
	"state" => "all"
));

echo json_encode($decoded, JSON_PRETTY_PRINT);
