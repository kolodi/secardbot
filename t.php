<?php

$json = file_get_contents("secards.317m.json");
//echo $cardsJson;
$cards = json_decode($json, true);
//var_dump($cards);
$heroesCards = array_filter($cards, function($c) {
    return $c["type"] == "Hero";
});
var_dump($heroesCards);