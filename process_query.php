<?php

function CreatePhotoResultWithCard($card, $q, $foil) {
    $photoResult = new PhotoResult();
    $f = "";
    if($foil) $f  ="f";
    $photoResult->photo_url = "http://www.shadowera.com/cards/" . $card["id"] . $f . ".jpg";
    $photoResult->thumb_url = "http://www.shadowera.com/cards/" . $card["id"] . $f . ".jpg";
    $photoResult->title = $card["name"];
    $photoResult->caption = $q;
    return $photoResult;
}

function GetInlineAnswer($q, $db)
{
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
            $response->AddResult(CreatePhotoResultWithCard($card, $q, false));
        }

        // break here, return single random card
        $response->cache_time = 0;
        return $response;
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

    $cardClasses = array(
        "Warrior",
        "Hunter",
        "Mage",
        "Priest",
        "Rogue",
        "Wulven",
        "Elemental"
    );

    // prepare input array words (add capitalization)
    $capWords = array_map(function ($w) {
        return ucfirst($w);
    }, $queryWords);


    // foiled version
    $foilIndex = array_search("Foil", $capWords);
    $foiled = false;
    if($foilIndex > 0) {
        array_splice($capWords, $foilIndex, 1);
        $foiled = true;
    }

    $filtersFound = false;
    $sql = "SELECT cards.* FROM cards\n"
        . "WHERE ";

    $classFiletrs = array_intersect($cardClasses, $capWords);
    if (count($classFiletrs)) {
        $sql = "SELECT cards.* FROM cards_classes\n"
            . "LEFT JOIN cards ON cards_classes.card_id = cards.id\n"
            . "LEFT JOIN classes ON cards_classes.class_id = classes.id \n"
            . "WHERE ";
        $filtersFound = true;

        $quoted = array_map(function ($t) {
            return "'$t'";
        }, $classFiletrs);

        $sql .= "classes.name = (" . implode(" OR ", $quoted) . ")";

        //remove classes keywords
        $capWords = array_diff($capWords, $classFiletrs);
    }

    $typeFiletrs = array_intersect($cardTypes, $capWords);
    if (count($typeFiletrs)) {
        if ($filtersFound) $sql .= " AND ";
        $filtersFound = true;

        $quoted = array_map(function ($t) {
            return "'$t'";
        }, $typeFiletrs);
        $sql .= "cards.type = (" . implode(" OR ", $quoted) . ")";

        //remove types keywords
        $capWords = array_diff($capWords, $typeFiletrs);
    }
    $subtypeFilters = array_intersect($cardSubtypes, $capWords);
    if (count($subtypeFilters)) {
        if ($filtersFound) $sql .= " AND ";
        $filtersFound = true;

        $quoted = array_map(function ($t) {
            return "'$t'";
        }, $subtypeFilters);
        $sql .= "cards.subtype = (" . implode(" OR ", $quoted) . ")";

        // remove subtypes keywords
        $capWords = array_diff($capWords, $subtypeFilters);
    }
    $sql .= " LIMIT 50";

    if ($filtersFound) {
        // get cards by filters if at least one filter was found in query words
        $cards = $db->GetManyWithSQL($sql);
        if ($cards) {
            foreach ($cards as $card) {
                $response->AddResult(CreatePhotoResultWithCard($card, $q, $foiled));
            }
            // stop here, return filtered result
            return $response;
        }
    }

    
    

    // get by nickname
    $q = implode(" ", $capWords);
    $sql = "SELECT * FROM `cards` WHERE nick = '$q'";
    $card = $db->GetSingleWithSQL($sql);
    if ($card)
    {
        $response->AddResult(CreatePhotoResultWithCard($card, $q, $foiled));
    }
    else
        {
        // search by name
        $sql = "SELECT * FROM `cards` WHERE name LIKE '%$q%' LIMIT 50";
        $cards = $db->GetManyWithSQL($sql);
        if ($cards) {
            foreach ($cards as $card) {
                $response->AddResult(CreatePhotoResultWithCard($card, $q, $foiled));
            }
        }
    }

    // return filtered by name or single by nickname here
    return $response;
}