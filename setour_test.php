<?php
include "lib.php";
include "tournaments.php";
include "challonge/challonge.class.php";

/*****************************************
 * CONFIGURATION
 *****************************************/
$telegram_token = "494619184:AAGgqciTKBa4nIs2QmpxX4ZXdqTJp8EmTdQ";
//$challonge_token = "i1Sax3ehsAUmFiq1N4gvuxElYpnqGAzCzKqAppMt"; //Jeff
$challonge_token = "iWTgKx1WNQ48AJ77JMZNSHHfiil64WA7tMCsb0oC"; //Kolodi
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

        // init challonge
        $challongeAPI = new ChallongeAPI($challonge_token);
        // get all tournaments
        // TODO: get tournamnets for only last 24 hours
        $challongeAPI->GetTournamentsJSON();

        // filter for only pending tournaments created by user
        $userPendingTournaments = $challongeAPI->FilterTournamnets(array(
            "creator" => $telegramUserId,
            "state" => "pending"
        ));

        if(count($userPendingTournaments) == 1) {
            $popupName = $userPendingTournaments[0]["name"];
            $txt = "You already have pending popup: $popupName, plese /start_popup or cancel before creating new one";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            break;
        }
        if(count($userPendingTournaments) > 1) {
            //This is weird case, user can not have more than 1 pending popup
            $txt = "Something gone wrong, multiple pending popups";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            break;
        }

        if($telegramTextLowerTrimmed == "") {
            $txt = "/new_popup, please specify popup name:";
            $debugOutput = $telegramAPI->SendPromptMessage($telegramChatId, $txt, $telegramMessageId);
            break;
        }

        // Here we have 0 pending popupos for the user, so we can proceed for creating new one

        

        $url = $telegramUserId . "_" . uniqid();
        $popupName = trim($telegramText);
        $popup_params = array(
            "tournament" => array(
                "game_name" => 'Shadow Era',
                "name" => $popupName,
                "description" => "",
                "tournament_type" => "single elimination",
                "url" => $url
            )
        );

        $challonge_response = $challongeAPI->createTournament($popup_params);
        if ($challongeAPI->hasErrors()) {
            $challongeAPI->listErrors(); //--error starting--
            $txt = "Server Error when trying to create popup";
            $debugOutput = $telegramAPI->SendReplyMessage($telegramChatId, $txt, $telegramMessageId);
            break;
        }

        // here we are free of errors

        $txt = "Popup $popupName has been created ".
            "\nplese click on /join_popup to joint it";
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt, true, 'HTML');

        break;

    case "/join_popup":

        // tournament can be created only in public room,
        // maybe even only in specific chat id of OOPS room
        if ($isPrivateChat) {
            $txt = "You can join a popup only in public chat";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            break;
        }

        // init challonge
        $challongeAPI = new ChallongeAPI($challonge_token);
        // get all tournaments
        // TODO: get tournamnets for only last 24 hours
        $challongeAPI->GetTournamentsJSON();

        // filter for only pending tournaments created by user
        $pendingTournaments = $challongeAPI->FilterTournamnets(array(
            "state" => "pending"
        ));
        if(count($pendingTournaments) == 0) {
            $txt = "There is no pending popup, you can create one using /new_popup command";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            break;
        }

        
        $popup = $pendingTournaments[0];
        if(count($pendingTournaments) > 1) {
            $foundByName = false;
            if($telegramTextLowerTrimmed != "") {
                $t = $challongeAPI->GetTournamentByName($telegramTextLowerTrimmed, $pendingTournaments);
                if($t)  {
                    $popup = $t;
                    $foundByName = true;
                }
            }
            if($foundByName == false) {
                $txt = "Please choose from the list of popups to join: ";
                $buttons = array();
                foreach ($pendingTournaments as $t) {
                    $buttons[] = "/join_popup " . $t["name"];
                }
                $debugOutput = $telegramAPI->SendPromptWithButtonsInColumn($telegramChatId, $txt, $telegramMessageId, $buttons);
                break;
            }
        }

        // check if user is already in participant list
        $participants = $challongeAPI->GetParticipantsJSON($popup["id"]);
        foreach($participants as $p) {
            if($p["name"] == $telegramUser['first_name']) {
                $txt = "You have already joined this popup";
                $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
                break 2;
            }
        }

        // Here we have valid $popup to join
        $challonge_response = $challongeAPI->createParticipant($popup["id"], array(
            "participant" => array(
                "name" => $telegramUser['first_name']
            )
        ));
        if ($challongeAPI->hasErrors()) {
            $txt = "Server Error";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
        } else {
            $txt = $telegramUser['first_name'] . " joined the popup " . $popup["name"];
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
        }

        break;

    case "/participants":

        // init challonge
        $challongeAPI = new ChallongeAPI($challonge_token);
        // get all tournaments
        // TODO: get tournamnets for only last 24 hours
        $challongeAPI->GetTournamentsJSON();

        $challongeTournaments = $challongeAPI->lastTournaments;

        if(count($challongeTournaments) == 0) {
            $txt = "There is no popups, you can create one using /new_popup command";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            break;
        }

        $popup = $challongeTournaments[0];
        if(count($challongeTournaments) > 1) {
            $foundByName = false;
            if($telegramTextLowerTrimmed != "") {
                $t = $challongeAPI->GetTournamentByName($telegramTextLowerTrimmed);
                if($t)  {
                    $popup = $t;
                    $foundByName = true;
                }
            }
            if($foundByName == false) {
                $txt = "Please choose from the list of tournamnets to display /participants: ";
                $buttons = array();
                foreach ($challongeTournaments as $t) {
                    $buttons[] = "/participants " . $t["name"];
                }
                $debugOutput = $telegramAPI->SendPromptWithButtonsInColumn($telegramChatId, $txt, $telegramMessageId, $buttons);
                break;
            }
        }

        // Here we have 1 valid popup

        $participants = $challongeAPI->GetParticipantsJSON($popup["id"]);
        if ($challongeAPI->hasErrors()) {
            $txt = "Server error";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            break;
        }

        if(count($participants) == 0) {
            $txt = "$telegramText has no participants at the moment";
            $txt .= "\n Please /join_popup $telegramText";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt, true, 'HTML');
        }else{
            $counter = 1;
            $txt = "$telegramText participants: ";
            foreach($participants as $participant) {
                $txt .= "\n (" . $counter . ") " . $participant['name'];
                $counter++;
            }
            $txt .= "\n Please /join_popup $telegramText";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt, true, 'HTML');
        }
        
        break;

    case "/start_popup":
        $min_participants = 4;

        // init challonge
        $challongeAPI = new ChallongeAPI($challonge_token);
        // get all tournaments
        // TODO: get tournamnets for only last 24 hours
        $challongeAPI->GetTournamentsJSON();

        // filter for only pending tournaments created by user
        $userPendingTournaments = $challongeAPI->FilterTournamnets(array(
            "creator" => $telegramUserId,
            "state" => "pending"
        ));

        if(count($userPendingTournaments) == 0) {
            $txt = "There is no popup, you can create one using /new_popup command";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            break;
        }
        if(count($userPendingTournaments) > 1) {
            //This is weird case, user can not have more than 1 pending popup
            $txt = "Something gone wrong, multiple pending popups";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            break;
        }

        // here we have exactly 1 pending popup for the user
        $popup = $userPendingTournaments[0];

        if($popup["participants_count"] < $min_participants) {
            $txt = "The popup must have at least $min_participants participants to start. Use /participants command to review.";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            break;
        }
        
        $participantCount = $popup["participants_count"];
        if($participantCount < 8 && $telegramTextLowerTrimmed != "start") {
            $txt = "There are only $participantCount participants, you may want to wait till 8 or type \"start\" to confirm /start_popup";
            $telegramAPI->SendPromptMessage($telegramChatId, $txt, $telegramUserId);
            break;
        }
        
        // Here we have 8 or more participants or user typed "start" to force start with less than 8 participants
        $challonge_response = $challongeAPI->startTournament($popup['id']);

        if ($challongeAPI->hasErrors()) {
            $challongeAPI->listErrors(); //--error starting--
            $txt = "Server Error when trying to start popup";
            $debugOutput = $telegramAPI->SendReplyMessage($telegramChatId, $txt, $telegramMessageId);
            break;
        }

        // here we are free of errors

        $url = $popup['url'];
        $txt = "Popup has now been started, GLHF to all! " .
            "\nTo display results run /popup_results command. " .
            "\nhttp://challonge.com/$url";
        $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt, true, 'HTML');

        break;

    case "/cancel_popup":

         // init challonge
         $challongeAPI = new ChallongeAPI($challonge_token);
         // get all tournaments
         // TODO: get tournamnets for only last 24 hours
         $challongeAPI->GetTournamentsJSON();
 
         // filter for only pending tournaments created by user
         $userPendingTournaments = $challongeAPI->FilterTournamnets(array(
             "creator" => $telegramUserId,
             "state" => "pending"
         ));

        if(count($userPendingTournaments) == 0) {
            $txt = "There is no popup to cancel, you can create one using /new_popup command";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            break;
        }

        if(count($userPendingTournaments) > 1) {
            //This is weird case, user can not have more than 1 pending popup
            $txt = "Something gone wrong, multiple pending popups";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            break;
        }

        // here we have exactly 1 pending popup for the user
        $popup = $userPendingTournaments[0];
        $popupName = $popup["name"];
        if($telegramTextLowerTrimmed != "cancel") {
            $txt = "/cancel_popup. Are you sure to cancel $popupName, please type \"CANCEL\" to confirm";
            $debugOutput = $telegramAPI->SendPromptMessage($telegramChatId, $txt, $telegramMessageId);
            break;
        }

        // Here usere typed "cancel";
        $challonge_response = $challongeAPI->deleteTournament($popup['id']);
        if ($challongeAPI->hasErrors()) {
            $challongeAPI->listErrors(); //--error starting--
            $txt = "Server Error";
            $debugOutput = $telegramAPI->SendSimpleMessage($telegramChatId, $txt);
            break;
        } 

        // Here we have successfully deleted the tournamnet
        
        $txt = "Popup $popupName has been cancelled and deleted";
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


header('Content-Type: application/json');
echo $debugOutput;