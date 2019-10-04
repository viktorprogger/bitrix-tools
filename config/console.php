<?php

return [
    'namespace' => 'adapt\\tools\\commands',
    'directory' => ADAPT_TOOLS_DIR . '/lib/commands',
    'migrator'  => [
        'namespace' => 'Arrilot\\BitrixMigrations\\Commands',
        'directory' => ADAPT_TOOLS_DIR . '/vendor/arrilot/bitrix-migrations/src/Commands',
    ],
];
