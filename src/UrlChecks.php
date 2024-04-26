<?php

namespace Hexlet\Code;

use Hexlet\Code\Connection;
use Hexlet\Code\SQLExecutor;
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

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return void
     */
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getUrlId()
    {
        return $this->url_id;
    }

    /**
     * @return $this
     */
    public function setUrlId(int $url_id)
    {
        $this->url_id = $url_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatusCode()
    {
        return $this->status_code;
    }

    /**
     * @return $this
     */
    public function setStatusCode(string $code = '')
    {
        $this->status_code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getH1()
    {
        return $this->h1;
    }

    /**
     * @return $this
     */
    public function setH1(string $string = '')
    {
        $this->h1 = $string;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return $this
     */
    public function setTitle(string $string = '')
    {
        $this->title = $string;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return $this
     */
    public function setDescription(string $string = '')
    {
        $this->description = $string;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return Carbon::parse($this->created_at);
    }

    /**
     * @param string $name
     * @param string $value
     * @return void
     */
    private function setField($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }

    /**
     * @return UrlChecks
     * @throws \Exception
     */
    public function store()
    {
        if ($this->getUrlId() <= 0) {
            throw new \Exception('Can\'t store new url_check because have no url_id');
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
                ':created_at' => Carbon::now()->toDateTimeString()
            ];

            $lastId = (int)$executor->insert($sql, $sqlParams, self::$tableName);

            if ($lastId <= 0) {
                throw new \Exception('Something goes wrong. Can\'t store new url_check');
            }
            $this->setId($lastId);
        }

        return $this;
    }


    /**
     * @return array<int, UrlChecks>|null
     */
    public static function getAllByUrlId(int $url_id = 0)
    {
        if ($url_id <= 0) {
            throw new \Exception('Can\'t select url_checks because url_id = 0');
        }

        $pdo = Connection::get()->connect();
        $executor = new SQLExecutor($pdo);

        $sql = 'SELECT * FROM ' . self::$tableName . ' WHERE url_id=:url_id  ORDER BY created_at DESC';
        $sqlParams = [
            ':url_id' => $url_id
        ];

        $selectedRows = $executor->select($sql, $sqlParams);

        if (!$selectedRows) {
            return null;
        }

        $returnUrls = array_map(function ($row) {
            return self::create($row);
        }, $selectedRows);

        return $returnUrls;
    }

    /**
     * @param array<string, string> $fields
     * @return UrlChecks
     */
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
