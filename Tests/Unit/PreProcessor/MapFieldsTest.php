<?php
namespace CPSIT\T3import\Tests\PreProcessor;

use TYPO3\CMS\Core\Tests\Unit\Resource\BaseTestCase;

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
 * Class MapFieldsTest
 *
 * @package CPSIT\T3import\Tests\Service\PreProcessor
 * @coversDefaultClass \CPSIT\T3import\Component\PreProcessor\MapFields
 */
class MapFieldsTest extends BaseTestCase {

	/**
	 * @var \CPSIT\T3import\Component\PreProcessor\MapFields
	 */
	protected $subject;

	public function setUp() {
		$this->subject = $this->getAccessibleMock('CPSIT\\T3import\\Component\\PreProcessor\\MapFields',
			['dummy'], [], '', FALSE);
	}

	/**
	 * @test
	 * @covers ::isConfigurationValid
	 */
	public function isConfigurationValidReturnsInitiallyFalse() {
		$mockConfiguration = ['foo'];
		$this->assertFalse(
			$this->subject->isConfigurationValid($mockConfiguration)
		);
	}

	/**
	 * @test
	 * @covers ::isConfigurationValid
	 */
	public function isConfigurationValidReturnsFalseIfFieldsIsNotArray() {
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
	public function isConfigurationValidReturnsFalseIfFieldValueIsNotString() {
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
	public function isConfigurationValidReturnsFalseIfFieldValueIsEmpty() {
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
	public function isConfigurationValidReturnsTrueForValidConfiguration() {
		$config = [
			'fields' => [
				'foo' => 'bar',
				'baz' => 'fooBar'
			]
		];
		$this->assertTrue(
			$this->subject->isConfigurationValid($config)
		);
	}

	/**
	 * @test
	 */
	public function processMapsFields() {
		$config = [
			'fields' => [
				'firstSourceField' => 'firstTargetField',
				'secondSourceField' => 'secondTargetField'
			]
		];
		$record = [
			'firstSourceField' => 'firstValue',
			'secondSourceField' => 'secondValue'
		];
		$expectedResult = [
			'firstSourceField' => 'firstValue',
			'secondSourceField' => 'secondValue',
			'firstTargetField' => 'firstValue',
			'secondTargetField' => 'secondValue'
		];
		$this->subject->process($config, $record);

		$this->assertEquals(
			$expectedResult,
			$record
		);
	}
}
