<?php

namespace Hexlet\Code;

use PDO;

class SQLExecutor
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function insert($sql, $params, $tableName)
    {
        $this->executeQuery($sql, $params);

        return $this->pdo->lastInsertId($tableName . 'id_seq');
    }

    public function select($sql, $params)
    {
        $data = $this->executeQuery($sql, $params);

        return $data;
    }

    public function executeQuery($sql, $params)
    {
        if ($sql == '') {
            throw new \Exception('Empty query');
        }

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}