<?php

class DataBase {
    private $pdo;

    public function __construct() {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DATABASE.";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    public function lastIndex(){
        return $this->pdo->lastInsertId();
    }

    public function transaction($tipo) {
        switch ($tipo) {
            case TR_BEGIN:
                $this->pdo->beginTransaction();
                break;
            case TR_COMMIT:
                $this->pdo->commit();
                break;
            case TR_ROLLBACK:
                $this->pdo->rollBack();
                break;
        }
    }
}