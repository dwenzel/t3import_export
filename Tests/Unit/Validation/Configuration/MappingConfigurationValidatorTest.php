<?php

namespace CPSIT\T3importExport\Tests\Validation\Configuration;

use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\Validation\Configuration\MappingConfigurationValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
class MappingConfigurationValidatorTest extends TestCase
{

    /**
     * @var MappingConfigurationValidator | MockObject
     */
    protected MappingConfigurationValidator $subject;

    /**
     * set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function setUp()
    {
        $this->subject = new MappingConfigurationValidator();
    }

    public function testValidateThrowsExceptionIfAllowPropertiesIsNotString(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1451146869);
        $configuration = [
            'allowProperties' => []
        ];
        $this->subject->isValid($configuration);
    }

    public function testValidateThrowsExceptionIfPropertiesIsNotArray(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1451147517);
        $configuration = [
            'properties' => 'invalidStringValue'
        ];
        $this->subject->isValid($configuration);
    }

    public function testValidateValidatedPropertiesRecursive(): void
    {
        $this->subject = $this->getMockBuilder(MappingConfigurationValidator::class)
            ->setMethods(['validatePropertyConfigurationRecursive'])
            ->getMock();

        $configuration = [
            'properties' => [
                'propertyA' => [
                    'allowAllProperties' => 1
                ]
            ]
        ];

        $this->subject->expects($this->once())
            ->method('validatePropertyConfigurationRecursive')
            ->with($configuration['properties']['propertyA']);

        $this->subject->isValid($configuration);
    }

    public function testValidatePropertyConfigurationThrowsExceptionIfMaxItemsIsNotSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1451157586);
        $configuration = [
            'properties' => [
                'foo' => [
                    'children' => [
                        'propertyA' => ['allowAllProperties' => 1]
                    ]
                ]
            ]
        ];
        $this->subject->isValid($configuration);
    }

    public function testValidatePropertyConfigurationRecursiveDoesRecur(): void
    {
        $configuration = [
            'properties' => [
                'propertyA' => [
                    'children' => [
                        'maxItems' => 1,
                        'properties' => [
                            'propertyB' => [
                                'allowAllProperties' => 1
                            ]
                        ]
                    ]
                ]

            ]
        ];

        self::assertTrue(
            $this->subject->isValid($configuration)
        );
    }

}
