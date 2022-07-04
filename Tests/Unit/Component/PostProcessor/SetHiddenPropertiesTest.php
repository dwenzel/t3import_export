<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\PostProcessor;

use CPSIT\T3importExport\Component\PostProcessor\SetHiddenProperties;
use CPSIT\T3importExport\Tests\Unit\Fixtures\DummyDomainObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/***************************************************************
 *  Copyright notice
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
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

/**
 * Class SetHiddenPropertiesTest
 *
 * @package CPSIT\T3importExport\Tests\Service\PostProcessor
 * @coversDefaultClass \CPSIT\T3importExport\Component\PostProcessor\SetHiddenProperties
 */
class SetHiddenPropertiesTest extends TestCase
{

    protected SetHiddenProperties $subject;

    /**
     * @var AbstractDomainObject|MockObject
     */
    protected $domainObject;

    public function setUp()
    {
        $this->subject = new SetHiddenProperties();
        $this->domainObject = new DummyDomainObject();
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsInitiallyFalse(): void
    {
        $mockConfiguration = ['foo'];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfFieldsIsNotArray(): void
    {
        $config = [
            'fields' => 'foo'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfFieldValueIsNotString(): void
    {
        $config = [
            'fields' => [
                'foo' => 0
            ]
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfFieldValueIsEmpty(): void
    {
        $config = [
            'fields' => [
                'foo' => ''
            ]
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $config = [
            'fields' => [
                'foo' => 'bar',
                'baz' => 1,
                'fooBar' => ['baz']
            ]
        ];
        $this->assertTrue(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfChildrenIsNotArray(): void
    {
        $config = [
            'fields' => [
                'foo' => 'bar',
            ],
            'children' => 'foo'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    public function testProcessSetsExistingHiddenField(): void
    {
        $domainObject = new DummyDomainObject();
        $fieldName = 'languageUid';
        $config = [
            'fields' => [
                'languageUid' => 1
            ],
        ];
        $record = [
            $fieldName => 1
        ];
        $this->subject->process($config, $domainObject, $record);

        $this->assertSame(
            $domainObject->_getProperty('_' . $fieldName), $record[$fieldName]
        );
    }

    public function testProcessSetsPropertiesRecursive(): void
    {
        $domainObject = $this->getMockBuilder(DummyDomainObject::class)
            ->setMethods(['_hasProperty', '_getProperty'])
            ->getMock();
        $childObject = $this->getMockBuilder(DummyDomainObject::class)
            ->setMethods(['_hasProperty', '_getProperty'])
            ->getMock();
        $fieldName = 'languageUid';
        $config = [
            'fields' => [
                'languageUid' => 1
            ],
            'children' => [
                'fooField' => 1
            ]
        ];
        $record = [
            $fieldName => 1
        ];
        $domainObject->expects($this->atLeastOnce())
            ->method('_hasProperty')
            ->willReturn(true);
        $domainObject->expects($this->once())
            ->method('_getProperty')
            ->with(...['fooField'])
            ->willReturn($childObject);

        $childObject->expects($this->atLeastOnce())
            ->method('_hasProperty')
            ->willReturn(true);

        $this->subject->process($config, $domainObject, $record);
    }


}
