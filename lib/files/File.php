<?php

namespace adapt\tools\files;

class File
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var string
     */
    private $absolutePath;

    public function __construct($directory, $filename)
    {
        $this->directory = $directory;
        $this->filename = $filename;
        $this->absolutePath = $directory . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    public function getExtension()
    {
        $info = $this->getFileInfo();
        return $info['extension'];
    }

    public function getDirName()
    {
        $info = $this->getFileInfo();
        return $info['dirname'];
    }

    public function getName()
    {
        $info = $this->getFileInfo();
        return $info['filename'];
    }

    public function isDirectory()
    {
        return is_dir($this->absolutePath);
    }

    /**
     * @return array
     */
    public function getFileInfo()
    {
        if (!isset($this->fileInfo)) {
            $this->fileInfo = pathinfo($this->absolutePath);
        }

        return $this->fileInfo;
    }

    /**
     * @return string
     */
    public function getAbsolutePath()
    {
        return $this->absolutePath;
    }
}
