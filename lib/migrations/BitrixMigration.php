<?php

namespace adapt\tools\migrations;

use adapt\tools\Common\IBlock;
use Arrilot\BitrixMigrations\BaseMigrations\BitrixMigration as BaseMigration;
use Arrilot\BitrixMigrations\Exceptions\MigrationException;
use CIBlockProperty;

class BitrixMigration extends BaseMigration
{
    /**
     * @param int        $IB
     * @param int|string $code
     * @param array      $fields
     *
     * @throws MigrationException
     */
    public function updateProperty($IB, $code, $fields)
    {
        $IB = (int)$IB;
        if ($IB <= 0) {
            throw new MigrationException('You must set iblock id due to ambiguity avoiding');
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $arProperty = CIBlockProperty::GetList([], ['IBLOCK_ID' => $IB, 'CODE' => $code])->Fetch();
        if (!$arProperty['ID']) {
            throw new MigrationException(sprintf('Can\'t find property "%s" in iblock %d', $code, $IB));
        }

        $prop = new CIBlockProperty();
        if (!$prop->Update($arProperty['ID'], $fields)) {
            throw new MigrationException(sprintf('Can\'t update property "%s" with error: %s', $code, $prop->LAST_ERROR));
        }
    }
}
