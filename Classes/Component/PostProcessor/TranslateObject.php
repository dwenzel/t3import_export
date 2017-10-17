<?php
namespace CPSIT\T3importExport\Component\PostProcessor;

/***************************************************************
 *  Copyright notice
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\Property\PropertyMappingConfigurationBuilder;
use CPSIT\T3importExport\Property\TypeConverter\PersistentObjectConverter;
use CPSIT\T3importExport\Service\TranslationService;
use CPSIT\T3importExport\Validation\Configuration\MappingConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TargetClassConfigurationValidator;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverterInterface;

/**
 * Class TranslateObject
 * Translates
 *
 * @package CPSIT\T3importExport\Component\PostProcessor
 */
class TranslateObject extends AbstractPostProcessor implements PostProcessorInterface
{

    /**
     * @var \CPSIT\T3importExport\Service\TranslationService
     */
    protected $translationService;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var PropertyMappingConfigurationBuilder
     */
    protected $propertyMappingConfigurationBuilder;

    /**
     * @var PropertyMappingConfiguration
     */
    protected $propertyMappingConfiguration;

    /**
     * @var TargetClassConfigurationValidator
     */
    protected $targetClassConfigurationValidator;

    /**
     * @var MappingConfigurationValidator
     */
    protected $mappingConfigurationValidator;

    /**
     * injects the property mapping configuration builder
     *
     * @param PropertyMappingConfigurationBuilder $propertyMappingConfigurationBuilder
     */
    public function injectPropertyMappingConfigurationBuilder(PropertyMappingConfigurationBuilder $propertyMappingConfigurationBuilder)
    {
        $this->propertyMappingConfigurationBuilder = $propertyMappingConfigurationBuilder;
    }

    /**
     * @param TranslationService $translationService
     */
    public function injectTranslationService(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * injects the TargetClassConfigurationValidator
     *
     * @param TargetClassConfigurationValidator $validator
     */
    public function injectTargetClassConfigurationValidator(TargetClassConfigurationValidator $validator)
    {
        $this->targetClassConfigurationValidator = $validator;
    }

    /**
     * injects the MappingConfigurationValidator
     *
     * @param MappingConfigurationValidator $validator
     */
    public function injectMappingConfigurationValidator(MappingConfigurationValidator $validator)
    {
        $this->mappingConfigurationValidator = $validator;
    }

    /**
     * @var \TYPO3\CMS\Extbase\Property\TypeConverterInterface
     */
    protected $typeConverter;

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
     */
    public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * @param PersistentObjectConverter $typeConverter
     */
    public function injectPersistentObjectConverter(PersistentObjectConverter $typeConverter)
    {
        $this->typeConverter = $typeConverter;
    }

    /**
     * Tells whether a given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        if (!isset($configuration['parentField'])
            || !isset($configuration['language'])) {
            return false;
        }

        if (isset($configuration['mapping'])) {
            $mappingConfiguration = $configuration['mapping'];
            if (isset($mappingConfiguration['targetClass'])
                && !$this->targetClassConfigurationValidator->validate($mappingConfiguration)) {
                return false;
            }
            if (isset($mappingConfiguration['config'])
                && !$this->mappingConfigurationValidator->validate($mappingConfiguration)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Finds the localization parent of the converted record
     * and translates it (adding the converted record as translation)
     *
     * @param array $configuration
     * @param DomainObjectInterface $convertedRecord
     * @param array $record
     * @return bool
     */
    public function process($configuration, &$convertedRecord, &$record)
    {
        $targetType = get_class($convertedRecord);

        //Translate only if parent set and parent found by identity
        if (isset($record[$configuration['parentField']])) {
            $identity = $record[$configuration['parentField']];

            $parentObject = $this->getLocalizationParent($identity, $targetType);

            if ($parentObject) {
                $this->translationService->translate(
                    $parentObject,
                    $convertedRecord,
                    (int)$configuration['language']
                );
            }
        }
    }

    /**
     * @param $identity
     * @param $targetType
     * @return object
     */
    protected function getLocalizationParent($identity, $targetType)
    {
        $query = $this->persistenceManager->createQueryForType($targetType);
        $querySettings = $query->getQuerySettings();

        $querySettings->setIgnoreEnableFields(true);
        $querySettings->setRespectStoragePage(false);
        $querySettings->setLanguageUid(0);
        $query->setQuerySettings($querySettings);
        $parentObject = $query->matching($query->equals('uid', $identity))->execute()->getFirst();

        return $parentObject;
    }
}
