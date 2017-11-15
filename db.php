<?php
define("DB_HOST", "localhost");
define("DB_NAME", "se");
define("DB_USERNAME", "se");
define("DB_USER_PASSWORD", "se");
class DB
{
    public $pdo;

    function __construct()
    {
        try {
            $this->pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_USER_PASSWORD);
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function GetManyWithSQL($sql)
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchall(PDO::FETCH_ASSOC);
    }

    public function GetSingleWithSQL($sql)
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function GetAllCards()
    {
        $sql = "SELECT * FROM `cards`";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchall(PDO::FETCH_ASSOC);
    }

}