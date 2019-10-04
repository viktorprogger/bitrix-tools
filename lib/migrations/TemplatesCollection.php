<?php

namespace adapt\tools\migrations;

use Arrilot\BitrixMigrations\TemplatesCollection as BaseCollection;

class TemplatesCollection extends BaseCollection
{
    public function __construct($loadDefault = false)
    {
        parent::__construct();

        if ($loadDefault === true) {
            $this->registerBasicTemplates();
        }
    }
}
