<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\PostProcessor;

use CPSIT\T3importExport\Component\PostProcessor\TranslateObject;
use CPSIT\T3importExport\InvalidColumnMapException;
use CPSIT\T3importExport\Service\TranslationService;
use CPSIT\T3importExport\Validation\Configuration\MappingConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TargetClassConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TranslateObjectConfigurationValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

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
     * @var TargetClassConfigurationValidator|MockObject
     */
    protected TargetClassConfigurationValidator $targetClassConfigurationValidator;

    /**
     * @var MappingConfigurationValidator|MockObject
     */
    protected MappingConfigurationValidator $mappingConfigurationValidator;

    /**
     * @var PersistenceManagerInterface|MockObject
     */
    protected PersistenceManagerInterface $persistenceManager;

    /**
     * @var TranslationService | MockObject
     */
    protected TranslationService $translationService;

    /**
     * @var TranslateObjectConfigurationValidator|MockObject
     */
    protected TranslateObjectConfigurationValidator $configurationValidator;

    /**
     * set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function setUp(): void
    {
        $this->persistenceManager = $this->getMockForAbstractClass(PersistenceManagerInterface::class);
        $this->translationService = $this->getMockBuilder(TranslationService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLocalizationParent', 'translate'])
            ->getMock();
        $this->configurationValidator = $this->getMockBuilder(TranslateObjectConfigurationValidator::class)
            ->setMethods(['isValid'])
            ->getMock();

        $this->subject = new TranslateObject(
            $this->persistenceManager,
            $this->translationService,
            $this->configurationValidator
        );
    }


    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseFromValidator(): void
    {
        $config = ['bar' => 'foo'];
        $this->configurationValidator->expects(self::once())
            ->method('isValid')
            ->with($config)
            ->willReturn(false);
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsTrueFromValidator(): void
    {
        $config = ['bar' => 'foo'];
        $this->configurationValidator->expects(self::once())
            ->method('isValid')
            ->with($config)
            ->willReturn(true);
        $this->assertTrue(
            $this->subject->isConfigurationValid($config)
        );
    }


    /**
     * @test
     * @throws ReflectionException
     * @throws InvalidColumnMapException
     */
    public function processConvertsParentIfParentFieldIsSet(): void
    {
        $identity = 1;
        $config = [
            'language' => '1',
            'parentField' => 'foo'
        ];
        $record = [
            'foo' => $identity
        ];

        $targetClass = DomainObjectInterface::class;
        /** @var DomainObjectInterface|MockObject $convertedRecord */
        $convertedRecord = $this->getMockForAbstractClass($targetClass);
        $parentObject = $this->getMockForAbstractClass($targetClass);

        $expectedTargetClass = $convertedRecord::class;

            $this->translationService->expects($this->once())
            ->method('getLocalizationParent')
            ->with(...[$identity, $expectedTargetClass])
            ->willReturn($parentObject);

        $this->subject->process(
            $config,
            $convertedRecord,
            $record
        );
    }
}
