<?php

namespace adapt\tools\common;

use Arrilot\BitrixMigrations\Exceptions\MigrationException;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

class IBlock
{
    /**
     * @param string $code
     * @param string $type
     *
     * @return int
     * @throws ArgumentException
     * @throws LoaderException
     * @throws MigrationException
     */
    public static function getIblockId($code, $type = '')
    {
        if (strlen($code) <= 0) {
            throw new MigrationException('Iblock code is empty');
        }

        Loader::includeModule('iblock');

        $parameters = [
            'filter' => [
                'CODE' => $code,
            ],
            'select' => ['ID']
        ];

        if ($type !== '') {
            $parameters['filter']['IBLOCK_TYPE_ID'] = $type;
        }

        $result = IblockTable::getList($parameters)->fetchRaw();
        if ($result === false) {
            return 0;
        }

        return (int)$result['ID'];
    }

    public static function getPropertyId($ib, $code)
    {
        return (int)\CIBlockProperty::GetList(false, ['IBLOCK_ID' => $ib, 'CODE' => $code])->Fetch()['ID'];
    }
}
