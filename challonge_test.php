<?php

session_id("test-session");
session_start();

include "challonge/challonge.class.php";

//$c = new ChallongeAPI("iWTgKx1WNQ48AJ77JMZNSHHfiil64WA7tMCsb0oC"); //Kolodi
$c = new ChallongeAPI("i1Sax3ehsAUmFiq1N4gvuxElYpnqGAzCzKqAppMt"); //Jeff
//$c->verify_ssl = false;

include "tournaments.php";
if(isset($_SESSION["popup"])) 
    $popup = unserialize($_SESSION["popup"]);
else
{
    $popup = new Popup();
    $popup->id = "1249844";
    $_SESSION["popup"] = serialize($popup);
}

/***************************************************
 * Create Tournaments
 ***************************************************/
echo "<pre><h1>Create Tournament</h1>";
$tournament_types = array(
    "single elimination", "double elimination", "round robin", "swiss"
);

//--POPUP Properties--
$max_participants = 8;
$tournament_type = 0;
$description = "A popup tournament description here";
$tournament_start = date("Y-m-d H:i");
$url = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", 8)), 0, 8);
$popup_params = array(
    "tournament" => array(
         "name" => "Tournament " . date("YmdHis") . rand(1000,9999)
        ,"description" => $description
        ,"tournament_type" => isset($tournament_types[$tournament_type]) ? $tournament_types[$tournament_type] : $tournament_types[0]
        ,"signup_cap" => $max_participants
       // ,"url" => $url
    )
);
if($tournament_start != ""){
    $popup_params["tournament"]["start_at"] = $tournament_start;
}

//--Major Tournament Properties--
$max_participants = 10;
$tournament_type = 1;
$description = "A major tournament description here";
$tournament_start = date("Y-m-d H:i");
$url = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", 8)), 0, 8);
$major_params = array(
    "tournament" => array(
        "name" => "Tournament " . date("YmdHis") . rand(1000,9999)
        ,"description" => $description
        ,"tournament_type" => isset($tournament_types[$tournament_type]) ? $tournament_types[$tournament_type] : $tournament_types[0]
        ,"signup_cap" => $max_participants
        ,"url" => $url
    )
);
if($tournament_start != ""){
    $major_params["tournament"]["start_at"] = $tournament_start;
}

$response = $c->createTournament($popup_params);
//$response = $c->createTournament($major_params);
//--Response: Create Tournament--
if($c->hasErrors()) {
    $c->listErrors();
}else{
    echo "<pre>"; print_r($response);
}

/***************************************************
 * Add Participants
 ***************************************************/
echo "<pre><h1>Add Participants</h1>";
echo "TO Test";

/***************************************************
 * Cancel Tournament
 ***************************************************/
echo "<pre><h1>Cancel Tournament</h1>";
echo "TO Test";

/***************************************************
 * Retrieve Tournaments
 ***************************************************/
echo "<pre><h1>Retrieve Tournaments</h1>";
$params = array( 
    //"state" => "all"
    "state" => "pending"
    //"state" => "in_progress"
    //"state" => "ended"
);

$tournaments = $c->getTournaments($params);
//--Response: Create Tournament--
if($c->hasErrors()) {
    $c->listErrors();
}else{
    foreach($tournaments as $tournament){
        //echo "<pre>--";print_r($tournament); //Print all response
        echo "<pre><br />" . $tournament->id . " " . $tournament->name . " [" . ucfirst($tournament->{'tournament-type'}) .  "]". "[" . $tournament->state . "][<a href='http://challonge.com/{$tournament->url}'>Challonge LINK</a>] " ;
    }
}


//$json = json_encode($tournaments);
//echo $json;
//echo $tournaments["tournament"]
//var_dump($t);
//echo "<pre>";print_r($tournaments);

/*
if($tournaments != false && $tournaments->Count() > 0)
{
    echo $tournaments->tournament[0]->id ;
    print_r($tournaments->asXML());
}
*/

