<?php
include "lib.php";
include "tournaments.php";
include "challonge/challonge.class.php";

/*****************************************
 * CONFIGURATION
 *****************************************/
$telegram_token = "494619184:AAGgqciTKBa4nIs2QmpxX4ZXdqTJp8EmTdQ";
$challonge_token = "i1Sax3ehsAUmFiq1N4gvuxElYpnqGAzCzKqAppMt"; //Jeff
//$challonge_token = "iWTgKx1WNQ48AJ77JMZNSHHfiil64WA7tMCsb0oC"; //Kolodi
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
 * VALIDATE INPUT
 *****************************************/
$telegramAPI = new TG($telegram_token);
$telegram = json_decode($telegramAPI->GetLastUpdate(), true);
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
$telegramUserId = $telegramUser["id"];
$telegramChatId = $telegramMessage["chat"]["id"];
$telegramMessageId = $telegramMessage['message_id'];
$isPrivateChat = $telegramMessage["chat"]["type"] == "private";
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
$telegramTextLowerTrimmed = trim(strtolower($telegramText));
if (!$telegramCommand || !in_array($telegramCommand, $commands));
    //die("No command");


/*****************************************
 * RESOURCES
 *****************************************/

$challongeAPI = new ChallongeAPI($challonge_token);
$challongeTournaments = $challongeAPI->GetTournamentsJSON(array(
    "state" => "all"
));
$challongePending = array();
$challongeInProgress = array();
$challongeTournamentMapByName = array();
foreach($challongeTournaments as $index => &$tournament){
    $challongeTournamentMapByName[trim(strtolower($tournament['name']))] = $index;
    $creator = explode("_", $tournament['url']);
    $tournament['creator'] = isset($creator[0]) ? $creator[0] : '';
    switch ($tournament['state']) {
        case "pending":
            $challongePending[] = $index;
            break;
        case "in_progress":
            $challongeInProgress[] = $index;
            break;
    }
}
session_id("popup");
session_start();
$sessionData = array();
if (isset($_SESSION["SessionData"])) {
    $sessionData = unserialize($_SESSION["SessionData"]);
}


/*****************************************
 * DEBUG
 *****************************************/
//echo "<pre><br />\$telegramChatId => $telegramChatId";
//echo "<pre><br />";print_r($telegram);
//echo "<pre><br />\$telegramMessage => "; print_r($telegramMessage);
//echo "<pre><br />\$telegramMessageId => $telegramMessageId ";
//echo "<pre><br />\$telegramUser => "; print_r($telegramUser);
//echo "<pre><br />\$telegramText => $telegramText";
//echo "<pre><br />\$telegramCommand => $telegramCommand";
//echo "<pre><br />";print_r($challongeTournamentMapByName);    //array['lower_tournament_name' => index]
//echo "<pre><br />";print_r($challongeTournaments);            //array('index' => tournaments)
//echo "<pre><br />";print_r($challongePending);                //array('index')
//echo "<pre><br />";print_r($challongeInProgress);             //array('index')
//echo "<pre><br />";print_r($sessionData);
//die;
/*****************************************
 * Command Definition
 *****************************************/

