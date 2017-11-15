<?php
include "db.php";
$db = new DB();
$pdo = $db->pdo;

$cards = $db->GetAllCards();

$classesListRaw = $db->GetManyWithSQL("SELECT * FROM `classes`;");
$classesList = array_map(function ($dbClass) {
    return $dbClass["name"];
}, $classesListRaw);

$counter = 0;
//var_dump($classesList);
foreach ($cards as $card) {
    $classString = str_replace(' ', '', $card["classes"]);

    if(!$classString) continue;

    $classes = explode(",", $classString);
    $num = count($classes);

    if ($num < 1) continue;

    if($num > 1) {
        $n = $num;
    }
    //echo count($classes);
    
    //create links in cards_classes table
    foreach ($classes as $c) {
        $id = array_search($c, $classesList);
        if ($id < 0) {
            echo "error";
            continue;
        }
        $sql = "INSERT INTO `cards_classes` (card_id, class_id) \n"
        . "VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $row = array(
            $card["id"],
            $id
        );
        //$stmt->execute($row);
        $counter ++;
        
        
    }
    
    //echo "<br>";


}
echo $counter;