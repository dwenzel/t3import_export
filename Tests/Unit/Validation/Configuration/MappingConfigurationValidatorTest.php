<?php
namespace CPSIT\T3importExport\Tests\Validation\Configuration;

use CPSIT\T3importExport\Validation\Configuration\MappingConfigurationValidator;
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
class MappingConfigurationValidatorTest extends UnitTestCase {

	/**
	 * @var MappingConfigurationValidator
	 */
	protected $subject;

	/**
	 * set up
	 */
	public function setUp() {
		$this->subject = $this->getAccessibleMock(
			MappingConfigurationValidator::class,
			['dummy']
		);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
	 * @expectedExceptionCode 1451146869
	 */
	public function validateThrowsExceptionIfAllowPropertiesIsNotString() {
		$configuration = [
			'allowProperties' => []
		];
		$this->subject->validate($configuration);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
	 * @expectedExceptionCode 1451147517
	 */
	public function validateThrowsExceptionIfPropertiesIsNotArray() {
		$configuration = [
			'properties' => 'invalidStringValue'
		];
		$this->subject->validate($configuration);
	}

	/**
	 * @test
	 */
	public function validateValidatedPropertiesRecursive() {
		$this->subject = $this->getAccessibleMock(
			MappingConfigurationValidator::class,
			['validatePropertyConfigurationRecursive']
		);
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

		$this->subject->validate($configuration);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
	 * @expectedExceptionCode 1451157586
	 */
	public function validatePropertyConfigurationRecursiveThrowsExceptionIfMaxItemsIsNotSet() {
		$configuration = [
			'children' => [
				'propertyA' => ['allowAllProperties' => 1]
			]
		];
		$this->subject->_call(
			'validatePropertyConfigurationRecursive',
			$configuration);
	}

	/**
	 * @test
	 */
	public function validatePropertyConfigurationRecursiveDoesRecur() {
		$this->subject = $this->getAccessibleMock(
			MappingConfigurationValidator::class,
			['validatePropertyConfiguration']
		);
		$configuration = [
			'children' => [
				'maxItems' => 1,
				'properties' => [
					'propertyA' => [
						'allowAllProperties' => 1
					]
				]
			]
		];
		$this->subject->expects($this->exactly(2))
			->method('validatePropertyConfiguration')
			->withConsecutive(
				[$configuration],
				[$configuration['children']['properties']['propertyA']]
			);
		$this->subject->_call(
			'validatePropertyConfigurationRecursive',
			$configuration);
	}
}
