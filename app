#!/usr/bin/env php
<?php
$loader = require __DIR__ . '/vendor/autoload.php';

use adapt\tools\common\Application;
use adapt\tools\common\Config;
use Bitrix\Main\Loader;

$_SERVER["DOCUMENT_ROOT"] = dirname(dirname(dirname(__DIR__)));

error_reporting(E_ALL ^ E_NOTICE);
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_CRONTAB", true);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

/** @noinspection PhpUnhandledExceptionInspection */
Loader::includeModule("iblock");
/** @noinspection PhpUnhandledExceptionInspection */
Loader::includeModule("adapt.tools");

$config = Config::getInstance();

$application = new Application();
$application->registerCommands($config->get(['console', 'directory']), $config->get(['console', 'namespace']));
$application->registerCommands($config->get(['console', 'migrator', 'directory']), $config->get(['console', 'migrator', 'namespace']));

$application->run();

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
