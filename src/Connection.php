<?php

namespace Hexlet\Code;

final class Connection
{
    private static ?Connection $conn = null;

    /**
     * @return \PDO
     * @throws \Exception
     */
    public function connect()
    {

        if (getenv('DATABASE_URL')) {
            $databaseUrl = parse_url($_ENV['DATABASE_URL']);
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
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        return $pdo;
    }

    public static function get()
    {
        if (null === static::$conn) {
            static::$conn = new self();
        }

        return static::$conn;
    }
}