<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\PostProcessor;

use CPSIT\T3importExport\Component\PostProcessor\TranslateObject;
use CPSIT\T3importExport\Service\TranslationService;
use CPSIT\T3importExport\Validation\Configuration\MappingConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TargetClassConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TranslateObjectConfigurationValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

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
class TranslateObjectTest extends TestCase
{
    protected TranslateObject $subject;

    /**
     * @var MockObject|TargetClassConfigurationValidator
     */
    protected TargetClassConfigurationValidator|MockObject $targetClassConfigurationValidator;

    /**
     * @var MappingConfigurationValidator|MockObject
     */
    protected MappingConfigurationValidator|MockObject $mappingConfigurationValidator;

    /**
     * @var MockObject|PersistenceManagerInterface
     */
    protected PersistenceManagerInterface|MockObject $persistenceManager;

    /**
     * @var MockObject | TranslationService
     */
    protected TranslationService|MockObject $translationService;

    /**
     * @var MockObject|TranslateObjectConfigurationValidator
     */
    protected TranslateObjectConfigurationValidator|MockObject $configurationValidator;

    /**
     * set up
     */
    protected function setUp(): void
    {
        $this->persistenceManager = $this->getMockBuilder(PersistenceManagerInterface::class)
            ->getMock();
        $this->translationService = $this->getMockBuilder(TranslationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLocalizationParent', 'translate'])
            ->getMock();
        $this->configurationValidator = $this->getMockBuilder(TranslateObjectConfigurationValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isValid'])
            ->getMock();

        $this->subject = new TranslateObject(
            $this->persistenceManager,
            $this->translationService,
            $this->configurationValidator
        );
    }

    /**
     * @throws \CPSIT\ImportExportCore\Exception\InvalidConfigurationException
     * @throws \CPSIT\T3importExport\MissingClassException
     */
    #[Test]
    public function isConfigurationValidReturnsFalseFromValidator(): void
    {
        $config = ['bar' => 'foo'];
        $this->configurationValidator->expects($this->once())
            ->method('isValid')
            ->with($config)
            ->willReturn(false);
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @throws \CPSIT\ImportExportCore\Exception\InvalidConfigurationException
     * @throws \CPSIT\T3importExport\MissingClassException
     */
    #[Test]
    public function isConfigurationValidReturnsTrueFromValidator(): void
    {
        $config = ['bar' => 'foo'];
        $this->configurationValidator->expects($this->once())
            ->method('isValid')
            ->with($config)
            ->willReturn(true);
        $this->assertTrue(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @throws \CPSIT\T3importExport\InvalidColumnMapException
     */
    #[Test]
    public function processConvertsParentIfParentFieldIsSet(): void
    {
        $identity = 1;
        $config = [
            'language' => '1',
            'parentField' => 'foo',
        ];
        $record = [
            'foo' => $identity,
        ];

        $targetClass = DomainObjectInterface::class;
        /** @var DomainObjectInterface|MockObject $convertedRecord */
        $convertedRecord = $this->getMockBuilder($targetClass)->getMock();
        $parentObject = $this->getMockBuilder($targetClass)->getMock();

        $expectedTargetClass = $convertedRecord::class;

        $this->translationService->expects($this->once())
            ->method('getLocalizationParent')
            ->with($identity, $expectedTargetClass)
            ->willReturn($parentObject);

        $this->subject->process(
            $config,
            $convertedRecord,
            $record
        );
    }
}
