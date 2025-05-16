<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Domain\Model;

interface DataStreamInterface
{
    /**
     * @return string
     */
    public function setStreamBuffer($buffer);

    /**
     * @return string
     */
    public function getStreamBuffer();
}
