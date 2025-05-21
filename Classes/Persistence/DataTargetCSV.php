<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use CPSIT\ImportExportCore\ConfigurableInterface;
use CPSIT\ImportExportCore\ConfigurableTrait;
use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\ImportExportCore\IdentifiableTrait;
use CPSIT\T3importExport\Resource\ResourceTrait;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use CPSIT\ImportExportCore\Persistence\DataTargetInterface;
/**
 * Class DataSourceCSV
 */
class DataTargetCSV implements DataTargetInterface, ConfigurableInterface
{
    use IdentifiableTrait;
    use ConfigurableTrait;
    use ResourceTrait;

    final public const string TEMP_DIRECTORY = 'typo3temp/tx_importexport_';

    protected static $characterProperties = ['delimiter', 'enclosure', 'escape'];

    /**
     * Tells if a given configuration is valid
     */
    public function isConfigurationValid(array $configuration): bool
    {
        if (isset($configuration['fields'])) {
            if (!is_string($configuration['fields']) || empty($configuration['fields'])) {
                return false;
            }
        }

        foreach (self::$characterProperties as $property) {
            if (isset($configuration[$property])) {
                $value = $configuration[$property];
                if (
                    !is_string($value)
                    || empty($value)
                    || strlen($value) != 1
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return array|array[]|mixed
     */
    public function persistAll($result = null, ?array $configuration = null)
    {
        if (empty($result)) {
            return false;
        }

        $delimiter = ',';
        $enclosure = '"';
        $escape = '\\';

        if (isset($configuration['delimiter'])) {
            $delimiter = $configuration['delimiter'];
        }
        if (isset($configuration['enclosure'])) {
            $enclosure = $configuration['enclosure'];
        }
        if (isset($configuration['escape'])) {
            $escape = $configuration['escape'];
        }

        /** @var TaskResult $result */
        if ($result instanceof TaskResult) {
            $records = $result->toArray();
        }

        $result->rewind();
        $fields = array_keys($result->current());

        $fileName = $configuration['file'];
        $absFileName = GeneralUtility::getFileAbsFileName($fileName);
        touch($absFileName);

        $output = fopen($absFileName, 'r+');
        fwrite($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        if (!$output) {
            throw new FileOperationErrorException(
                'can\'t create new temp file: \'' . $absFileName . '\''
            );
        }
        if (isset($configuration['fields'])) {
            $fields = GeneralUtility::trimExplode(',', $configuration['fields'], true);
            fputcsv($output, $fields, $delimiter, $enclosure, $escape);
        }

        foreach ($result as $record) {
            $row = array_intersect_key($record, array_flip($fields));
            fputcsv($output, $row, $delimiter, $enclosure, $escape);
        }

        fclose($output);

        return $records;
    }

    public function persist($result = null, ?array $configuration = null)
    {
        // do nothing
    }
}
