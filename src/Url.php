<?php

namespace Hexlet\Code;

use Hexlet\Code\Connection;
use Hexlet\Code\SQLExecutor;
use Hexlet\Code\UrlChecks;
use Carbon\Carbon;

class Url
{
    private string $name = '';
    private ?int $id;
    private string $created_at = '';
    private static string $tableName = 'urls';

    public function __construct()
    {
        $this->id = null;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    public function getCreatedAt()
    {
        return Carbon::parse($this->created_at);
    }

    private function setField(string $name, string $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }

    public function getAllChecks()
    {
        if ($this->getId() <= 0) {
            return null;
        }

        $urlChecks = UrlChecks::getAllByUrlId($this->getId());
        return (!$urlChecks) ? null : $urlChecks;
    }

    public function getLastCheck()
    {
        $urlChecks = $this->getAllChecks();
        return (!$urlChecks) ? null : reset($urlChecks);
    }

    public function store()
    {
        if ($this->getName() == '') {
            throw new \Exception('Please set url name');
        }

        $pdo = Connection::get()->connect();
        $executor = new SQLExecutor($pdo);

        if (is_null($this->getId())) {
            $sql = 'INSERT INTO ' . self::$tableName . ' (name, created_at) VALUES (:name, :created_at)';
            $sqlParams = [
                ':name' => $this->getName(),
                ':created_at' => Carbon::now()->toDateTimeString()
            ];

            $lastId = (int)$executor->insert($sql, $sqlParams, self::$tableName);

            if ($lastId <= 0) {
                throw new \Exception('Error insert data');
            }
            $this->setId($lastId);
        }

        return $this;
    }

    public static function byId(int $id = 0)
    {
        if ($id <= 0) {
            throw new \Exception('Invalid id');
        }

        $pdo = Connection::get()->connect();
        $executor = new SQLExecutor($pdo);

        $sql = 'SELECT * FROM ' . self::$tableName . ' WHERE id = :id';
        $sqlParams = [
            ':id' => $id
        ];

        $return = $executor->select($sql, $sqlParams);
        return (!$return) ? self::create([]) : self::create(reset($return));
    }

    public static function getAll()
    {
        $pdo = Connection::get()->connect();
        $executor = new SQLExecutor($pdo);

        $sql = 'SELECT * FROM ' . self::$tableName . ' ORDER BY created_at DESC';
        $sqlParams = [];

        $selectedRows = $executor->select($sql, $sqlParams);

        if (!$selectedRows) {
            return null;
        }

        $returnUrls = array_map(function ($row) {
           return self::create($row);
        }, $selectedRows);

        return $returnUrls;
    }

    private static function create($fields)
    {
        $url = new self();

        if (count($fields) <= 0) {
            return $url;
        }

        foreach ($fields as $key => $value) {
            $url->setField($key, $value);
        }

        return $url;
    }
}