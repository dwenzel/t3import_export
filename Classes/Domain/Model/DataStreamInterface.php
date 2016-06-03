<?php

namespace CPSIT\T3importExport\Domain\Model;

interface DataStreamInterface
{
    /**
     * @param $buffer
     * @return string
     */
    public function setSteamBuffer($buffer);

    /**
     * @return string
     */
    public function getSteamBuffer();
}
