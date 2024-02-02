<?php
namespace CPSIT\T3importExport\Tests\Validation\Configuration;

use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\MissingClassException;
use CPSIT\T3importExport\Validation\Configuration\TargetClassConfigurationValidator;
use PHPUnit\Framework\TestCase;
use stdClass;

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
class TargetClassConfigurationValidatorTest extends TestCase
{

    protected TargetClassConfigurationValidator $subject;

    /**
     * set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function setUp(): void
    {
        $this->subject = new TargetClassConfigurationValidator();
    }

    public function testValidateThrowsExceptionIfTargetClassIsNotSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1_451_146_126);
        $configuration = ['foo'];
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->subject->isValid($configuration);
    }

    public function testValidateThrowsExceptionIfTargetClassIsNotString(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1_451_146_384);
        $configuration = [
            'targetClass' => 1
        ];
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->subject->isValid($configuration);
    }

    public function testValidateThrowsExceptionIfTargetClassDoesNotExist(): void
    {
        $this->expectException(MissingClassException::class);
        $this->expectExceptionCode(1_451_146_564);
        $configuration = [
            'targetClass' => 'NonExistingClassName'
        ];
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->subject->isValid($configuration);
    }

    /**
     * @test
     */
    public function validateReturnsTrueForValidConfiguration(): void
    {
        $existingClassName = stdClass::class;
        $validConfiguration = [
            'targetClass' => $existingClassName
        ];

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertTrue(
            $this->subject->isValid($validConfiguration)
        );
    }
}
