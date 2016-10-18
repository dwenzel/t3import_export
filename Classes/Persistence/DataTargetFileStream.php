<?php

namespace CPSIT\T3importExport\Persistence;


use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\Domain\Model\DataStreamInterface;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use CPSIT\T3importExport\Domain\Model\TaskResult;

class DataTargetFileStream extends DataTargetRepository implements DataTargetInterface, ConfigurableInterface
{
    const TEMP_DIRECTORY = 'typo3temp/tx_importexport_';

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
        if ($object instanceof DataStreamInterface) {
            $this->writeBuffer($object->getSteamBuffer());
            if (isset($configuration['flush'])) {
                $object->setSteamBuffer(null);
            }
        }
    }

    /**
     * @param array|null $result
     * @param array|\Iterator|null $configuration
     * @return void
     */
    public function persistAll($result = null, array $configuration = null)
    {
        if (
            !is_null($result)
            && $result instanceof TaskResult
        ) {
            $result->rewind();
            if ($result->valid()) {
                $result->setInfo($this->tempFile);
            }
        }
    }

    /**
     * @param $buffer
     * @return void
     * @throws FileOperationErrorException
     */
    protected function writeBuffer($buffer)
    {
        if (empty($this->tempFile) || !file_exists($this->tempFile)) {
            $this->tempFile = $this->createAnonymTempFile();
        }

        // file put content
        if (file_put_contents($this->tempFile, $buffer, FILE_APPEND|LOCK_EX) === false) {
            throw new FileOperationErrorException(
                'can\'t write in temp file: \''. $this->tempFile .'\''
            );
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
        /** @var BasicFileUtility $basicFileUtility */
        $basicFileUtility = $this->objectManager->get('TYPO3\CMS\Core\Utility\File\BasicFileUtility');
        $absPath = GeneralUtility::getFileAbsFileName(static::TEMP_DIRECTORY);

        if (!file_exists($absPath)) {
            if (!GeneralUtility::mkdir($absPath)) {
                throw new FileOperationErrorException(
                    'can\'t create temp folder: \''. static::TEMP_DIRECTORY.'\''
                );
            }
        }
        // todo replace deprecated (TYPO3\CMS\Core\Resource\ResourceStorage->getUniqueName)
        $absFileName = $basicFileUtility->getUniqueName($fileName, $absPath);
        if (!touch($absFileName)) {
            throw new FileOperationErrorException(
                'can\'t create new temp file: \''.$absFileName .'\''
            );
        }
        return $absFileName;
    }

    public function isConfigurationValid(array $configuration)
    {
        return true;
    }

    public function getConfiguration()
    {
        return $this->config;
    }

    public function setConfiguration(array $configuration)
    {
        $this->config = $configuration;
    }
}