$debugOutput = "";
switch ($telegramCommand) {
    case "/help":
        $txt = file_get_contents("popup_help.txt");
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt, true, 'HTML');

        break;

    case "/new_popup":
        // tournament can be created only in public room,
        // maybe even only in specific chat id of OOPS room
        if ($isPrivateChat) {
            $txt = "Popup can only be created in public chat";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            break;
        }
        
        foreach ($challongePending as $i) {
            $t = $challongeTournaments[$i];
            if (isset($t['creator']) && $telegramUserId == $t['creator']) {
                $txt = "You already have pending popup, please start or cancel it before creating new one";
                $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
                break 2;
            }
        }

        if (!$telegramText
            || isset($challongeTournamentMapByName[$telegramTextLowerTrimmed])) //--Unique name needed--
        {
            $txt = "/new_popup, please give unique name to the popup:";
            $debugOutput = $telegramAPI->SendPromptMessage($telegramChatId, $txt, $telegramMessageId);
            break;
        }

        $creator = $telegramUserId;
        $max_participants = 8;
        $tournament_type = 'single';
        $description = "";
        $url = $creator . "_" . uniqid();

        $popup_params = array(
            "tournament" => array(
                "game_name" => 'Shadow Era',
                "name" => trim($telegramText),
                "description" => $description,
                "tournament_type" => isset($tournament_types[$tournament_type]) ? $tournament_types[$tournament_type] : $tournament_types['single'],
                //"signup_cap" => $max_participants,
                "url" => $url
            )
        );
        $challonge_response = $challongeAPI->createTournament($popup_params);
        $txt = "New popup has been created, please /join_popup ".
               "\n<a href='http://challonge.com/$url'>$telegramText - Challonge</a>";
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt, false, 'HTML');

        break;

    case "/join_popup":
        if(count($challongePending) == 0) {
            $txt = "There is no popup, you can create one using /new_popup command";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            break;
        }
        if (count($challongePending) > 1
            || !isset($challongeTournamentMapByName[$telegramTextLowerTrimmed])) {
            // we are here because there are more than 1 popup and user did not specified correctly (or at all) the popup name
            $txt = "Please choose from the list of popups to join: ";
            $buttons = array();
            foreach ($challongePending as $i) {
                $buttons[] = "/join_popup " . $challongeTournaments[$i]["name"];
            }
            $debugOutput = $telegramAPI->SendPromptWithButtonsInColumn($telegramChatId, $txt, $telegramMessageId, $buttons);
            break;
        }
        // here we have only one pending popup
        $popup = $challongeTournaments[$challongePending[0]];
        $tournament_id = $popup['id'];
        $challonge_response = $challongeAPI->createParticipant($tournament_id, array(
            "participant" => array(
                "name" => $telegramUser['first_name']
            )
        ));

        if ($challongeAPI->hasErrors()) {
            $txt = "You already joined this popup, use this command to list all /participants";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
        } else {
            $txt = $telegramUser['first_name'] . " joined the popup $telegramText";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
        }

        break;

    case "/participants":
        if(count($challongeTournaments) == 0) {
            $txt = "There is no popup, you can create one using /new_popup command";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);

        } elseif($telegramText == ''  //--Unspecified popup name--
            || !isset($challongeTournamentMapByName[$telegramTextLowerTrimmed]) //--Popup does not exists--
        ) {
            $txt = "Please choose from the list of popups to view participants: ";
            $buttons = array();
            foreach ($challongeTournaments as $t) {
                $buttons[] = "/participants " . $t["name"];
            }

            $debugOutput = $telegramAPI->SendPromptWithButtonsInColumn($telegramChatId, $txt, $telegramMessageId, $buttons);
        }else{
            $tournament_id = $challongeTournaments[$challongeTournamentMapByName[$telegramTextLowerTrimmed]]['id'];
            $participants = $challongeAPI->GetParticipantsJSON($tournament_id);
            $max_participants = $challongeTournaments[$challongeTournamentMapByName[$telegramTextLowerTrimmed]]['signup_cap'];

            $counter = 1;
            $txt = "$telegramText participants: ";
            foreach($participants as $participant) {
                $txt .= "\n (" . $counter . ") " . $participant['name'];
                if($counter > $max_participants) $txt .= " <i>(on waiting list)</i>";
                $counter++;
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
    case "/start_popup":
        $min_participants = 4;

        if(count($challongeTournaments) == 0) {
            $txt = "There is no popup, you can create one using /new_popup command";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);

        } elseif($telegramText == ''  //--Unspecified popup name--
            || !isset($challongeTournamentMapByName[$telegramTextLowerTrimmed]) //--Popup does not exists--
        ) {
            $txt = "Please choose from the list of popups to start: ";
            $buttons = array();
            foreach ($challongePending as $i) {
                $buttons[] = "/start_popup " . $challongeTournaments[$i]["name"];
            }
            $debugOutput = $telegramAPI->SendPromptWithButtonsInColumn($telegramChatId, $txt, $telegramMessageId, $buttons);

        }elseif($challongeTournaments[$challongeTournamentMapByName[$telegramTextLowerTrimmed]]['participants_count'] < $min_participants) {
            $txt = "The popup must have at least $min_participants participants to start. Use /participants command to review.";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);

        }else{
            $tournament_id = $challongeTournaments[$challongeTournamentMapByName[$telegramTextLowerTrimmed]]['id'];
            $participants = $challongeAPI->GetParticipantsJSON($tournament_id);
            $max_participants = $challongeTournaments[$challongeTournamentMapByName[$telegramTextLowerTrimmed]]['signup_cap'];

            $sessionDataIndex = 'start_popup_count_' .$tournament_id;
            if(!isset($sessionData[$sessionDataIndex])) $sessionData[$sessionDataIndex] = 0;

            if($sessionData[$sessionDataIndex] < 1) {
                //--Confirm /start_popup for first run of command--
                $counter = 1;
                $txt = "$telegramText participants: ";
                foreach($participants as $participant) {
                    $txt .= "\n (" . $counter . ") " . $participant['name'];
                    if($counter > $max_participants) $txt .= " <i>(on waiting list)</i>";
                    $counter++;
                }
                //--Fillers--
                for($i = $counter; $i <= $max_participants; $i++){
                    $txt .= "\n (" . $i . ") <i>--</i>" ;
                }
                $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt, true, 'HTML');
                $txt = "Please confirm action to start popup. ";
                $debugOutput = $telegramAPI->SendPromptWithButtonsInColumn($telegramChatId, $txt, $telegramMessageId, array(
                    '/start_popup ' . $telegramText,
                    '/cancel'
                ));
                $sessionData[$sessionDataIndex]++;

            }else{
                //--Start now--
                $challonge_response = $challongeAPI->startTournament($tournament_id);

                if($challongeAPI->hasErrors()) {
                    $challongeAPI->listErrors(); //--error starting--
                }else{
                    $url =  $challongeTournaments[$challongeTournamentMapByName[$telegramTextLowerTrimmed]]['url'];
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
        foreach ($challongePending as $i) {
            $t = $challongeTournaments[$i];
            if (isset($t['creator']) && $telegramUserId == $t['creator']) {
                if ($telegramTextLowerTrimmed == 'delete') {

                    $challonge_response = $challongeAPI->deleteTournament($t['id']);
                    if ($challongeAPI->hasErrors()) {
                        $challongeAPI->listErrors(); //--error starting--
                        $txt = "Server Error";
                        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
                    } else {
                        $popupName = $t['name'];
                        $txt = "Popup $popupName has been cancelled and deleted";
                        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
                    }
                } else {
                    $popupName = $t['name'];
                    $txt = "/cancel_popup. Are you sure to cancel $popupName, please type \"DELETE\" to confirm";
                    $debugOutput = $telegramAPI->SendPromptMessage($telegramChatId, $txt, $telegramMessageId);
                }
                break 2;
            }
        }
        $txt = "You have no pending popups";
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
        break;
    case "/quit_popup":
        //TODO: implement
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, 'Undefined');
        break;
    case "/kick":
        //TODO: implement
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, 'Undefined');
        break;
    case "/opponent":
        //TODO: implement
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, 'Undefined');
        break;
    case "/popup_results":
        //TODO: implement
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, 'Undefined');
        break;
    case "/report_score":
        //TODO: implement
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, 'Undefined');
        break;
    case "/confirm_score":
        //TODO: implement
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, 'Undefined');
        break;
    case "/start":
        //TODO: implement
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, 'Undefined');
        break;
    case "/popups":
        //TODO: implement
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, 'Undefined');
        break;
    default:
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, 'Undefined');
}

$_SESSION["SessionData"] = serialize($sessionData);

header('Content-Type: application/json');
echo $debugOutput;