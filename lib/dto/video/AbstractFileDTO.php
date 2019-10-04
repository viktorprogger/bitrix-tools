<?php

namespace adapt\tools\dto\video;

abstract class AbstractFileDTO
{
    protected $path;
    protected $name;
    protected $code;

    /**
     * @param string $filepath
     */
    public function __construct($filepath)
    {
        $this->path = $filepath;
        $this->setName();
        $this->setFileCode();
    }

    abstract protected function setFileCode();
    abstract public function isShort();

    protected function setName() {
        $this->name = array_pop(array_filter(explode(DIRECTORY_SEPARATOR, $this->path)));
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPath()
    {
        return $this->path;
    }

    /**
     * Yeah, I'm dirty!
     * @return string
     */
    public function getPathRelative()
    {
        $rootLen = strlen($_SERVER['DOCUMENT_ROOT']);

        return substr($this->getPath(), $rootLen + 1);
    }

    public function getChildCode()
    {
        return $this->code;
    }
}
