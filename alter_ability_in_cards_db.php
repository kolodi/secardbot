<?php

include "db.php";
$db = new DB();
$pdo = $db->pdo;

// $sth = $dbh->prepare("SELECT * FROM cards");
// $sth->execute();

// print("Fetch all of the remaining rows in the result set:\n");
// $result = $sth->fetchAll();
// print_r($result);

$allCardsJson = file_get_contents("secards.317m.json");
$cards = json_decode($allCardsJson, true);

$sql = "UPDATE cards SET ability = ? WHERE id = ?";

$stmt = $pdo->prepare($sql);

foreach($cards as $card)
{
    $row = array(
        $card["ability"],
        $card["id"]
    );
    $stmt->execute($row);
}