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
    "/close",
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
$challongeTournaments = array();
$challongeTournamentMapByName = array();
if (isset($challonge->tournament)) {
    //--Multiple active and pending tournaments--
    foreach ($challonge->tournament as $tournament) {
        $id = $tournament->id . "";
        $name = $tournament->name . "";
        $url = $tournament->url . "";
        $creator = explode("_", $url);
        $creator = isset($creator[0]) ? $creator[0] : '';
        $max_participants = $tournament->{'signup-cap'}+0;
        $participant_count = $tournament->{'participants-count'}+0;
        $challongeTournaments[$id] =  array(
            'id' => $id,
            'name' => $name,
            'url' => $url,
            'creator' => $creator,
            'max_participants' => $max_participants,
            'participant_count' => $participant_count
        );
        $challongeTournamentMapByName[strtolower($name)] = $id;
        if ($tournament->state == "pending") {
            $challongePending[$id] = $name;
        } else {
            $challongeInProgress[$id] = $name;
        }

    }
}

session_id("popup");
session_start();
$sessionData = array();
if (isset($_SESSION["SessionData"])) {
    $sessionData = unserialize($_SESSION["SessionData"]);
}

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

$telegramText = isset($telegramMessage["text"]) ? $telegramMessage["text"] : '';
$telegramUser = $telegramMessage["from"];
$telegramChatId = $telegramMessage["chat"]["id"];
$telegramMessageId = $telegramMessage['message_id'];
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
$telegramTextLower = strtolower($telegramText);
if (!$telegramCommand || !in_array($telegramCommand, $commands))
    die("No command");

