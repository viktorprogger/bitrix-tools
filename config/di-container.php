<?php

return [
    'singletons' => [
        'Arrilot\BitrixMigrations\Interfaces\DatabaseStorageInterface' => [
            'class' => 'adapt\tools\migrations\DatabaseStorage',
            'table' => 'migrations',
        ],
        'Arrilot\BitrixMigrations\TemplatesCollection'                 => [
            'class' => 'adapt\tools\migrations\TemplatesCollection',
            'loadDefault' => true,
        ],
        'Arrilot\BitrixMigrations\Migrator'                            => [
            'class'  => 'Arrilot\BitrixMigrations\Migrator',
            'config' => [
                'dir'             => ADAPT_TOOLS_DIR . '/migrations',
                'use_transaction' => true,
                'default_fields'  => [
                    '\Arrilot\BitrixMigrations\Constructors\IBlock' => [
                        'INDEX_ELEMENT' => 'N',
                        'INDEX_SECTION' => 'N',
                        'VERSION'       => 2,
                        'SITE_ID'       => 's1',
                    ],
                ],
            ],
        ],
        'Arrilot\BitrixMigrations\Commands\MakeCommand'                => [
            'class' => 'Arrilot\BitrixMigrations\Commands\MakeCommand',
            'name'  => 'migrator:make',
        ],
        'Arrilot\BitrixMigrations\Commands\InstallCommand'                => [
            'class' => 'Arrilot\BitrixMigrations\Commands\InstallCommand',
            'name'  => 'migrator:install',
        ],
        'Arrilot\BitrixMigrations\Commands\MigrateCommand'                => [
            'class' => 'Arrilot\BitrixMigrations\Commands\MigrateCommand',
            'name'  => 'migrator:migrate',
        ],
        'Arrilot\BitrixMigrations\Commands\RollbackCommand'                => [
            'class' => 'Arrilot\BitrixMigrations\Commands\RollbackCommand',
            'name'  => 'migrator:rollback',
        ],
        'Arrilot\BitrixMigrations\Commands\TemplatesCommand'                => [
            'class' => 'Arrilot\BitrixMigrations\Commands\TemplatesCommand',
            'name'  => 'migrator:templates',
        ],
        'Arrilot\BitrixMigrations\Commands\StatusCommand'                => [
            'class' => 'Arrilot\BitrixMigrations\Commands\StatusCommand',
            'name'  => 'migrator:status',
        ],
        'Arrilot\BitrixMigrations\Commands\ArchiveCommand'                => [
            'class' => 'Arrilot\BitrixMigrations\Commands\ArchiveCommand',
            'name'  => 'migrator:archive',
        ],
    ],
];
