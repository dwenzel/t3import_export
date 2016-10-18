<?php
namespace CPSIT\T3importExport\Tests\Domain\Model;

use CPSIT\T3importExport\Domain\Model\TransferSet;
use CPSIT\T3importExport\Domain\Model\TransferTask;
use CPSIT\T3importExport\Persistence\DataSourceInterface;
use CPSIT\T3importExport\Persistence\DataTargetInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;

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
class TransferTaskTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3importExport\Domain\Model\TransferTask
	 */
	protected $subject;

	public function setUp() {
		$this->subject = $this->getAccessibleMock(
			TransferTask::class, ['dummy'], [], '', FALSE
		);
	}

	/**
	 * @test
	 */
	public function getIdentifierInitiallyReturnsNull() {
		$this->assertNull(
			$this->subject->getIdentifier()
		);
	}

	/**
	 * @test
	 */
	public function getDescriptionInitiallyReturnsNull() {
		$this->assertNull(
			$this->subject->getDescription()
		);
	}

	/**
	 * @test
	 */
	public function setDescriptionForStringSetsDescription() {
		$identifier = 'foo';
		$this->subject->setDescription($identifier);

		$this->assertSame(
			$identifier,
			$this->subject->getDescription()
		);
	}

	/**
	 * @test
	 */
	public function getTargetClassInitiallyReturnsNull() {
		$this->assertNull(
			$this->subject->getTargetClass()
		);
	}

	/**
	 * @test
	 */
	public function setTargetClassForStringSetsTargetClass() {
		$identifier = 'foo';
		$this->subject->setTargetClass($identifier);

		$this->assertSame(
			$identifier,
			$this->subject->getTargetClass()
		);
	}

	/**
	 * @test
	 */
	public function getTargetInitiallyReturnsNull() {
		$this->assertNull(
			$this->subject->getTarget()
		);
	}

	/**
	 * @test
	 */
	public function getSourceInitiallyReturnsNull() {
		$this->assertNull(
			$this->subject->getSource()
		);
	}

	/**
	 * @test
	 */
	public function setTargetForObjectSetsTarget() {
		$target = $this->getMock(
			DataTargetInterface::class,
			[], [], '', false
		);
		$this->subject->setTarget($target);
		$this->assertSame(
			$target,
			$this->subject->getTarget()
		);
	}

	/**
	 * @test
	 */
	public function setSourceForObjectSetsSource() {
		$source = $this->getMock(
			DataSourceInterface::class,
			[], [], '', false
		);
		$this->subject->setSource($source);
		$this->assertSame(
			$source,
			$this->subject->getSource()
		);
	}

	/**
	 * @test
	 */
	public function getPreProcessorsInitiallyReturnsEmptyArray() {
		$this->assertSame(
			[],
			$this->subject->getPreProcessors()
		);
	}

	/**
	 * @test
	 */
	public function preProcessorsCanBeSet() {
		$processors = ['foo'];
		$this->subject->setPreProcessors($processors);
		$this->assertSame(
			$processors,
			$this->subject->getPreProcessors()
		);
	}

	/**
	 * @test
	 */
	public function getPostProcessorsInitiallyReturnsEmptyArray() {
		$this->assertSame(
			[],
			$this->subject->getPostProcessors()
		);
	}

	/**
	 * @test
	 */
	public function postProcessorsCanBeSet() {
		$processors = ['foo'];
		$this->subject->setPostProcessors($processors);
		$this->assertSame(
			$processors,
			$this->subject->getPostProcessors()
		);
	}

	/**
	 * @test
	 */
	public function getConvertersInitiallyReturnsEmptyArray() {
		$this->assertSame(
			[],
			$this->subject->getConverters()
		);
	}

	/**
	 * @test
	 */
	public function convertersCanBeSet() {
		$processors = ['foo'];
		$this->subject->setConverters($processors);
		$this->assertSame(
			$processors,
			$this->subject->getConverters()
		);
	}

	/**
	 * @test
	 */
	public function getFinishersInitiallyReturnsEmptyArray() {
		$this->assertSame(
			[],
			$this->subject->getFinishers()
		);
	}

	/**
	 * @test
	 */
	public function finishersCanBeSet() {
		$finishers = ['foo'];
		$this->subject->setFinishers($finishers);

		$this->assertSame(
			$finishers,
			$this->subject->getFinishers()
		);
	}

	/**
	 * @test
	 */
	public function getInitializersInitiallyReturnsEmptyArray() {
		$this->assertSame(
			[],
			$this->subject->getInitializers()
		);
	}

	/**
	 * @test
	 */
	public function initializersCanBeSet() {
		$initializers = ['foo'];
		$this->subject->setInitializers($initializers);

		$this->assertSame(
			$initializers,
			$this->subject->getInitializers()
		);
	}

	/**
	 * @test
	 */
	public function getLabelReturnsInitiallyNull()
	{
		$this->assertNull(
			$this->subject->getLabel()
		);
	}

	/**
	 * @test
	 */
	public function setLabelForStringSetsLabel()
	{
		$label = 'foo';
		$this->subject->setLabel($label);
		$this->assertSame(
			$label,
			$this->subject->getLabel()
		);

	}
}