//--Debug--
//echo "<pre><br />";print_r($challonge);
//echo "<pre><br />";print_r($challongeTournaments);
//echo "<pre><br />";print_r($challongeTournamentMapByName);
//echo "<pre><br />";print_r($challongePending);
//echo "<pre><br />";print_r($challongeInProgress);
//echo "<pre><br />\$telegramChatId => $telegramChatId";
//echo "<pre><br />";print_r($telegram);
//echo "<pre><br />\$telegramMessage => "; print_r($telegramMessage);
//echo "<pre><br />\$telegramMessageId => $telegramMessageId ";
//echo "<pre><br />\$telegramUser => "; print_r($telegramUser);
//echo "<pre><br />\$telegramText => $telegramText";
//echo "<pre><br />\$telegramCommand => $telegramCommand";
//echo "<pre><br />";print_r($sessionData);
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
        $msg = new TextMessage($telegramChatId, $helpText);
        $msg_string = json_encode($msg);
        $debugOutput = $tg->SendMessage($msg_string);
        break;
    case "/new_popup":

        if ($telegramMessage["chat"]["type"] == "private") {
            $txt = "Popup can only be created in public chat";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            break;
        }

        $hasPopupRunning = false;
        foreach ($challongeTournaments as $t) {
            if ($telegramUser["id"] == $t["creator"]) {
                $hasPopupRunning = true;
                $txt = "You already have a popup running, please finish current one before creating new one";
                $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
                break;
            }
        }
        if ($hasPopupRunning) break;


        if (!$telegramText || isset($challongeTournamentMapByName[$telegramText])) {
            $txt = "/new_popup, please give unique name to the popup:";
            $debugOutput = $telegramAPI->SendPromptMessage($telegramChatId, $txt, $telegramMessage["message_id"]);
            break;
        }

        $creator = $telegramUser["id"];
        $max_participants = 8;
        $tournament_type = 'single';
        $description = "";
        $url = $creator . "_" . uniqid();

        $popup_params = array(
            "tournament" => array(
                "game_name" => 'Shadow Era',
                "name" => $telegramText,
                "description" => $description,
                "tournament_type" => isset($tournament_types[$tournament_type]) ? $tournament_types[$tournament_type] : $tournament_types['single'],
                "signup_cap" => $max_participants,
                "url" => $url
            )
        );
        $challonge_response = $challongeAPI->createTournament($popup_params);
        $txt = "New popup has been created, please /join_popup" .
               "\nhttp://challonge.com/$url";
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt, true, "HTML");

        break;
    case "/start_popup":
        $min_participants = 4;

        if(count($challongeTournaments) == 0) {
            $txt = "There is no popup, you can create one using /new_popup command";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);

        } elseif($telegramText == ''  //--Unspecified popup name--
            || !isset($challongeTournamentMapByName[$telegramTextLower]) //--Popup does not exists--
        ) {
            $txt = "Please choose from the list of popups to start: ";
            foreach($challongePending as &$t) $t = "/start_popup " . $t;
            $debugOutput = $telegramAPI->SendPromptWithButtonsInColumn($telegramChatId, $txt, $telegramMessageId, $challongePending);

        }elseif($challongeTournaments[$challongeTournamentMapByName[$telegramTextLower]]['participant_count'] < $min_participants) {
            $txt = "The popup must have at least $min_participants participants to start. Use /participants command to review.";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);

        }else{
            $challonge_response = $challongeAPI->getParticipants($challongeTournamentMapByName[$telegramTextLower]);
            $max_participants = $challongeTournaments[$challongeTournamentMapByName[$telegramTextLower]]['max_participants'];

            $sessionDataIndex = 'start_popup_count_' .$challongeTournamentMapByName[$telegramTextLower];
            if(!isset($sessionData[$sessionDataIndex])){
                $sessionData[$sessionDataIndex] = 0;
            }

            if($sessionData[$sessionDataIndex] < 1) {
                //--Confirm /start_popup for first run of command--
                $counter = 1;
                if($challongeAPI->hasErrors() || $challonge_response == "") {
                    //$challongeAPI->listErrors();
                    $txt = "$telegramText preview participants: ";
                }else{
                    $txt = "$telegramText preview participants: ";
                    foreach($challonge_response->participant as $participant){
                        $txt .= "\n (" . $counter . ") " . $participant->name;
                        if($counter > $max_participants) $txt .= " <i>(on waiting list)</i>";
                        $counter++;
                    }
                }

                //--Fillers--
                for($i = $counter; $i <= $max_participants; $i++){
                    $txt .= "\n (" . $i . ") <i>--</i>" ;
                }
                $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt, true, 'HTML');

                $txt = "Please confirm action to start popup. ";
                $debugOutput = $telegramAPI->SendPromptWithButtonsInColumn($telegramChatId, $txt, $telegramMessageId, array(
                    '/start_popup ' .$telegramText,
                    '/cancel'
                ));

                $sessionData[$sessionDataIndex]++;

            }else{
                //--Start now--
                $challonge_response = $challongeAPI->startTournament($challongeTournamentMapByName[$telegramTextLower]);

                if($challongeAPI->hasErrors()) {
                    $challongeAPI->listErrors(); //--error starting--
                }else{
                    $url =  $challongeTournaments[$challongeTournamentMapByName[$telegramTextLower]]['url'];
                    $txt = "Popup has now been started, GLHF to all! ".
                           "\nTo display results run /popup_results command. ".
                           "\nhttp://challonge.com/$url";
                    $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt, true, 'HTML');
                }
            }

        }

        break;
    case '/cancel':
        $txt = "Action cancelled";
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
        break;
    case "/cancel_popup":
        //TODO: implement
        break;
    case "/join_popup":
        if(count($challongePending) == 0) {
            $txt = "There is no popup, you can create one using /new_popup command";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);

        } elseif($telegramText == ''  //--Unspecified popup name--
                || !isset($challongeTournamentMapByName[$telegramTextLower]) //--Popup does not exists--
                || !isset($challongePending[$challongeTournamentMapByName[$telegramTextLower]]) //--Popup is no longer pending--
        ) {
            $txt = "Please choose from the list of popups to join: ";
            foreach($challongePending as &$t) $t = "/join_popup $t";
            $debugOutput = $telegramAPI->SendPromptWithButtonsInColumn($telegramChatId, $txt, $telegramMessageId, $challongePending);

        } else {
            $challonge_response = $challongeAPI->createParticipant($challongeTournamentMapByName[$telegramTextLower], array(
                "participant" => array("name" => $telegramUser['first_name'])
            ));

            if($challongeAPI->hasErrors()) {
                $txt = "You already joined this popup, use this command to list all /participants";
                $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            }else{
                $txt = $telegramUser['first_name']. " joined the popup $telegramText";
                $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            }
        }
        break;
    case "/quit_popup":
        //TODO: implement
        break;
    case "/kick":
        //TODO: implement
        break;
    case "/participants":
        if(count($challongeTournaments) == 0) {
            $txt = "There is no popup, you can create one using /new_popup command";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);

        } elseif($telegramText == ''  //--Unspecified popup name--
            || !isset($challongeTournamentMapByName[$telegramTextLower]) //--Popup does not exists--
        ) {
            $txt = "Please choose from the list of popups to view participants: ";
            $challongeAllTournaments = array();
            foreach($challongeTournaments as $t) $challongeAllTournaments[] = "/participants " . $t['name'];
            $debugOutput = $telegramAPI->SendPromptWithButtonsInColumn($telegramChatId, $txt, $telegramMessageId, $challongeAllTournaments);

        }else{
            $challonge_response = $challongeAPI->getParticipants($challongeTournamentMapByName[$telegramTextLower]);
            $max_participants = $challongeTournaments[$challongeTournamentMapByName[$telegramTextLower]]['max_participants'];
            $counter = 1;

            if($challongeAPI->hasErrors() || $challonge_response == "") {
                //$challongeAPI->listErrors();
                $txt = "$telegramText participants: "; //--Empty--
            }else{
                $txt = "$telegramText participants: ";
                foreach($challonge_response->participant as $participant){
                    $txt .= "\n (" . $counter . ") " . $participant->name;
                    if($counter > $max_participants) $txt .= " <i>(on waiting list)</i>";
                    $counter++;
                }
            }

            //--Fillers--
            for($i = $counter; $i <= $max_participants; $i++){
                $txt .= "\n (" . $i . ") <i>--</i>" ;
            }

            //--Post message--
            if($counter > $max_participants) {
                $txt .= "\n You can still /join_popup waiting list";
            }else {
                $txt .= "\n Please /join_popup $telegramText";
            }
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt, true, 'HTML');
        }


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
    case "/popups":
        //TODO: implement
        break;
}

$_SESSION["SessionData"] = serialize($sessionData);

header('Content-Type: application/json');
echo $debugOutput;