<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Domain\Model;

use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\T3importExport\Domain\Model\TransferSet;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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

    protected function setUp(): void
    {
        $this->subject = new TransferSet();
    }

    #[Test]
    public function getIdentifierInitiallyReturnsNull(): void
    {
        $this->assertNull(
            $this->subject->getIdentifier()
        );
    }

    #[Test]
    public function setIdentifierForStringSetsIdentifier(): void
    {
        $identifier = 'foo';
        $this->subject->setIdentifier($identifier);

        $this->assertSame(
            $identifier,
            $this->subject->getIdentifier()
        );
    }

    #[Test]
    public function getDescriptionInitiallyReturnsEmptyString(): void
    {
        $this->assertEquals(
            '',
            $this->subject->getDescription()
        );
    }

    #[Test]
    public function setDescriptionForStringSetsDescription(): void
    {
        $identifier = 'foo';
        $this->subject->setDescription($identifier);

        $this->assertSame(
            $identifier,
            $this->subject->getDescription()
        );
    }

    #[Test]
    public function getTasksInitiallyReturnsEmptyArray(): void
    {
        $this->assertEquals(
            [],
            $this->subject->getTasks()
        );
    }

    #[Test]
    public function setTasksForArraySetsTasks(): void
    {
        $tasks = ['foo'];
        $this->subject->setTasks($tasks);

        $this->assertSame(
            $tasks,
            $this->subject->getTasks()
        );
    }

    #[Test]
    public function getLabelInitiallyReturnsEmptyString(): void
    {
        $this->assertEquals(
            '',
            $this->subject->getLabel()
        );
    }

    #[Test]
    public function setLabelForStringSetsLabel(): void
    {
        $label = 'foo';
        $this->subject->setLabel($label);
        $this->assertSame(
            $label,
            $this->subject->getLabel()
        );
    }
}
