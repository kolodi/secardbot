<?php
include "lib.php";
include "tournaments.php";
include "challonge/challonge.class.php";

$telegram_token = "494619184:AAGgqciTKBa4nIs2QmpxX4ZXdqTJp8EmTdQ";
$challonge_token = "iWTgKx1WNQ48AJ77JMZNSHHfiil64WA7tMCsb0oC"; //Kolodi
$challonge_token = "i1Sax3ehsAUmFiq1N4gvuxElYpnqGAzCzKqAppMt"; //Jeff
$challonge = new ChallongeAPI($challonge_token);
$tournament_types = array(
    "single" => "single elimination", "double" => "double elimination", "rr" => "round robin", "swiss" => "swiss"
);

$debugOutput = null;

class SessionData
{
    public $tournament;
    public $creator;
}

$tg = new TG($telegram_token);

$lastUpdate = $tg->GetLastUpdate();
if ($lastUpdate == false)
    die("No Last Update");

$lastUpdate = json_decode($lastUpdate, true);
if ($lastUpdate["ok"] == false)
    die("Last Update Not OK");
if ($lastUpdate["result"] == false || count($lastUpdate["result"]) == 0)
    die("No result in update");

if(isset($lastUpdate["result"][0]["message"])) {
    $updateMessage = $lastUpdate["result"][0]["message"];
}elseif(isset($lastUpdate["result"][0]["edited_message"])) {
    $updateMessage = $lastUpdate["result"][0]["edited_message"];
}else{
    die("Unknown message");
}

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

        // call challonge api and create new single elimination tournament
        //TODO: how to check for a unique popup name ($updateText):
        // Solution 1: call challonge api to search by name and status in (pending, in_progress)

        $creator = $updateMessage["from"]["id"];
        $max_participants = 8;
        $tournament_type = 'single';
        $description = "";
        $url = $creator . "_" . uniqid();

        $popup_params = array(
            "tournament" => array(
                 "name" => $updateText
                ,"description" => $description
                ,"tournament_type" => isset($tournament_types[$tournament_type]) ? $tournament_types[$tournament_type] : $tournament_types['single']
                ,"signup_cap" => $max_participants
                ,"url" => $url
            )
        );
        $challonge_response = $challonge->createTournament($popup_params);

        if($challonge->hasErrors()) {
            $challonge->listErrors();
        }else{
            $sessionData = new SessionData();
            $sessionData->creator = $creator;
            $sessionData->tournament = new Popup();
            $sessionData->tournament->id = $challonge_response->id.""; //convert to string
            $sessionData->tournament->state = $challonge_response->state.""; //convert to string
            $sessionData->tournament->url = $url;
            $_SESSION["SessionData"] = serialize($sessionData);

            $txt = "New popup has been created, please /join_popup";
            $debugOutput = $tg->SendSimpleMessage($updateMessage["chat"]["id"], $txt);
        }

        break;
    case "/start_popup":
        break;
    case "/cancel_popup":
        if ($sessionData) {
            if ($sessionData->creator == $updateMessage["from"]["id"]) {
                // TODO: confirm message, challonge api call after confirm
                $challonge_response = $challonge->deleteTournament($sessionData->tournament->id);
                //echo "<pre>";print_r($challonge_response);die;

                if($challonge->hasErrors()) {
                    $challonge->listErrors();
                }else{
                    $txt = "popup destroyed!";
                    $debugOutput = $tg->SendSimpleMessage($updateMessage["chat"]["id"], $txt);
                    session_destroy();
                }

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
if(isset($challonge_response)) {
    echo "<pre>";print_r($challonge_response);
}
//$msg = new TextMessage("190257574", "Hello");
//$msg_string = json_encode($msg);

//echo ($tg->SendMessage($msg_string));

