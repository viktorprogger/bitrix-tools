<?php

namespace adapt\tools\migrations;

use Arrilot\BitrixMigrations\Storages\BitrixDatabaseStorage;

class DatabaseStorage extends BitrixDatabaseStorage
{
    const TIMEOUT = 28800; // 8 часов

    public function __construct($table)
    {
        parent::__construct($table);

        if ($this->db->type === 'MYSQL') {
            $this->db->Query('SET wait_timeout=' . self::TIMEOUT);
        }
    }
}
