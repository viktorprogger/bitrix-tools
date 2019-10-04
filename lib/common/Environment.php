<?php
/** @noinspection PhpDocMissingThrowsInspection */

namespace adapt\tools\common;

use Bitrix\Main\Config\Option;

class Environment {

    /**
     * @return bool
     */
    public static function isDev()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return (bool)Option::get('adapt.tools', 'mode-dev', false);
    }
}
