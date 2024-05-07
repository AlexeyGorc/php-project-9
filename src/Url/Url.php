<?php

namespace Hexlet\Code\Url;

use Carbon\Carbon;
use Hexlet\Code\Database\Connection;
use Hexlet\Code\Database\SQLExecutor;

class Url
{
    private ?string $name;
    private ?int $id;
    private ?Carbon $createdAt;
    private static string $tableName = 'urls';

    public function __construct()
    {
        $this->name = '';
        $this->id = null;
        $this->createdAt = Carbon::now();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = strtolower($name);
        return $this;
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
    public function setId(int $id): void
    {
        $this->id = $id;
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
     * @return Carbon|null
     */
    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    /**
     * @return array<int, UrlChecks>|null
     */
    public function getAllChecks()
    {
        if ($this->getId() <= 0) {
            return null;
        }

        $urlChecks = UrlChecks::getAllByUrlId($this->getId());

        return (!$urlChecks) ? null : $urlChecks;
    }

    /**
     * @return UrlChecks|null
     */
    public function getLastCheck()
    {
        $urlChecks = $this->getAllChecks();

        return (!$urlChecks) ? null : array_pop($urlChecks);
    }

    /**
     * @return Url
     * @throws \Exception
     */
    public function store()
    {
        $pdo = (new Connection())->get();
        $executor = new SQLExecutor($pdo);

        if (is_null($this->getId())) {
            $sql = 'INSERT INTO ' . self::$tableName . ' (name, created_at) VALUES (:name, :createdAt)';
            $sqlParams = [
                ':name' => $this->getName(),
                ':createdAt' => Carbon::now()->toDateTimeString()
            ];

            $lastId = (int)$executor->insert($sql, $sqlParams, self::$tableName);

            if ($lastId <= 0) {
                throw new \Exception('Something goes wrong. Can\'t store new url');
            }
            $this->setId($lastId);
        }

        return $this;
    }

    /**
     * @return Url
     * @throws \Exception
     */
    public static function findOrCreate(string $name = '')
    {
        if (trim($name) == '') {
            throw new \Exception('Can\'t select url because have no url name');
        }

        $pdo = (new Connection())->get();
        $executor = new SQLExecutor($pdo);

        $sql = 'SELECT * FROM ' . self::$tableName . ' WHERE name=:name LIMIT 1';
        $sqlParams = [
            ':name' => $name
        ];

        $return = $executor->select($sql, $sqlParams);

        return (!$return) ? self::createFromDatabaseRow([]) : self::createFromDatabaseRow(reset($return));
    }

    /**
     * @return Url
     * @throws \Exception
     */
    public static function findById(int $id = 0)
    {
        if ($id <= 0) {
            throw new \Exception('Can\'t select url because id = 0');
        }

        $pdo = (new Connection())->get();
        $executor = new SQLExecutor($pdo);

        $sql = 'SELECT * FROM ' . self::$tableName . ' WHERE id=:id';
        $sqlParams = [
            ':id' => $id
        ];

        $return = $executor->select($sql, $sqlParams);

        return (!$return) ? self::createFromDatabaseRow([]) : self::createFromDatabaseRow(reset($return));
    }

    public function exists(): bool
    {
        return !is_null($this->getId());
    }

    /**
     * @return array<int, Url>|null
     */
    public static function getAll()
    {
        $pdo = (new Connection())->get();
        $executor = new SQLExecutor($pdo);

        $sql = 'SELECT * FROM ' . self::$tableName . ' ORDER BY created_at DESC';
        $sqlParams = [];

        $selectedRows = $executor->select($sql, $sqlParams);

        if (!$selectedRows) {
            return null;
        }

        $returnUrls = array_map(function ($row) {
            return self::createFromDatabaseRow($row);
        }, $selectedRows);

        return $returnUrls;
    }

    /**
     * @param array<string, string> $fields
     * @return Url
     */
    private static function createFromDatabaseRow(array $fields): Url
    {
        $url = new self();

        $url->id = $fields['id'] ?? null;
        $url->name = $fields['name'] ?? null;
        $url->createdAt = isset($fields['created_at']) ? Carbon::parse($fields['created_at']) : null;

        return $url;
    }
}
