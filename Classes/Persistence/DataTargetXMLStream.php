<?php

namespace CPSIT\T3importExport\Persistence;


use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\Domain\Model\DataStreamInterface;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataTargetXMLStream extends DataTargetFileStream implements DataTargetInterface, ConfigurableInterface
{
    const DEFAULT_HEADER = '<?xml version="1.0" encoding="UTF-8"?>';
    const DEFAULT_ROOT_NODE = 'rows';

    /**
     * subConfig for Data-Traget
     *
     * @var array
     */
    protected $config;

    /**
     * absolute path to temp file
     *
     * @var string
     */
    protected $tempFile;

    /**
     * @var \XMLWriter
     */
    protected $writer;

    /**
     * @param array|\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
     * @param array|null $configuration
     * @return void
     * @throws FileOperationErrorException
     */
    public function persist($object, array $configuration = null)
    {
        // init XML
        $this->initFileIfNotExist($configuration);
        // write object data into array
        parent::persist($object, $configuration);
    }

    /**
     * @param array|null $result
     * @param array|\Iterator|null $configuration
     * @return void
     */
    public function persistAll($result = null, array $configuration = null)
    {
        if (isset($this->writer)) {
            // close file
            $this->writer->endElement();
            // remove writer from memory and remove possible access locks from files
            $this->writer->flush();
            unset($this->writer);
        }


        // todo: add filepath
    }

    /**
     * @param $buffer
     * @throws FileOperationErrorException
     */
    protected function writeBuffer($buffer)
    {
        if (isset($this->writer)) {
            $this->writer->writeRaw($buffer);
            // write stuff into output
            $this->writer->flush();
        }
    }

    /**
     * @param $configuration
     * @throws FileOperationErrorException
     */
    protected function initFileIfNotExist($configuration)
    {
        if (!isset($this->writer)) {

            $this->writer = new \XMLWriter();

            if(isset($configuration['output']) && $configuration['output'] == 'file') {
                $tmpFileName = $this->createAnonymTempFile();
            } else {
                $tmpFileName = 'php://output';
            }
            $this->writer->openUri($tmpFileName);
            $this->writer->writeRaw($this->getFileHeader($configuration));
            $this->writer->startElement($this->getRootNodeName($configuration));
            $this->writer->flush();
        }
    }

    /**
     * @param array $configuration
     * @return string
     */
    protected function getFileHeader($configuration = null)
    {
        $header = self::DEFAULT_HEADER;
        if (isset($configuration) && isset($configuration['header'])) {
            $header = $configuration['header'];
        }

        return $header;
    }

    /**
     * @param array $configuration
     * @return string
     */
    protected function  getRootNodeName($configuration = null)
    {
        $nodeName = self::DEFAULT_ROOT_NODE;
        if (isset($configuration) && isset($configuration['rootNodeName'])) {
            $nodeName = $configuration['rootNodeName'];
        }

        return $nodeName;
    }
}