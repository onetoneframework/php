<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Database\Driver;

use SQLite3;
use SQLite3Result;
use Closure;
use Clover\Classes\Data\ArrayObject;

class SqLite
{
    private $connection;

    public function __construct($file, int $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE, string $encryptionKey = "")
    {
        $this->connection = new SQLite3($file, $flags, $encryptionKey);
    }

    public function isConnected()
    {
        return $this->connection->lastErrorCode() == 0;
    }

    public function getLastErrorMessage()
    {
        return $this->connection->lastErrorMsg();
    }

    public function fetchRows($result, Closure $closure)
    {
        while ($row = $this->fetchArray($result)) {
            $closure($row);
        }
    }

    public function fetchArray(SQLite3Result $result): array|bool|ArrayObject
    {
        $result = $result->fetchArray(SQLITE3_ASSOC);

        if (is_array($result)) {
            $result = new ArrayObject($result);
        }

        return $result;
    }

    public function getColumnList($table)
    {
        $query = <<<EOD
            SELECT 
                * 
            FROM 
                PRAGMA_TABLE_INFO('%s')
            ORDER BY 1
EOD;

        $result = $this->query(sprintf($query, $table));

        $rows = new ArrayObject();
        while ($row = $this->fetchArray($result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function getTableList()
    {
        $query = <<<EOD
            SELECT 
                * 
            FROM 
                sqlite_master 
            WHERE type IN ('table', 'view') 
                AND name NOT LIKE 'sqlite_%' 
            UNION ALL
            SELECT 
                * 
            FROM 
                sqlite_temp_master 
            WHERE type IN ('table', 'view') 
            ORDER BY 1;
EOD;

        $result = $this->query($query);

        $rows = new ArrayObject();
        while ($row = $this->fetchArray($result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function query($sql): SQLite3Result|bool
    {
        return $this->connection->query($sql);
    }

    public function querySingle($sql)
    {
        return $this->connection->querySingle($sql);
    }

    public function execute($sql)
    {
        return $this->connection->exec($sql);
    }

    public function close()
    {
        return $this->connection->close();
    }
}
