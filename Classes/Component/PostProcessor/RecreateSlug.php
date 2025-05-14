<?php

namespace CPSIT\T3importExport\Component\PostProcessor;


use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use CPSIT\T3importExport\Utility\TcaUtility;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\Model\RecordState;
use TYPO3\CMS\Core\DataHandling\SlugHelper;

/**
 * Class RecreateSlug
 * Generates a slug for a given record.
 */
class RecreateSlug extends AbstractPostProcessor implements PostProcessorInterface
{
    public const KEY_TABLE_NAME = 'tableName';
    public const KEY_SLUG_FIELD = 'slugField';
    public const KEY_SITE_ID = 'siteId';

    public const DEFAULT_TABLE_NAME = 'pages';
    public const DEFAULT_SLUG_FIELD = 'slug';
    public const DEFAULT_SITE_ID = 1;

    public function __construct()
    {

    }

    /**
     * @param array $configuration
     * @return bool
     */
    #[\Override]
    public function isConfigurationValid(array $configuration): bool
    {
        if (!empty($configuration[self::KEY_TABLE_NAME])
            && !is_string($configuration[self::KEY_TABLE_NAME])) {
            return false;
        }
        if (!empty($configuration[self::KEY_SLUG_FIELD])
            && !is_string($configuration[self::KEY_SLUG_FIELD])) {
            return false;
        }
        if (!empty($configuration[self::KEY_SITE_ID])
            && !MathUtility::canBeInterpretedAsInteger($configuration[self::KEY_SITE_ID])) {
            return false;
        }


        return true;
    }

    /**
     * @param array $configuration
     * @param AbstractDomainObject $convertedRecord
     * @param array $record
     * @return TRUE
     */
    public function process(array $configuration, &$convertedRecord, array &$record): bool
    {
        $tableName = $configuration[self::KEY_TABLE_NAME] ?? self::DEFAULT_TABLE_NAME;
        $slugField = $configuration[self::KEY_SLUG_FIELD] ?? self::DEFAULT_SLUG_FIELD;
        $siteId = $configuration[self::KEY_SITE_ID] ?? self::DEFAULT_SITE_ID;

        $convertedRecord[$slugField] = $this->recreateSlug($convertedRecord, $tableName, $slugField);

        return true;
    }

    /**
     * @param array $row
     * @param string $tableName
     * @return void
     * @throws SiteNotFoundException
     */
    public function recreateSlug(array $row, string $tableName, $slugField): string
    {
        $fieldConfig = TcaUtility::getTcaOfField($slugField, $tableName)['config'];

        $slugHelper = GeneralUtility::makeInstance(
            SlugHelper::class,
            $tableName,
            $slugField,
            $fieldConfig
        );
        $slug = $slugHelper->generate($row, $row['pid']);
        $recordState = $this->getRecordState($row, $tableName);
        return $slugHelper->buildSlugForUniqueInSite($slug, $recordState);
    }

    /**
     * @param array $row tx_extension_domain_model_anything.*
     * @param string $tableName tx_extension_domain_model_anything
     * @return RecordState
     */
    protected function getRecordState(array $row, string $tableName): RecordState
    {
        return GeneralUtility::makeInstance(RecordStateFactory::class, $tableName)
            ->fromArray($row);
    }

}
