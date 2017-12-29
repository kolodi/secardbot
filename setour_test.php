<?php
include "lib.php";
include "tournaments.php";
include "challonge/challonge.class.php";

/*****************************************
 * CONFIGURATION
 *****************************************/
$telegram_token = "494619184:AAGgqciTKBa4nIs2QmpxX4ZXdqTJp8EmTdQ";
$challonge_token = "iWTgKx1WNQ48AJ77JMZNSHHfiil64WA7tMCsb0oC"; //Kolodi
$challonge_token = "i1Sax3ehsAUmFiq1N4gvuxElYpnqGAzCzKqAppMt"; //Jeff
$tournament_types = array(
    "single" => "single elimination",
    "double" => "double elimination",
    "rr" => "round robin",
    "swiss" => "swiss"
);

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

/*****************************************
 * RESOURCES
 *****************************************/
$telegramAPI = new TG($telegram_token);
$challongeAPI = new ChallongeAPI($challonge_token);

$telegram = json_decode($telegramAPI->GetLastUpdate(), true);
$challonge = $challongeAPI->getTournaments(array(
    //--Tournament Filter--
    "state" => "all"
    //"created_after" => date("Y-m-d", strtotime("-1 day"))
    //,"created_before" => date("Y-m-d")
));

$challongePending = array();
$challongeInProgress = array();
if (isset($challonge->tournament)) {
    //--Multiple active and pending tournaments--
    foreach ($challonge->tournament as $tournament) {
        if ($tournament->state == "pending") {
            $tournamentVariableName = "challongePending";
        } else {
            $tournamentVariableName = "challongeInProgress";
        }
        $creator = explode("_", $tournament->url . "");
        $creator = isset($creator[0]) ? $creator[0] : '';
        ${$tournamentVariableName}[] = array(
            'id' => $tournament->id . "",
            'name' => $tournament->name . "",
            'url' => $tournament->url . "",
            'creator' => $creator
        );
    }
}
$challongePendingPlusInProgress = array_merge($challongePending, $challongeInProgress);

/*****************************************
 * VALIDATION
 *****************************************/
if ($telegram == false)
    die("No Last Update");
if ($telegram["ok"] == false)
    die("Last Update Not OK");
if ($telegram["result"] == false || count($telegram["result"]) == 0)
    die("No result in update");
if (isset($telegram["result"][0]["message"])) {
    $telegramMessage = $telegram["result"][0]["message"];
} elseif (isset($telegram["result"][0]["edited_message"])) {
    $telegramMessage = $telegram["result"][0]["edited_message"];
} else {
    die("Unknown message");
}

$telegramText = $telegramMessage["text"];
$telegramUser = $telegramMessage["from"];
$telegramCommand = "";
if (isset($telegramMessage["entities"])) {
    foreach ($telegramMessage["entities"] as $entity) {
        if ($entity["type"] == "bot_command") {
            $telegramCommand = substr($telegramText, $entity["offset"], $entity["length"]);
            $telegramText = str_replace($telegramCommand, "", $telegramText);
            $telegramText = trim($telegramText);
        }
    }
} else if (isset($telegramMessage["reply_to_message"]) && isset($telegramMessage["reply_to_message"]["entities"])) {
    foreach ($telegramMessage["reply_to_message"]["entities"] as $entity) {
        if ($entity["type"] == "bot_command") {
            $telegramCommand = substr($telegramMessage["reply_to_message"]["text"], $entity["offset"], $entity["length"]);
        }
    }
}

$telegramCommand = str_replace("@setourbot", "", $telegramCommand);
if (!$telegramCommand || !in_array($telegramCommand, $commands))
    die("No command");

//echo "<pre>";print_r($challonge);
//print_r($challongePending);
//print_r($challongeInProgress);
//echo "<pre>";print_r($telegram);
//echo "<pre><br />\$telegramMessage => "; print_r($telegramMessage);
//echo "<pre><br />\$telegramText => $telegramText";
//echo "<pre><br />\$telegramCommand => $telegramCommand";
//die;

/*****************************************
 * Command Definition
 *****************************************/

$debugOutput = "";
switch ($telegramCommand) {
    case "/start":
        //TODO: implement
        break;
    case "/help":
        $helpText = file_get_contents("popup_help.txt");
        $msg = new TextMessage($telegramMessage["chat"]["id"], $helpText);
        $msg_string = json_encode($msg);
        $debugOutput = $tg->SendMessage($msg_string);
        break;
    case "/new_popup":

        if ($telegramMessage["chat"]["type"] == "private") {
            $txt = "Popup can only be created in public chat";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramMessage["chat"]["id"], $txt);
            break;
        }

        $hasPopupRunning = false;
        foreach ($challongePendingPlusInProgress as $t) {
            if($telegramUser["id"] == $t["creator"]) {
                $hasPopupRunning = true;
                $txt = "You already have a popup running, please finish current one before creating new one";
                $debugOutput = $telegramAPI->SendSimpleMessage($telegramMessage["chat"]["id"], $txt);
                break;
            }
        }
        if($hasPopupRunning) break;

        if (!$telegramText) {
            $txt = "/new_popup, please give unique name to the popup:";
            $debugOutput = $telegramAPI->SendPromptMessage($telegramMessage["chat"]["id"], $txt, $telegramMessage["message_id"]);
            break;
        }

        $creator = $telegramMessage["from"]["id"];
        $max_participants = 8;
        $tournament_type = 'single';
        $description = "";
        $url = $creator . "_" . uniqid();

        $popup_params = array(
            "tournament" => array(
                "game_name" => 'Shadow Era', "name" => $telegramText, "description" => $description, "tournament_type" => isset($tournament_types[$tournament_type]) ? $tournament_types[$tournament_type] : $tournament_types['single'], "signup_cap" => $max_participants, "url" => $url
            )
        );
        $challonge_response = $challongeAPI->createTournament($popup_params);
        $txt = "New popup has been created, please /join_popup";
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramMessage["chat"]["id"], $txt);
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramMessage["chat"]["id"], "http://challonge.com/$url"); //for testing only

        break;
    case "/start_popup":

        //TODO: implement
        break;
    case "/cancel_popup":
        //TODO: implement
        break;
    case "/join_popup":
        //TODO: implement
        break;
    case "/quit_popup":
        //TODO: implement
        break;
    case "/kick":
        //TODO: implement
        break;
    case "/participants":
        //TODO: implement
        break;
    case "/opponent":
        //TODO: implement
        break;
    case "/popup_results":
        //TODO: implement
        break;
    case "/report_score":
        //TODO: implement
        break;
    case "/confirm_score":
        //TODO: implement
        break;
}

header('Content-Type: application/json');
echo $debugOutput;