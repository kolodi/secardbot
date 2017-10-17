<?php 

$pdo = include "db.php";

$nicknames = include('shortnames.php');

$sql = "UPDATE `cards` SET `nick` = :nick WHERE `cards`.`id` = :id";
$q = $pdo->prepare($sql);
foreach ($nicknames as $key => $val) {
    if($val) {
        $q->execute(array(':nick' => $val, ':id' => $key));
    }
}

