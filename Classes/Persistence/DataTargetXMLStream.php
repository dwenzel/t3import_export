<?php

namespace CPSIT\T3importExport\Persistence;


use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\Domain\Model\DataStreamInterface;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataTargetXMLStream extends DataTargetRepository implements DataTargetInterface, ConfigurableInterface
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
     * @param array|\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
     * @param array|null $configuration
     * @return void
     * @throws FileOperationErrorException
     */
    public function persist($object, array $configuration = null)
    {
        $this->initFileIfNotExist($configuration);

        if ($object instanceof DataStreamInterface) {
            $this->writeData($object->getSteamBuffer());
            $object->setSteamBuffer(null);
        }
    }

    /**
     * @param array|null|null $result
     * @param array|null|null $configuration
     * @return void
     */
    public function persistAll(array $result = null, array $configuration = null)
    {
        // close file
        $foot = '</'.$this->getRootNodeName($configuration).'>';
        $this->writeData($foot);
    }

    /**
     * @param $buffer
     * @throws FileOperationErrorException
     */
    protected function writeData($buffer)
    {
        // create new tempFile if non existing
        if (empty($this->tempFile) || !file_exists($this->tempFile)) {
            $this->tempFile = $this->createAnonymTempFile();
        }

        // file put content
        if (isset($this->tempFile) && $this->writeDataIntoFile($this->tempFile, $buffer) === false) {
            throw new FileOperationErrorException(
                'can\'t write in temp file: \''. $this->tempFile .'\''
            );
        }
    }

    /**
     * @param string $absoluteFilePath
     * @param string $data
     * @return bool
     */
    protected function writeDataIntoFile($absoluteFilePath, $data)
    {
        $isSuccess = false;
        if(file_put_contents($absoluteFilePath, $data, FILE_APPEND|LOCK_EX)) {
            $isSuccess = true;
        }

        return $isSuccess;
    }

    /**
     * @param $configuration
     * @throws FileOperationErrorException
     */
    protected function initFileIfNotExist($configuration)
    {
        if (empty($this->tempFile) || !file_exists($this->tempFile)) {
            $this->tempFile = $this->createAnonymTempFile();

            if (isset($this->tempFile) && file_exists($this->tempFile)) {
                $head = $this->getFileHeader($configuration);
                $head .= '<'.$this->getRootNodeName($configuration).'>';
                $this->writeData($head);
            }
        }
    }

    /**
     * @return string
     * @throws FileOperationErrorException
     */
    protected function createAnonymTempFile()
    {
        return $this->createTempFile(md5(uniqid(time())));
    }

    /**
     * return absolute path of the temp file
     *
     * @param $fileName
     * @return string
     * @throws FileOperationErrorException
     */
    protected function createTempFile($fileName)
    {
        $basicFileUtility = $this->objectManager->get('TYPO3\CMS\Core\Utility\File\BasicFileUtility');
        $tempRelativePath = 'typo3temp/'.$GLOBALS['_EXTKEY'];
        $absPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($tempRelativePath);

        if (!file_exists($absPath)) {
            if (!GeneralUtility::mkdir($absPath)) {
                throw new FileOperationErrorException(
                    'can\'t create temp folder: \''. $tempRelativePath .'\''
                );
            }
        }
        $absFileName = $basicFileUtility->getUniqueName($fileName, $absPath);
        if (!touch($absFileName)) {
            throw new FileOperationErrorException(
                'can\'t create new temp file: \''.$absFileName .'\''
            );
        }
        return $absFileName;
    }

    /**
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        return true;
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * @param array $configuration
     */
    public function setConfiguration(array $configuration)
    {
        $this->config = $configuration;
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