<?php
namespace CPSIT\T3importExport\Tests\PreProcessor;


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
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class XMLMapperTest
 *
 * @package CPSIT\T3importExport\Tests\Service\PreProcessor
 * @coversDefaultClass \CPSIT\T3importExport\Component\PreProcessor\XMLMapper
 */
class XMLMapperTest extends UnitTestCase
{
	/**
	 * @var \CPSIT\T3importExport\Component\PreProcessor\XMLMapper
	 */
	protected $subject;

	public function setUp()
	{
		$this->subject = $this->getAccessibleMock('CPSIT\\T3importExport\\Component\\PreProcessor\\XMLMapper',
			['dummy'], [], '', FALSE);
	}

	/**
	 * @test
	 */
	public function configurationIsEmpty()
	{
		$testConfig = [];

		$this->assertFalse(
			$this->subject->isConfigurationValid($testConfig)
		);
	}

	/**
	 * @test
	 */
	public function configurationIsInvalid()
	{
		$testConfig = [
			'foo' => 'bar',
			'bar' => []
		];

		// required field 'fields'
		$this->assertFalse(
			$this->subject->isConfigurationValid($testConfig)
		);


		$testConfig = [
			'fields' => [],
			'otherShit' => true
		];

		// accept empty fields array
		$this->assertTrue(
			$this->subject->isConfigurationValid($testConfig)
		);

		$testConfig['fields'] = [
			'foo' => true,
			'stuff' => '@something'
		];

		$this->assertFalse(
			$this->subject->isConfigurationValid($testConfig)
		);
	}

	/**
	 * @test
	 */
	public function configurationIsValid()
	{
		$testConfig = [
			'fields' => [
				'foo' => '@attribute',
				'mapTo' => 'customName',
				'staticSub' => [
					'foo' => '@attribute',
					'mapTo' => 'staticArray'
				],
				'manyChildren' => [
					'children' => [
						'mapTo' => 'child',
						'id' => '@attribute'
					],
					'mapTo' => 'mChildren'
				]
			]
		];

		$this->assertTrue(
			$this->subject->isConfigurationValid($testConfig)
		);

		$testSimpleUseDefaults = [
			'fields' => [
				'manyChildren' => [
					'children' => [
						'id' => '@attribute'
					]
				]
			]
		];

		$this->assertTrue(
			$this->subject->isConfigurationValid($testSimpleUseDefaults)
		);
	}

	/**
	 * @test
	 */
	public function processWithSimpleValidConfig()
	{
		$testSimpleArray = [
			'id' => 123,
			'manyChildren' => [
				[
					'id' => 1,
					'foo' => 'bar'
				],
				[
					'id' => 2,
					'foo' => 'bar'
				]
			]
		];

		$testConfigSimpleUseDefaults = [
			'fields' => [
				'manyChildren' => [
					'children' => [
						'id' => '@attribute'
					]
				]
			]
		];

		$expectedSimpleResult = [
			'id' => 123,
			'manyChildren' => [
				[
					'@attribute' => [
						'id' => 1
					],
					'foo' => 'bar'
				],
				[
					'@attribute' => [
						'id' => 2
					],
					'foo' => 'bar'
				]
			]
		];

		$this->assertTrue(
			$this->subject->isConfigurationValid($testConfigSimpleUseDefaults)
		);
		$this->subject->process($testConfigSimpleUseDefaults, $testSimpleArray);
		$this->assertEquals($expectedSimpleResult, $testSimpleArray);
	}

	/**
	 * @test
	 */
	public function processWithComplexValidConfig()
	{
		$testArray = [
			'id' => 123,
			'foo' => 'bar',
			'staticArray' => [
				'id' => 1,
				'foo' => 'bar'
			],
			'manyChildren' => [
				[
					'id' => 1,
					'foo' => 'bar',
					'field' => 'value'
				],
				[
					'id' => 2,
					'foo' => 'bar',
					'field' => 'value'
				]
			]
		];

		$testConfig = [
			'fields' => [
				'mapTo' => 'parent',
				'foo' => '@attribute',
				'staticArray' => [
					'id' => '@attribute',
					'foo' => '@attribute',
					'mapTo' => 'staticField'
				],
				'manyChildren' => [
					'children' => [
						'id' => '@attribute',
						'foo' => '@attribute',
						'mapTo' => 'child'
					],
					'mapTo' => 'subParent',
				]
			]
		];

		$expectedResult = [
			'id' => 123,
			'@mapTo' => 'parent',
			'@attribute' => [
				'foo' => 'bar'
			],
			'staticArray' => [
				'@attribute' => [
					'id' => 1,
					'foo' => 'bar'
				],
				'@mapTo' => 'staticField',
			],
			'manyChildren' => [
				'@mapTo' => 'subParent',
				[
					'@attribute' => [
						'id' => 1,
						'foo' => 'bar'
					],
					'field' => 'value',
					'@mapTo' => 'child'
				],
				[
					'@attribute' => [
						'id' => 2,
						'foo' => 'bar'
					],
					'field' => 'value',
					'@mapTo' => 'child'
				]
			]
		];

		$this->assertTrue(
			$this->subject->isConfigurationValid($testConfig)
		);
		$this->subject->process($testConfig, $testArray);
		$this->assertEquals($expectedResult, $testArray);
	}
}
