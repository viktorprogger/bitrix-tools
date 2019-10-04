<?php

use Bex\D7dull\ExampleTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class adapt_tools extends CModule
{
    public function __construct()
    {
        $arModuleVersion = [];

        include __DIR__ . '/version.php';

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_ID = 'adapt.tools';
        $this->MODULE_NAME = 'Adapt Tools';
        $this->MODULE_DESCRIPTION = 'Внутенний функционал сайта';
        $this->PARTNER_NAME = 'Adapt';
        $this->PARTNER_URI = 'http://adapt.ru';
    }

    public function doInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
    }

    public function doUninstall()
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}
