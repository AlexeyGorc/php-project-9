<?php

namespace Hexlet\Code;

use Hexlet\Code\Connection;
use Hexlet\code\SQLExecutor;
use Carbon\Carbon;

class UrlChecks
{
    private ?int $id;
    private int $url_id;
    private string $status_code = '';
    private string $h1 = '';
    private string $title = '';
    private string $description = '';
    private string $created_at = '';
    private static string $tableName = 'url_checks';

    public function __construct()
    {
        $this->id = null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setid(int $id)
    {
        $this->id = $id;
    }

    public function getUrlId()
    {
        return $this->url_id;
    }

    public function setUrlId(int $url_id)
    {
        $this->url_id = $url_id;
        return $this;
    }

    public function getStatusCode()
    {
        return $this->status_code;
    }

    public function setStatusCode(string $code = '')
    {
        $this->status_code = $code;
        return $this;
    }

    public function getH1()
    {
        return $this->h1;
    }

    public function setH1(string $string = '')
    {
        $this->h1 = $string;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle(string $string = '')
    {
        $this->title = $string;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(string $string = '')
    {
        $this->description = $string;
        return $this;
    }

    public function getCreatedAt()
    {
        return Carbon::parse($this->created_at);
    }

    public function setField($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }

    public function store()
    {
        if ($this->getUrlId() <= 0) {
            throw new \Exception('Url id must be set');
        }

        $pdo = Connection::get()->connect();
        $executor = new SQLExecutor($pdo);

        if (is_null($this->getId())) {
            $sql = 'INSERT INTO ' . self::$tableName .
                ' (url_id, status_code, h1, title, description, created_at) VALUES ' .
                '(:url_id, :status_code, :h1, :title, :description, :created_at)';
            $sqlParams = [
                ':url_id' => $this->getUrlId(),
                ':status_code' => $this->getStatusCode(),
                ':h1' => $this->getH1(),
                ':title' => $this->getTitle(),
                ':description' => $this->getDescription(),
                ':created_at' => $this->getCreatedAt()
            ];

            $lastId = (int)$executor->insert($sql, $sqlParams, self::$tableName);

            if ($lastId <= 0) {
                throw new \Exception('Insert failed');
            }
            $this->setId($lastId);
        }
        return $this;
    }

    public static function getAllByUrlId(int $url_id = 0)
    {
        if ($url_id <= 0) {
            throw new \Exception('Url id must be set');
        }

        $pdo = Connection::get()->connect();
        $executor = new SQLExecutor($pdo);

        $sql = 'SELECT * FROM ' . self::$tableName . 'WHERE url_id = :url_id ORDER BY created_at DESC';
        $sqlParams = [
            ':url_id' => $url_id
        ];

        $selectedRows = $executor->select($sql, $sqlParams);

        if (!$selectedRows) {
            return null;
        }

        return array_map(function ($row) {
            return self::create($row);
        }, $selectedRows);
    }

    private static function create($fields)
    {
        $url = new self();

        if (count($fields) <= 0) {
            return $url;
        }

        foreach ($fields as $key => $value) {
            $url->setField($key, (string)$value);
        }

        return $url;
    }
}