<?php
namespace CPSIT\T3import\Tests\Domain\Model;

use CPSIT\T3import\Domain\Model\ImportSet;
use CPSIT\T3import\Domain\Model\ImportTask;
use CPSIT\T3import\Persistence\DataSourceInterface;
use CPSIT\T3import\Persistence\DataTargetInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use CPSIT\T3import\Domain\Model\Dto\ImportDemand;

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
class ImportTaskTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3import\Domain\Model\ImportTask
	 */
	protected $subject;

	public function setUp() {
		$this->subject = $this->getAccessibleMock(
			ImportTask::class, ['dummy'], [], '', FALSE
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
}