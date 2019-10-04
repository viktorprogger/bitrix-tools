<?php

namespace adapt\tools\common;

use adapt\tools\common\arrayHelper\ArrayHelper;
use adapt\tools\di\Container;
use Closure;

final class Config
{
    private static $instance;
    private $configCompiled = [];
    /**
     * @var Container
     */
    private $container;

    private $config = [];

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct()
    {
        $configDir = dirname(dirname(__DIR__));
        $this->config = require $configDir . DIRECTORY_SEPARATOR . 'config/main.php';

        if (file_exists($configDir . DIRECTORY_SEPARATOR . 'config/main.local.php')) {
            $this->config = ArrayHelper::merge($this->config, require $configDir . DIRECTORY_SEPARATOR . 'config/main.local.php');
        }

        $this->container = new Container($this->get('di-container'));
    }

    public function __clone()
    {
    }

    /**
     * @param string|Closure|array $name     key name of the array element, an array of keys or property name of the
     *                                       object, or an anonymous function returning the value. The anonymous
     *                                       function signature should be:
     *                                       `function($array, $defaultValue)`.
     * @param mixed                $default  the default value to be returned if the specified array key does not
     *                                       exist. Not used when getting value from an object.
     *
     * @return mixed the value of the element if found, default value otherwise
     */
    public function get($name, $default = null)
    {
        if (!$result = ArrayHelper::getValue($this->configCompiled, $name)) {
            $result = ArrayHelper::getValue($this->config, $name, $default);
            $result = $this->configPrepare($result);
            ArrayHelper::setValue($this->configCompiled, $name, $result);
        }

        return $result;
    }

    public function getContainer()
    {
        return $this->container;
    }

    private function configPrepare(&$config)
    {
        if (is_array($config)) {
            foreach ($config as &$value) {
                $value = $this->configPrepare($value);
            }
        } elseif ($config instanceof Closure) {
            $config = $config();
        }

        return $config;
    }
}
