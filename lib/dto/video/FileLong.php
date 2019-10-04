<?php

namespace adapt\tools\dto\video;

class FileLong extends AbstractFileDTO
{
    protected function setFileCode()
    {
        $pathinfo = pathinfo($this->path);
        $this->code = $pathinfo['filename'];
    }

    public function isShort()
    {
        return false;
    }
}
