<?php

namespace adapt\tools\files;

class FileReader
{
    /**
     * @param $directory
     *
     * @return File[]
     */
    public static function read($directory)
    {
        if (!is_dir($directory)) {
            throw new \RuntimeException(sprintf("%s does not exists or is not a directory", $directory));
        }

        $result = [];
        foreach (scandir($directory) as $item) {
            if (!in_array($item, ['.', '..'], true)) {
                $result[] = new File($directory, $item);
            }
        }

        return $result;
    }
}
