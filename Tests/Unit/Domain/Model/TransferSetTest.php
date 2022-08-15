<?php
namespace CPSIT\T3importExport\Tests\Domain\Model;

use CPSIT\T3importExport\Domain\Model\TransferSet;
use PHPUnit\Framework\TestCase;
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
class TransferSetTest extends TestCase
{

    protected TransferSet $subject;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function setUp(): void
    {
        $this->subject = new TransferSet();
    }

    public function testGetIdentifierInitiallyReturnsNull(): void
    {
        $this->assertNull(
            $this->subject->getIdentifier()
        );
    }

    public function testSetIdentifierForStringSetsIdentifier(): void
    {
        $identifier = 'foo';
        $this->subject->setIdentifier($identifier);

        $this->assertSame(
            $identifier,
            $this->subject->getIdentifier()
        );
    }

    public function testGetDescriptionInitiallyReturnsNull(): void
    {
        $this->assertNull(
            $this->subject->getDescription()
        );
    }

    public function testSetDescriptionForStringSetsDescription(): void
    {
        $identifier = 'foo';
        $this->subject->setDescription($identifier);

        $this->assertSame(
            $identifier,
            $this->subject->getDescription()
        );
    }

    public function testGetTasksInitiallyReturnsNull(): void
    {
        $this->assertNull(
            $this->subject->getTasks()
        );
    }

    public function testSetTasksForArraySetsTasks(): void
    {
        $tasks = ['foo'];
        $this->subject->setTasks($tasks);

        $this->assertSame(
            $tasks,
            $this->subject->getTasks()
        );
    }

    public function testGetLabelReturnsInitiallyNull(): void
    {
        $this->assertNull(
            $this->subject->getLabel()
        );
    }

    public function testSetLabelForStringSetsLabel(): void
    {
        $label = 'foo';
        $this->subject->setLabel($label);
        $this->assertSame(
            $label,
            $this->subject->getLabel()
        );
    }
}
