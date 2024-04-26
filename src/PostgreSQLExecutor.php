<?php

namespace Hexlet\Code;

class PostgreSQLExecutor
{
    /**
     * @var \PDO
     */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param string $sql
     * @param array<string, string|int> $params
     * @param string $tableName
     * @return string|false
     */
    public function insert($sql, $params, $tableName)
    {
        $this->executeQuery($sql, $params);

        return $this->pdo->lastInsertId($tableName . '_id_seq');
    }

    /**
     * @param string $sql
     * @param array<string, string|int> $params
     * @return false|array<int, array<mixed>>
     */
    public function select($sql, $params)
    {
        $data = $this->executeQuery($sql, $params);

        return $data;
    }

    /**
     * @param string $sql
     * @param array<string, string|int> $params
     * @return false|array<int, array<mixed>>
     * @throws \Exception
     */
    public function executeQuery($sql, $params)
    {
        if ($sql == '') {
            throw new \Exception('Can\'t execute empty sql');
        }

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
