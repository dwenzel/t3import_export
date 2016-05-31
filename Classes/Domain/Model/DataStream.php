<?php
/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace CPSIT\T3importExport\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;


class DataStream extends AbstractEntity
{
    protected $context;

    protected $outputBuffer;

    protected $tmpFile;

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    public function __call($name, $arguments)
    {
        $propertyName = lcfirst(str_replace('set', '', $name));

        if (count($arguments) == 1) {
            $this->context[$propertyName] = $arguments[0];
        } else {
            $this->context[$propertyName] = $arguments;
        }
    }

    public function generateOutput($persist = false)
    {
        $buffer = http_build_query($this->context);
        if ($persist) {
            $this->outputBuffer = $buffer;
        }
        return $buffer;
    }

    /**
     * @return mixed
     */
    public function getTmpFile()
    {
        return $this->tmpFile;
    }

    /**
     * @param mixed $tmpFile
     */
    public function setTmpFile($tmpFile)
    {
        $this->tmpFile = $tmpFile;
    }

    /**
     * @return mixed
     */
    public function getOutputBuffer()
    {
        return $this->outputBuffer;
    }

    /**
     * @param mixed $outputBuffer
     */
    public function setOutputBuffer($outputBuffer)
    {
        $this->outputBuffer = $outputBuffer;
    }
}