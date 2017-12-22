<?php

include "db.php";
$db = new DB();
$pdo = $db->pdo;

// $sth = $dbh->prepare("SELECT * FROM cards");
// $sth->execute();

// print("Fetch all of the remaining rows in the result set:\n");
// $result = $sth->fetchAll();
// print_r($result);

$allCardsJson = file_get_contents("secards.352m.json");
$cards = json_decode($allCardsJson, true);

$sql = "INSERT INTO `cards` (id, name, cards_set, imageUrl, faction, classes, type, subtype, cost, attack, attacktype, health, ability, is_unique, rarity, buyprice) \n"
. "VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

$stmt = $pdo->prepare($sql);

foreach($cards as $card)
{
    $row = array(
        $card["id"],
        $card["name"],
        $card["set"],
        $card["imageUrl"],
        $card["faction"],
        $card["classes"],
        $card["type"],
        $card["subtype"],
        intval($card["cost"]),
        intval($card["attack"]),
        $card["attacktype"],
        intval($card["health"]),
        $card["ability"],
        intval($card["unique"]),
        $card["rarity"],
        intval($card["buyprice"])
    );
    $stmt->execute($row);
}