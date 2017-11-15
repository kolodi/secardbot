<?php
//TODO: save user request in db

header('Content-Type: application/json');

/*
Input format:
{
  "update_id": 440148654,
  "inline_query": {
      "id": "817150061370106850",
      "from": {
          "id": 190257574,
          "is_bot": false,
          "first_name": "Kolodi",
          "username": "kolodim",
          "language_code": "en-US"
      },
      "query": "ally",
      "offset": ""
  }
}
*/
$content = file_get_contents("php://input");

$update = json_decode($content, true);
if (!$update)
{
  exit("no input");
}


include "lib.php";
include "db.php";
include "process_query.php";

$db = new DB();

// put user in db
$user = $update['inline_query']['from'];
$user["is_bot"] = intval(boolval($user["is_bot"]));
$sql = "INSERT INTO tg_user (id, is_bot, first_name, username, language_code) VALUES (?,?,?,?,?)";
$stmt = $db->pdo->prepare($sql);
$stmt->execute(array_values($user));

// put query in db
$inline_query = $update["inline_query"];
$inline_query["from"] = $user["id"];
$sql = "INSERT INTO inline_query (id, user, query, offset) VALUES (?,?,?,?)";
$stmt = $db->pdo->prepare($sql);
$stmt->execute(array_values($inline_query));



$q = $update['inline_query']['query'];
$inlineQueryId = $update['inline_query']['id'];


$tg = new TG('bot_token');

$responce = GetInlineAnswer($q, $db);
$responce->inline_query_id = $inlineQueryId;
//echo $responce->inline_query_id;
$data_string = json_encode($responce);


//$result = $tg->SendInlineAnswerToTG($data_string);