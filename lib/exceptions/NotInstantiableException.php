<?php

namespace adapt\tools\exceptions;

use Exception;
use RuntimeException;

/**
 * InvalidConfigException represents an exception caused by incorrect object configuration.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since  2.0
 */
class NotInstantiableException extends RuntimeException
{
    public function __construct($class, $message = null, $code = 0, Exception $previous = null)
    {
        if ($message === null) {
            $message = "Can not instantiate $class.";
        }
        parent::__construct($message, $code, $previous);
    }
}
