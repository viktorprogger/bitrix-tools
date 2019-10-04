<?php

namespace adapt\tools\common;

use adapt\tools\di\Container;
use adapt\tools\exceptions\NotInstantiableException;
use adapt\tools\files\FileReader;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;

class Application extends BaseApplication
{
    /**
     * @var Container
     */
    private $container;

    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        $this->container = Config::getInstance()->getContainer();

        parent::__construct($name, $version);
    }

    public function registerCommands($directory, $namespace)
    {
        foreach (FileReader::read($directory) as $file) {
            if ($file->isDirectory()) {
                $this->registerCommands($file->getAbsolutePath(), $namespace . '\\' . $file->getFilename());
            } elseif ($file->getExtension() === 'php') {
                $class = $namespace . '\\' . $file->getName();

                try {
                    $command = $this->container->get($class);

                    if ($command instanceof Command) {
                        $this->add($command);
                    }
                } catch (NotInstantiableException $e) {
                }
            }
        }
    }
}
