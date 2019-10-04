<?php

namespace adapt\tools\dto\video;

class FileShort extends AbstractFileDTO
{
    protected function setFileCode()
    {
        $pathinfo = pathinfo($this->path);

        if(strlen($this->name) > 15) {
            $value_e = explode('_', $pathinfo['filename']);
            $this->code = $value_e[0];
        } else {
            $this->code = $pathinfo['filename'];
        }
    }

    public function isShort()
    {
        return true;
    }
}
