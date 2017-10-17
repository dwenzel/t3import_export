<?php

namespace CPSIT\T3importExport\Domain\Model;

interface DataStreamInterface
{
    /**
     * @param $buffer
     * @return string
     */
    public function setStreamBuffer($buffer);

    /**
     * @return string
     */
    public function getStreamBuffer();
}
