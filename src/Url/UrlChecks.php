<?php

namespace Hexlet\Code\Url;

use Carbon\Carbon;
use Hexlet\Code\Database\Connection;
use Hexlet\Code\Database\SQLExecutor;

class UrlChecks
{
    private ?int $id;
    private int $urlId;
    private int $statusCode;
    private ?string $h1;
    private ?string $title;
    private ?string $description;
    private ?Carbon $createdAt;

    private static string $tableName = 'url_checks';

    public function __construct()
    {
        $this->id = null;
        $this->urlId = 0;
        $this->statusCode = 0;
        $this->h1 = '';
        $this->title = '';
        $this->description = '';
        $this->createdAt = Carbon::now();
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
        return $this->urlId;
    }

    /**
     * @return $this
     */
    public function setUrlId(int $urlId)
    {
        $this->urlId = $urlId;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return $this
     */
    public function setStatusCode(int $code = 0)
    {
        $this->statusCode = $code;
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
    public function setH1(string $value = '')
    {
        $this->h1 = $value;
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
    public function setTitle(string $string)
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
    public function setDescription(string $string)
    {
        $this->description = $string;
        return $this;
    }

    /**
     * @param Carbon|string $createdAt
     */
    public function setCreatedAt($createdAt): void
    {
        if ($createdAt instanceof Carbon) {
            $this->createdAt = $createdAt;
        } else {
            $this->createdAt = Carbon::parse($createdAt);
        }
    }

    /**
     * @return Carbon
     */
    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
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

        $pdo = (new Connection())->get();
        $executor = new SQLExecutor($pdo);

        if (is_null($this->getId())) {
            $sql = 'INSERT INTO ' . self::$tableName .
                ' (url_id, status_code, h1, title, description, created_at) VALUES ' .
                '(:url_id, :status_code, :h1, :title, :description, :createdAt)';
            $sqlParams = [
                ':url_id' => $this->getUrlId(),
                ':status_code' => $this->getStatusCode(),
                ':h1' => $this->getH1(),
                ':title' => $this->getTitle(),
                ':description' => $this->getDescription(),
                ':createdAt' => Carbon::now()->toDateTimeString()
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

        $pdo = (new Connection())->get();
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
            if ($key === 'created_at') {
                $url->setCreatedAt($value);
            } elseif ($key === 'status_code') {
                $url->setStatusCode((int)$value);
            } else {
                $url->setField($key, (string)$value);
            }
        }

        return $url;
    }
}
