<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence;

use CPSIT\ImportExportCore\ConfigurableInterface;
use CPSIT\ImportExportCore\ConfigurableTrait;
use CPSIT\ImportExportCore\Domain\Model\DataStreamInterface;
use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\ImportExportCore\Stream\TemporaryStream;
use CPSIT\T3importExport\Domain\Model\Dto\FileInfo;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

class DataTargetFileStream extends DataTargetRepository implements ConfigurableInterface
{
    use ConfigurableTrait;

    /**
     * @deprecated since 4.0, will be removed in 5.0. Temporary files are
     * written into `getTemporaryDirectory()` below the var directory.
     */
    final public const string TEMP_DIRECTORY = 'typo3temp/tx_importexport_';

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
     * Bytes read from a data stream at once
     */
    final public const int CHUNK_SIZE = 8192;

    /**
     * @param array|DomainObjectInterface $object
     * @throws FileOperationErrorException
     * @throws \RuntimeException if the stream of a data stream is not readable
     */
    #[\Override]
    public function persist($object, ?array $configuration = null): mixed
    {
        if ($object instanceof DataStreamInterface) {
            $stream = $object->getStream();
            if (!$stream->isReadable()) {
                throw new \RuntimeException(
                    'The stream of the data stream ' . $object::class . ' is not readable',
                    1795500101
                );
            }
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
            $written = false;
            while (!$stream->eof()) {
                $chunk = $stream->read(self::CHUNK_SIZE);
                if ($chunk === '') {
                    break;
                }
                $this->writeBuffer($chunk);
                $written = true;
            }
            if (!$written) {
                $this->writeBuffer('');
            }
            if (isset($configuration['flush'])) {
                $stream->close();
                $object->setStream(new TemporaryStream());
            }
        }

        return null;
    }

    /**
     * @param array|null $result
     * @param array|\Iterator|null $configuration
     */
    #[\Override]
    public function persistAll($result = null, ?array $configuration = null)
    {
        if (
            !is_null($result)
            && $result instanceof TaskResult
        ) {
            $result->rewind();
            if ($result->valid() && !empty($this->tempFile)) {
                $fileInfo = GeneralUtility::makeInstance(FileInfo::class, $this->tempFile);

                $result->setInfo($fileInfo);
            }
        }
    }

    /**
     * @throws FileOperationErrorException
     */
    protected function writeBuffer($buffer)
    {
        if (empty($this->tempFile) || !file_exists($this->tempFile)) {
            $this->tempFile = $this->createAnonymTempFile();
        }

        // file put content
        if (file_put_contents($this->tempFile, $buffer, FILE_APPEND | LOCK_EX) === false) {
            throw new FileOperationErrorException(
                'can\'t write in temp file: \'' . $this->tempFile . '\''
            );
        }
    }

    /**
     * @return string
     * @throws FileOperationErrorException
     */
    protected function createAnonymTempFile()
    {
        return $this->createTempFile(md5(uniqid((string)time(), true)));
    }

    /**
     * return absolute path of the temp file
     *
     * @return string
     * @throws FileOperationErrorException
     */
    protected function createTempFile($fileName)
    {
        /** @var BasicFileUtility $basicFileUtility */
        $basicFileUtility = GeneralUtility::makeInstance(BasicFileUtility::class);
        $absPath = $this->getTemporaryDirectory();

        if (!file_exists($absPath)) {
            try {
                GeneralUtility::mkdir_deep($absPath);
            } catch (\RuntimeException $exception) {
                throw new FileOperationErrorException(
                    'can\'t create temp folder: \'' . $absPath . '\'',
                    1795300401,
                    $exception
                );
            }
        }

        $absFileName = $basicFileUtility->getUniqueName($fileName, $absPath);
        if (!touch($absFileName)) {
            throw new FileOperationErrorException(
                'can\'t create new temp file: \'' . $absFileName . '\''
            );
        }
        return $absFileName;
    }

    /**
     * Directory of the temporary files: `transient/t3import_export/` of the var
     * directory, outside of the public directory in Composer installations
     *
     * @return string absolute path with trailing slash
     */
    protected function getTemporaryDirectory(): string
    {
        return Environment::getVarPath() . '/transient/t3import_export/';
    }

    public function isConfigurationValid(array $configuration): bool
    {
        return true;
    }
}
