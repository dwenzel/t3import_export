<?php
namespace CPSIT\T3import\Tests\PreProcessor;

use TYPO3\CMS\Core\Tests\Unit\Resource\BaseTestCase;
use TYPO3\CMS\Extbase\Service\TypoScriptService;

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
 * Class AbstractPreProcessorTest
 *
 * @package CPSIT\T3import\Tests\Service\PreProcessor
 * @coversDefaultClass \CPSIT\T3import\PreProcessor\AbstractPreProcessor
 */
class AbstractPreProcessorTest extends BaseTestCase {

	/**
	 * @var \CPSIT\T3import\PreProcessor\AbstractPreProcessor
	 */
	protected $subject;

	public function setUp() {
		$this->subject = $this->getAccessibleMockForAbstractClass(
			'CPSIT\\T3import\\PreProcessor\\AbstractPreProcessor',
			[], '', FALSE);
	}

	/**
	 * @test
	 * @covers ::isConfigurationValid
	 */
	public function isConfigurationValidReturnsAlwaysTrue() {
		$mockConfiguration = ['foo'];
		$this->assertTrue(
			$this->subject->isConfigurationValid($mockConfiguration)
		);
	}

	/**
	 * @test
	 * @cover ::injectContentObjectRenderer
	 */
	public function injectContentObjectRendererSetsContentObjectRenderer() {
		$mockContentObjectRenderer = $this->getMock(
			'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer'
		);

		$this->subject->injectContentObjectRenderer($mockContentObjectRenderer);
		$this->assertSame(
			$mockContentObjectRenderer,
			$this->subject->_get('contentObjectRenderer')
		);
	}

	/**
	 * @test
	 * @cover ::injectTypoScriptService
	 */
	public function injectTypoScriptServiceSetsTypoScriptService() {
		$mockTypoScriptService = $this->getMock(
			'TYPO3\\CMS\\Extbase\\Service\\TypoScriptService'
		);

		$this->subject->injectTypoScriptService($mockTypoScriptService);
		$this->assertSame(
			$mockTypoScriptService,
			$this->subject->_get('typoScriptService')
		);
	}

	/**
	 * @test
	 * @covers ::isDisabled
	 */
	public function isDisabledReturnsInitiallyFalse() {
		$configuration = [];
		$this->assertFalse(
			$this->subject->isDisabled($configuration)
		);
	}

	/**
	 * @test
	 * @covers ::isDisabled
	 */
	public function isDisabledReturnsTrueIfDisabledIsSet() {
		$configuration = [
			'disable' => '1'
		];
		$this->assertTrue(
			$this->subject->isDisabled($configuration)
		);

	}

	/**
	 * @test
	 * @covers ::renderContent
	 */
	public function renderContentConvertsTypoScript() {
		$mockTypoScriptService = $this->getMock(
			'TYPO3\\CMS\\Extbase\\Service\\TypoScriptService',
			['convertPlainArrayToTypoScriptArray']
		);
		$mockContentObjectRenderer = $this->getMock(
			'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer'
		);
		$this->subject->_set('typoScriptService', $mockTypoScriptService);
		$this->subject->_set('contentObjectRenderer', $mockContentObjectRenderer);

		$configuration = [
			'disable' => [
				'foo' => 'bar',
			]
		];
		$mockContentObjectRenderer->expects($this->once())
			->method('getContentObject');

		$mockTypoScriptService->expects($this->once())
			->method('convertPlainArrayToTypoScriptArray')
			->with($configuration['disable']);
		$this->subject->_call('isDisabled', $configuration);

	}

	/**
	 * @test
	 * @covers ::renderContent
	 */
	public function renderContentEvaluatesTypoScript() {
		$mockTypoScriptService = $this->getMock(
			'TYPO3\\CMS\\Extbase\\Service\\TypoScriptService'
		);
		$mockContentObjectRenderer = $this->getMock(
			'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer',
			['getContentObject', 'start']
		);
		$mockContentObject = $this->getMock(
			'TYPO3\\CMS\\Frontend\\ContentObject\\TextContentObject',
			['render'], [], '', FALSE
		);
		$this->subject->_set('typoScriptService', $mockTypoScriptService);
		$this->subject->_set('contentObjectRenderer', $mockContentObjectRenderer);

		$configuration = [
			'disable' => [
				'value' => '1',
				'if' => [
					'isTrue' => '1'
				],
				'_typoScriptNodeValue' => 'TEXT',
			]
		];

		$mockContentObjectRenderer->expects($this->once())
			->method('getContentObject')
			->will($this->returnValue($mockContentObject));
		$mockContentObject->expects($this->once())
			->method('render')
			->will($this->returnValue('1'));

		$this->assertTrue(
			$this->subject->_call('isDisabled', $configuration)
		);

	}

	/**
	 * @test
	 * @covers ::isDisabled
	 */
	public function isDisabledRendersContent() {
		$subject = $this->getAccessibleMock(
			'CPSIT\\T3import\\PreProcessor\\AbstractPreProcessor',
			['renderContent', 'process'], [], '', FALSE);
		$configuration = [
			'disable' => [
				'value' => '1',
				'if' => [
					'isTrue' => '1'
				],
				'_typoScriptNodeValue' => 'TEXT',
			]
		];

		$subject->expects($this->once())
			->method('renderContent')
			->with([], $configuration['disable']);

		$subject->isDisabled($configuration);

	}
}
