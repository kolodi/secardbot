<?php
define("BOT_TOKEN", "427968656:AAFpi4bosqiIwjNdajtf7AoDsB-NHKdLkqs");
define("MIN_QUERY_LENGTH", 3);
define("MAX_QUERY_LENGTH", 50);
$content = file_get_contents("php://input");

$update = json_decode($content, true);
if(!$update)
{
  exit;
}

$q = "";

$inlineQuery = isset($update['inline_query']) ? $update['inline_query'] : "";
if($inlineQuery) {
    $inlineQueryId = isset($inlineQuery['id']) ? $inlineQuery['id'] : "";
    $q = isset($inlineQuery['query']) ? strtolower($inlineQuery['query']) : "";
    $inputQueryLenght = strlen($q);
    if($inputQueryLenght < MIN_QUERY_LENGTH) {
        exit;
    }
    if($inputQueryLenght > MAX_QUERY_LENGTH) {
        exit;
    }
} else {
    exit;
}

$photoArray = array();
$allCardsJson = file_get_contents("secards.317m.json");
$cards = json_decode($allCardsJson, true);
$cacheTime = 300;

function absuri($path){
    
    $url  = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
    $url .= $_SERVER['SERVER_NAME'];
    $url .= $_SERVER['REQUEST_URI'];
    return dirname($url) . "/" . $path;
}

function PreparePhotoResult($_card) {
    global $q;
    $imageFolder = "Images/";
    $imageUrlBase = "http://www.shadowera.com/cards/";
    $image = $imageUrlBase . $_card["imageUrl"]; // from se server
    $imageThumb = $image;
    $imageThumbLocalFilename = $imageFolder . $_card["id"] . ".thumb.jpg";
    if(file_exists($imageThumbLocalFilename))
        $imageThumb = absuri($imageThumbLocalFilename);
    return array(
        'type' => "photo",
        'id' => uniqid(),
        'photo_url' => 	$image,
        'thumb_url' => $imageThumb,
        'title' => $q,
        'caption' => $q,
        'photo_width' => 344,
        'photo_height' => 480
    );
}

switch($q) {
case "random card":
    $key = rand(0, count($cards) - 1);
    array_push($photoArray, PreparePhotoResult($cards[$key]));
    $cacheTime = 1;
    break;
case "random hero":
    $heroesCards = array_values(array_filter($cards, function($c) {
        return $c["type"] == "Hero";
    }));
    $key = rand(0, count($heroesCards) - 1);
    array_push($photoArray, PreparePhotoResult($heroesCards[$key]));
    $cacheTime = 1;
    break;
case "random hero human":
    $heroesCards = array_values(array_filter($cards, function($c) {
        return $c["type"] == "Hero" && $c["faction"] == "Human";
    }));
    $key = rand(0, count($heroesCards) - 1);
    array_push($photoArray, PreparePhotoResult($heroesCards[$key]));
    $cacheTime = 1;
    break;
case "random hero shadow":
    $heroesCards = array_values(array_filter($cards, function($c) {
        return $c["type"] == "Hero" && $c["faction"] == "Shadow";
    }));
    $key = rand(0, count($heroesCards) - 1);
    array_push($photoArray, PreparePhotoResult($heroesCards[$key]));
    $cacheTime = 1;
    break;
    
case "all heroes":
    $heroesCards = array_values(array_filter($cards, function($c) {
        return $c["type"] == "Hero";
    }));
    foreach($heroesCards as $card) {
        array_push($photoArray, PreparePhotoResult($card));
    }
    break;
    
default:
    // filters
    $parameter = explode(" ", $q);
    $cardTypes = array("hero", "ally", "item", "ability", "location");
    $cardSubtypes = array("aldmor", "homunculus", "ravager", "templar", "twilight", "undead",  "wulven", "yari", "wild", "outlaw");
    $cardSubtypes = array_merge($cardSubtypes, array("attachment", "support"));
    $cardSubtypes = array_merge($cardSubtypes, array("artifact", "trap", "weapon", "armor"));
    $cardFactions = array("neutral", "human", "shadow");
    $filters = array(
        "type" => false,
        "subtype" => false,
        "faction" => false
    );
    foreach($parameter as $p) {
        if(in_array($p, $cardTypes)) {
            $filters["type"] = $p;
            continue;
        }
        if(in_array($p, $cardSubtypes)) {
            $filters["subtype"] = $p;
            continue;
        }
        if(in_array($p, $cardFactions)) {
            $filters["faction"] = $p;
            continue;
        }
    }
    if($filters["type"]) {

        $filteredCards = array_filter($cards, function($c) use($filters) {
            $typeF = $filters["type"] ? strtolower($c["type"]) == $filters["type"] : true;
            $subtypeF = $filters["subtype"] ? strtolower($c["subtype"]) == $filters["subtype"] : true;
            $factionF = $filters["faction"] ? strtolower($c["faction"]) == $filters["faction"] || !$c["faction"]: true;
            return $typeF && $subtypeF && $factionF;
        });

        foreach($filteredCards as $card) {
            array_push($photoArray, PreparePhotoResult($card));
        }

        

    } else {

        // search in nicknames first
        $nicknames = include('shortnames.php');
        foreach($nicknames as $id=>$nick) {
            if($nick==$q) {
                array_push($photoArray, PreparePhotoResult(array("id"=>$id, "imageUrl"=>($id.".jpg"))));
                break;
            }
        }

        // search names matches
        foreach($cards as $card) {
            if(strpos(strtolower($card["name"]), $q) !== false) {
                array_push($photoArray, PreparePhotoResult($card));
            }
        }

    }
    break;
}

$botUrl = "https://api.telegram.org/bot" . BOT_TOKEN . "/answerInlineQuery";
$postFields = array(
    'inline_query_id' => uniqid(),
    'results' => $photoArray,
    'cache_time' => $cacheTime
);
$data_string = json_encode($postFields);

$ch = curl_init($botUrl);                                                                      
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
    'Content-Type: application/json',                                                                                
    'Content-Length: ' . strlen($data_string))                                                                       
);                                                                                                                   
                                                                                                                     
$result = curl_exec($ch);
// save last confirm
$fp = fopen('last_confirm.json', 'w');
fwrite($fp, $result);
fclose($fp);
// save last input
$fp = fopen('last_in.json', 'w');
fwrite($fp, $content);
fclose($fp);
// save last out
$fp = fopen('last_out.json', 'w');
fwrite($fp, $data_string);
fclose($fp);