<?php
namespace CPSIT\T3import\Tests\Unit\Component;

use CPSIT\T3import\Component\AbstractComponent;
use CPSIT\T3import\InvalidConfigurationException;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Service\TypoScriptService;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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
class AbstractComponentTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3import\Component\AbstractComponent
	 */
	protected $subject;

	/**
	 * set up
	 */
	public function setUp() {
		$this->subject = $this->getAccessibleMockForAbstractClass(
			AbstractComponent::class
		);
	}

	/**
	 * @test
	 */
	public function dummyTest() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 * @cover ::injectSignalSlotDispatcher
	 */
	public function injectSignalSlotDispatcherSetsDispatcher() {
		$mockDispatcher = $this->getMock(
			Dispatcher::class
		);

		$this->subject->injectSignalSlotDispatcher($mockDispatcher);
		$this->assertSame(
			$mockDispatcher,
			$this->subject->_get('signalSlotDispatcher')
		);
	}

	/**
	 * @test
	 * @cover ::injectContentObjectRenderer
	 */
	public function injectContentObjectRendererSetsDispatcher() {
		$mockDispatcher = $this->getMock(
			ContentObjectRenderer::class
		);

		$this->subject->injectContentObjectRenderer($mockDispatcher);
		$this->assertSame(
			$mockDispatcher,
			$this->subject->_get('contentObjectRenderer')
		);
	}

	/**
	 * @test
	 * @cover ::injectTypoScriptService
	 */
	public function injectTypoScriptServiceSetsTypoScriptService() {
		$mockTypoScriptService = $this->getMock(
			TypoScriptService::class
		);

		$this->subject->injectTypoScriptService($mockTypoScriptService);
		$this->assertSame(
			$mockTypoScriptService,
			$this->subject->_get('typoScriptService')
		);
	}


}