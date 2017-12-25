<?php

session_id("test-session");
session_start();

include "challonge/challonge.class.php";

$c = new ChallongeAPI("iWTgKx1WNQ48AJ77JMZNSHHfiil64WA7tMCsb0oC");
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

$params = array( 
    //"state" => "all"
    "state" => "pending"
    //"state" => "in_progress"
    //"state" => "ended"
);

$t = $c->getTournaments($params);

if ($t == false) {
    foreach ($c->errors as $error) {
      echo $error."\n"; // Output the error message
    }
  }

//$json = json_encode($t);
//echo $json;
//echo $t["tournament"]
//var_dump($t);
if($t != false && $t->Count() > 0) 
{
    echo $t->tournament[0]->id ;
    
    print_r($t->asXML());
}

