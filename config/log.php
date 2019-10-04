<?php

use adapt\tools\common\Environment;
use Monolog\Logger;

return [
    'default' => [
        'handlers' => [
            'stream' => [
                'class'  => 'Monolog\Handler\StreamHandler',
                'stream' => static function () {
                    return fopen(dirname(__DIR__) . '/runtime/adapt.log', 'ab');
                },
                'level'  => static function() {
                    return Environment::isDev() ? Logger::DEBUG : Logger::ERROR;
                },
            ],
        ],
    ],
];
