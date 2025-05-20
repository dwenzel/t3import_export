<?php

declare(strict_types=1);

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

use CPSIT\T3importExport\InvalidColumnMapException;
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\MissingClassException;
use CPSIT\T3importExport\Service\TranslationService;
use CPSIT\T3importExport\Validation\Configuration\TranslateObjectConfigurationValidator;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Class TranslateObject
 * Translates
 */
class TranslateObject extends AbstractPostProcessor implements PostProcessorInterface
{
    public function __construct(
        protected PersistenceManagerInterface $persistenceManager,
        protected TranslationService $translationService,
        protected TranslateObjectConfigurationValidator $configurationValidator
    ) {}

    /**
     * Tells whether a given configuration is valid
     *
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     */
    #[\Override]
    public function isConfigurationValid(array $configuration): bool
    {
        return $this->configurationValidator->isValid($configuration);
    }

    /**
     * Finds the localization parent of the converted record
     * and translates it (adding the converted record as translation)
     *
     * @throws InvalidColumnMapException
     */
    public function process(array $configuration, mixed &$convertedRecord, array &$record): bool
    {
        $targetType = $convertedRecord::class;

        if (!isset($record[$configuration['parentField']])) {
            return false;
        }
        $identity = $record[$configuration['parentField']];

        //Translate only if parent set and parent found by identity
        $parentObject = $this->translationService->getLocalizationParent($identity, $targetType);

        if ($parentObject instanceof DomainObjectInterface) {
            $this->translationService->translate(
                $parentObject,
                $convertedRecord,
                (int)$configuration['language']
            );

            return true;
        }

        return false;
    }
}
