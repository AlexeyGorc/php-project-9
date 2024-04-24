<?php

namespace Hexlet\Code;

class Connection
{
    private static ?Connection $connection = null;

    public function connect(): \PDO
    {
        if (getenv('DATABASE_URL')) {
            $databaseUrl = parse_url($_ENV('DATABASE_URL'));
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
            throw new \Exception('Error reading database configuration');
        }

        $connection = sprintf(
            "psql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            $params['host'],
            $params['port'],
            $params['database'],
            $params['user'],
            $params['password']
        );

        $pdo = new \PDO($connection);
        $pdo->setAttribute((int)\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        return $pdo;
    }

    public static function get(): ?Connection
    {
        if (null === static::$connection) {
            static::$connection = new self();
        }

        return static::$connection;
    }
}