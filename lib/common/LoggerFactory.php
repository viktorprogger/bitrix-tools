<?php

namespace adapt\tools\common;

use adapt\tools\Exceptions\NotImplementedException;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

class LoggerFactory
{
    /** @var Logger[] $loggers */
    private static $loggers = [];

    /**
     * @param string $code
     *
     * @return Logger
     */
    public static function get($code = 'default')
    {
        if (!isset(self::$loggers[$code])) {
            $config = Config::getInstance()->get(['log', $code]);

            if ($config === null) {
                throw new NotImplementedException(sprintf("Config for logger '%s' is not found", $code));
            }

            self::$loggers[$code] = self::create($code, $config);
        }

        return self::$loggers[$code];
    }

    /**
     * @param $code
     * @param $config
     *
     * @return Logger
     */
    public static function create($code, $config)
    {
        $logger = new Logger($code);

        if (isset($config['handlers'])) {
            foreach ($config['handlers'] as $handlerConfig) {
                /** @var HandlerInterface $handler */
                $handler = self::createFromConfig($handlerConfig);

                if (isset($handlerConfig['formatter'])) {
                    /** @var FormatterInterface $formatter */
                    $formatter = self::createFromConfig($handlerConfig['formatter']);
                    $handler->setFormatter($formatter);
                }

                $logger->pushHandler($handler);
            }
        }

        if (isset($config['processors'])) {
            foreach ($config['handlers'] as $processor) {
                $logger->pushProcessor($processor);
            }
        }

        return $logger;
    }
}
