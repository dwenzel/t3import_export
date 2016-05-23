<?php
/**
 * Created by PhpStorm.
 * User: benjamin
 * Date: 19.05.16
 * Time: 15:53
 */

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