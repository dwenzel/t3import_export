<?php
namespace CPSIT\T3importExport\Tests\Validation\Configuration;

use CPSIT\T3importExport\Validation\Configuration\TargetClassConfigurationValidator;
use TYPO3\CMS\Core\Tests\UnitTestCase;

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
class TargetClassConfigurationValidatorTest extends UnitTestCase
{

    /**
     * @var TargetClassConfigurationValidator
     */
    protected $subject;

    /**
     * set up
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            TargetClassConfigurationValidator::class,
            ['dummy']
        );
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
     * @expectedExceptionCode 1451146126
     */
    public function validateThrowsExceptionIfTargetClassIsNotSet()
    {
        $configuration = ['foo'];
        $this->subject->validate($configuration);
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
     * @expectedExceptionCode 1451146384
     */
    public function validateThrowsExceptionIfTargetClassIsNotString()
    {
        $configuration = [
            'targetClass' => 1
        ];
        $this->subject->validate($configuration);
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\MissingClassException
     * @expectedExceptionCode 1451146564
     */
    public function validateThrowsExceptionIfTargetClassDoesNotExist()
    {
        $configuration = [
            'targetClass' => 'NonExistingClassName'
        ];
        $this->subject->validate($configuration);
    }

    /**
     * @test
     */
    public function validateReturnsTrueForValidConfiguration()
    {
        $existingClassName = \stdClass::class;
        $validConfiguration = [
            'targetClass' => $existingClassName
        ];

        $this->assertTrue(
            $this->subject->validate($validConfiguration)
        );
    }
}
