<?php

namespace adapt\tools\common;

use ReflectionClass;
use RuntimeException;

class Factory
{
    /**
     * @param string|array $config
     *
     * @return object
     * @throws RuntimeException
     */
    public static function createFromConfig($config)
    {
        if (is_array($config)) {
            if (!isset($config['class'])) {
                throw new RuntimeException('Config must have "class" key');
            }

            /** @noinspection PhpUnhandledExceptionInspection */
            $reflection = new ReflectionClass($config['class']);
            $constructor = $reflection->getConstructor();
            $paramReflection = $constructor->getParameters();
            $parameters = [];
            foreach ($paramReflection as $param) {
                if (isset($config[$param->getName()])) {
                    $parameters[] = $config[$param->getName()];
                }
            }

            return $reflection->newInstanceArgs($parameters);
        }

        if (is_string($config)) {
            return new $config;
        }

        throw new RuntimeException('Config must be either a classname or array');
    }
}
