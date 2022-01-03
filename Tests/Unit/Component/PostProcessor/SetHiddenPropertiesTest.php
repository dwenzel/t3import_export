<?php
namespace CPSIT\T3importExport\Tests\Unit\Component\PostProcessor;

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

    /**
     * @var \CPSIT\T3importExport\Component\PostProcessor\SetHiddenProperties
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = $this->getAccessibleMock('CPSIT\\T3importExport\\Component\\PostProcessor\\SetHiddenProperties',
            ['dummy'], [], '', false);
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsInitiallyFalse()
    {
        $mockConfiguration = ['foo'];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfFieldsIsNotArray()
    {
        $config = [
            'fields' => 'foo'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($config)
        );
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfFieldValueIsNotString()
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
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfFieldValueIsEmpty()
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
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsTrueForValidConfiguration()
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
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfChildrenIsNotArray()
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

    /**
     * @test
     */
    public function processSetsExistingHiddenField()
    {
        $fieldName = 'languageUid';
        $config = [
            'fields' => [
                'languageUid' => 1
            ],
        ];
        $record = [
            $fieldName => 1
        ];
        $convertedRecord = $this->getAccessibleMockForAbstractClass(
            AbstractDomainObject::class
        );
        $this->subject->process($config, $convertedRecord, $record);

        $this->assertSame(
            $convertedRecord->_getProperty('_' . $fieldName), $record[$fieldName]
        );
    }

    /**
     * @test
     */
    public function processSetsPropertiesRecursive()
    {
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
        $convertedRecord = $this->getAccessibleMock(
            AbstractDomainObject::class,
            ['_hasProperty', '_getProperty']
        );
        $convertedRecord->expects($this->any())
            ->method('_hasProperty')
            ->will($this->returnValue(true));
        $convertedRecord->expects($this->once())
            ->method('_getProperty');

        $this->subject->process($config, $convertedRecord, $record);
    }

    /**
     * @test
     */
    public function processSetsPropertiesRecursiveWithSubAbstractDomainObject()
    {
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
        $convertedRecord = $this->getAccessibleMock(
            AbstractDomainObject::class,
            ['_hasProperty', '_getProperty']
        );
        $convertedRecordChild = $this->getAccessibleMock(
            AbstractDomainObject::class,
            ['_hasProperty', '_setProperty']
        );

        $convertedRecord->expects($this->any())
            ->method('_hasProperty')
            ->will($this->returnValue(true));
        $convertedRecord->expects($this->once())
            ->method('_getProperty')
            ->willReturn($convertedRecordChild);

        $convertedRecordChild->expects($this->once())
            ->method('_setProperty')
            ->with(
                $this->equalTo('_'.$fieldName),
                $this->equalTo($record[$fieldName])
            )
            ->will($this->returnValue(true));

        $this->subject->process($config, $convertedRecord, $record);
    }

    /**
     * @test
     */
    public function processSetsPropertiesRecursiveWithChildAbstractDomainObject()
    {
        $fieldName = 'languageUid';
        $config = [
            'fields' => [
                'languageUid' => 1
            ],
            'children' => [
                'languageUid' => 1
            ]
        ];
        $record = [
            $fieldName => 1
        ];
        $convertedRecord = $this->getAccessibleMock(
            AbstractDomainObject::class,
            ['_hasProperty', '_getProperty']
        );

        $convertedRecordChild = $this->getAccessibleMock(
            AbstractDomainObject::class,
            ['_hasProperty', '_setProperty']
        );

        $objStorage = new ObjectStorage();
        $objStorage->attach($convertedRecordChild);

        $convertedRecord->expects($this->any())
            ->method('_hasProperty')
            ->will($this->returnValue(true));
        $convertedRecord->expects($this->once())
            ->method('_getProperty')
            ->willReturn($objStorage);

        $convertedRecordChild->expects($this->once())
            ->method('_setProperty')
            ->with(
                $this->equalTo('_'.$fieldName),
                $this->equalTo($record[$fieldName])
            )
            ->will($this->returnValue(true));

        $this->subject->process($config, $convertedRecord, $record);
    }
}
