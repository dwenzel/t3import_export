<?php
namespace CPSIT\T3importExport\Tests\Domain\Model;

use CPSIT\T3importExport\Domain\Model\TransferSet;
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
class TransferSetTest extends UnitTestCase
{

    /**
     * @var \CPSIT\T3importExport\Domain\Model\TransferSet
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            TransferSet::class, ['dummy'], [], '', false
        );
    }

    /**
     * @test
     */
    public function getIdentifierInitiallyReturnsNull()
    {
        $this->assertNull(
            $this->subject->getIdentifier()
        );
    }

    /**
     * @test
     */
    public function setIdentifierForStringSetsIdentifier()
    {
        $identifier = 'foo';
        $this->subject->setIdentifier($identifier);

        $this->assertSame(
            $identifier,
            $this->subject->getIdentifier()
        );
    }

    /**
     * @test
     */
    public function getDescriptionInitiallyReturnsNull()
    {
        $this->assertNull(
            $this->subject->getDescription()
        );
    }

    /**
     * @test
     */
    public function setDescriptionForStringSetsDescription()
    {
        $identifier = 'foo';
        $this->subject->setDescription($identifier);

        $this->assertSame(
            $identifier,
            $this->subject->getDescription()
        );
    }

    /**
     * @tests
     */
    public function getTasksInitiallyReturnsNull()
    {
        $this->assertNull(
            $this->subject->getTasks()
        );
    }

    /**
     * @test
     */
    public function setTasksForArraySetsTasks()
    {
        $tasks = ['foo'];
        $this->subject->setTasks($tasks);

        $this->assertSame(
            $tasks,
            $this->subject->getTasks()
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
