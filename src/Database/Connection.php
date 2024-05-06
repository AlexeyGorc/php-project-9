<?php

namespace Hexlet\Code\Database;

class Connection
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = $this->connect();
    }

    /**
     * Подключение к базе данных и возврат экземпляра объекта \PDO
     * @return \PDO
     * @throws \Exception
     */
    private function connect(): \PDO
    {
        if (getenv('DATABASE_URL')) {
            $databaseUrl = parse_url(getenv('DATABASE_URL'));
        }

        if (isset($databaseUrl['host'])) {
            $params['host'] = $databaseUrl['host'];
            $params['port'] = isset($databaseUrl['port']) ? $databaseUrl['port'] : null;
            $params['database'] = isset($databaseUrl['path']) ? ltrim($databaseUrl['path'], '/') : null;
            $params['user'] = isset($databaseUrl['user']) ? $databaseUrl['user'] : null;
            $params['password'] = isset($databaseUrl['pass']) ? $databaseUrl['pass'] : null;
        } else {
            $params = parse_ini_file('database.ini');
        }
        if ($params === false) {
            throw new \Exception("Error reading database configuration file");
        }

        $conStr = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            $params['host'],
            $params['port'],
            $params['database'],
            $params['user'],
            $params['password']
        );

        $pdo = new \PDO($conStr);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /**
     * Получить экземпляр подключения к базе данных
     * @return \PDO
     */
    public function get(): \PDO
    {
        return $this->pdo;
    }
}
