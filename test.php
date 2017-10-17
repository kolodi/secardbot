<?php
define("MIN_QUERY_LENGTH", 2);
define("MAX_QUERY_LENGTH", 50);

function ExitWithResult($r)
{
    header('Content-Type: application/json');
    exit(json_encode($r, JSON_PRETTY_PRINT));
}


$q = strtolower($_GET["q"]);
$queryLenght = strlen($q);
if ($queryLenght < MIN_QUERY_LENGTH) {
    Exit("query too short");
}
if ($queryLenght > MAX_QUERY_LENGTH) {
    Exit("query too long");
}

include "lib.php";
include "db.php";
$db = new DB();
$tg = new TG("fake_token", "inline");
$response = new InlineQueryAnswer();

$queryWords = explode(" ", $q); // array of query words (ex.: random ally)

// check if is random query
if (in_array("random", $queryWords)) {
    $sql = "SELECT * FROM `cards` ";
    // type of randomness
    if (count($queryWords) == 1 || in_array("card", $queryWords)) {
        // random card

    }
    elseif (in_array("hero", $queryWords)) {
        $sql .= "WHERE type = 'Hero' ";
    }
    elseif (in_array("ally", $queryWords)) {
        $sql .= "WHERE type = 'Ally' ";
    }
    elseif (in_array("item", $queryWords)) {
        $sql .= "WHERE type = 'Item' ";
    }
    elseif (in_array("ability", $queryWords)) {
        $sql .= "WHERE type = 'Ability' ";
    }
    elseif (in_array("location", $queryWords)) {
        $sql .= "WHERE type = 'Location' ";
    }
    elseif (in_array("weapon", $queryWords)) {
        $sql .= "WHERE subtype = 'Weapon' ";
    }
    elseif (in_array("armor", $queryWords)) {
        $sql .= "WHERE subtype = 'Armor' ";
    }

    $sql .= "ORDER BY RAND() LIMIT 1";

    $card = $db->GetSingleWithSQL($sql);
    if ($card) {
        $photoResult = new PhotoResult();
        $photoResult->photo_url = "http://www.shadowera.com/cards/" . $card["imageUrl"];
        $photoResult->title = $card["name"];
        $response->AddResult($photoResult);
    }
    ExitWithResult($response);
}

$cardTypes = array(
    "Hero",
    "Ally",
    "Item",
    "Ability",
    "Location"
);
$cardSubtypes = array(
    "Wild",
    "Outlaw",
    "Twilight",
    "Homunculus",
    "Templar",
    "Ravager",
    "Wulven",
    "Aldmor",
    "Undead",
    "Attachment",
    "Support",
    "Artifact",
    "Yari",
    "Trap",
    "Armor",
    "Weapon",
    "Balor",
    "Scheuth",
    "Thriss",
    "Tinnal",
    "Ogmaga"
);

// prepare input array words (add capitalization)
$capWords = array_map(function ($w) {
    return ucfirst($w);
}, $queryWords);
$filtersFound = false;

$sql = "SELECT * FROM `cards` WHERE ";

$typeFiletrs = array_intersect($cardTypes, $capWords);
if (count($typeFiletrs)) {
    $filtersFound = true;

    $quoted = array_map(function ($t) {
        return "'$t'";
    }, $typeFiletrs);
    $sql .= "type = (" . implode(" OR ", $quoted) . ")";

    //remove keywords
    $capWords = array_diff($capWords, $typeFiletrs);
}
$subtypeFilters = array_intersect($cardSubtypes, $capWords);
if (count($subtypeFilters)) {
    if ($filtersFound) $sql .= " AND ";
    $filtersFound = true;

    $quoted = array_map(function ($t) {
        return "'$t'";
    }, $subtypeFilters);
    $sql .= "subtype = (" . implode(" OR ", $quoted) . ")";

    // remove keywords
    $capWords = array_diff($capWords, $subtypeFilters);
}
$sql .= " LIMIT 50";

if ($filtersFound) {
    // get cards by filters if at least one filter was found in query words
    $cards = $db->GetManyWithSQL($sql);
    if ($cards) {
        foreach ($cards as $card) {
            $photoResult = new PhotoResult();
            $photoResult->photo_url = "http://www.shadowera.com/cards/" . $card["imageUrl"];
            $photoResult->title = $card["name"];
            $response->AddResult($photoResult);
        }
        // stop here
        ExitWithResult($response);
    }
}

// get by nickname
$sql = "SELECT * FROM `cards` WHERE nick = '$q'";
$card = $db->GetSingleWithSQL($sql);
if ($card)
    {
    $photoResult = new PhotoResult();
    $photoResult->photo_url = "http://www.shadowera.com/cards/" . $card["imageUrl"];
    $photoResult->title = $card["name"];
    $response->AddResult($photoResult);
}
else
    {
    // search by name
    $sql = "SELECT * FROM `cards` WHERE name LIKE '%$q%' LIMIT 50";
    $cards = $db->GetManyWithSQL($sql);
    if ($cards) {
        foreach ($cards as $card) {
            $photoResult = new PhotoResult();
            $photoResult->photo_url = "http://www.shadowera.com/cards/" . $card["imageUrl"];
            $photoResult->title = $card["name"];
            $response->AddResult($photoResult);
        }
    }
}

ExitWithResult($response);