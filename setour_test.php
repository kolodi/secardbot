<?php

$token = "494619184:AAGgqciTKBa4nIs2QmpxX4ZXdqTJp8EmTdQ";

include "lib.php";
include "tournaments.php";
$debugOutput = null;

class SessionData
{
    public $tournament;
    public $creator;
}

$tg = new TG($token);

$lastUpdate = $tg->GetLastUpdate();
if ($lastUpdate == false)
    die("No Last Update");

$lastUpdate = json_decode($lastUpdate, true);
if ($lastUpdate["ok"] == false)
    die("Last Update Not OK");
if ($lastUpdate["result"] == false || count($lastUpdate["result"]) == 0)
    die("No result in update");

$updateMessage = $lastUpdate["result"][0]["message"];

session_id("popup");
session_start();

$commands = array(
    "/start",
    "/help",
    "/new_popup",
    "/start_popup",
    "/cancel_popup",
    "/join_popup",
    "/quit_popup",
    "/kick",
    "/participants",
    "/opponent",
    "/popup_results",
    "/report_score",
    "/confirm_score"
);
$noSessionAvailableCommands = array("/start", "/help", "/new_popup", "/popup_results");

$updateText = $updateMessage["text"];
$updateCommand = "";
if (isset($updateMessage["entities"])) {
    foreach ($updateMessage["entities"] as $entity) {
        if ($entity["type"] == "bot_command") {
            $updateCommand = substr($updateText, $entity["offset"], $entity["length"]);
            $updateText = str_replace($updateCommand, "", $updateText);
            $updateText = trim($updateText);
        }
    }
} else if (isset($updateMessage["reply_to_message"]) && isset($updateMessage["reply_to_message"]["entities"])) {
    foreach ($updateMessage["reply_to_message"]["entities"] as $entity) {
        if ($entity["type"] == "bot_command") {
            $updateCommand = substr($updateMessage["reply_to_message"]["text"], $entity["offset"], $entity["length"]);
        }
    }
}

//trim @setourbot
$updateCommand = str_replace("@setourbot", "", $updateCommand);

if (!$updateCommand || !in_array($updateCommand, $commands))
    die("No command");


$dataInSession = isset($_SESSION["SessionData"]); // bool
$sessionData = null;
if ($dataInSession) {
    $sessionData = unserialize($_SESSION["SessionData"]);
}

switch ($updateCommand) {
    case "/start":
        if ($sessionData) {
            $txt = "There is already a popup in place, ";
            if ($sessionData->tournament->state == "pending")
                $txt .= "you can /join_popup";
            else
                $txt .= "please wait for it to finish, check /participants or look at /popup_results";

            $debugOutput = $tg->SendSimpleMessage($updateMessage["chat"]["id"], $txt);

        } else {
            $txt = "Welcome to the popup bot. Use command /new_popup to create new popup or /help";
            $debugOutput = $tg->SendSimpleMessage($updateMessage["chat"]["id"], $txt);
        }
        break;
    case "/help":
        $helpText = file_get_contents("popup_help.txt");
        $msg = new TextMessage($updateMessage["chat"]["id"], $helpText);
        $msg_string = json_encode($msg);
        $debugOutput = $tg->SendMessage($msg_string);
        break;
    case "/new_popup":
        if ($sessionData) {
            $txt = "There is already a popup in place, ";
            if ($sessionData->tournament->state == "pending")
                $txt .= "you can /join_popup";
            else
                $txt .= "please wait for it to finish, check /participants or look at /popup_results";

            $debugOutput = $tg->SendSimpleMessage($updateMessage["chat"]["id"], $txt);
            break;
        } 
        if ($updateMessage["chat"]["type"] == "private") {
            $txt = "Popup can only be created in public chat";
            $debugOutput = $tg->SendSimpleMessage($updateMessage["chat"]["id"], $txt);
            break;
        }
        
        
        if(!$updateText) {
            $txt = "/new_popup, please give unique name to the popup:";
            $debugOutput = $tg->SendPromptMessage($updateMessage["chat"]["id"], $txt, $updateMessage["message_id"]);
            break;
        }
        
        // TODO: 
        // call challonge api and create new single elimination tournament
        
        $sessionData = new SessionData();
        $sessionData->creator = $updateMessage["from"]["id"];
        $sessionData->tournament = new Popup();
        $sessionData->tournament->state = "pending";
        $_SESSION["SessionData"] = serialize($sessionData);

        $txt = "New popup has been created, plese /join_popup";
        $debugOutput = $tg->SendSimpleMessage($updateMessage["chat"]["id"], $txt);


        break;
    case "/start_popup":
        break;
    case "/cancel_popup":
        if ($sessionData) {
            if ($sessionData->creator == $updateMessage["from"]["id"]) {
                // TODO: confirm message, challonge api call after confirm
                $txt = "popup destroyed!";
                $debugOutput = $tg->SendSimpleMessage($updateMessage["chat"]["id"], $txt);
                session_destroy();
            } else {
                $txt = "You are not creator of current popup";
                $debugOutput = $tg->SendSimpleMessage($updateMessage["chat"]["id"], $txt);
            }
        } else {
            $txt = "There is no popup, you can create one using /new_popup command";
            $debugOutput = $tg->SendSimpleMessage($updateMessage["chat"]["id"], $txt);
        }
        break;
    case "/join_popup":
        break;
    case "/quit_popup":
        break;
    case "/kick":
        break;
    case "/participants":
        break;
    case "/opponent":
        break;
    case "/popup_results":
        break;
    case "/report_score":
        break;
    case "/confirm_score":
        break;
}


header('Content-Type: application/json');
echo $debugOutput;
//$msg = new TextMessage("190257574", "Hello");
//$msg_string = json_encode($msg);

//echo ($tg->SendMessage($msg_string));

