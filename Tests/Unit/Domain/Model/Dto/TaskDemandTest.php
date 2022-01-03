<?php
namespace CPSIT\T3importExport\Tests\Domain\Model\Dto;

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

/**
 * Class TaskDemandTest
 *
 * @package CPSIT\T3importExport\Tests\Domain\Model\Dto
 */
class TaskDemandTest extends TestCase
{

    /**
     * @var \CPSIT\T3importExport\Domain\Model\Dto\TaskDemand
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            TaskDemand::class, ['dummy'], [], '', false
        );
    }

    /**
     * @test
     */
    public function getTasksInitiallyReturnsEmptyArray()
    {
        $this->assertSame(
            [],
            $this->subject->getTasks()
        );
    }

    /**
     * @test
     */
    public function getTaskForArraySetsTask()
    {
        $expectedArray = ['foo'];
        $this->subject->setTasks($expectedArray);

        $this->assertSame(
            $expectedArray,
            $this->subject->getTasks()
        );
    }
}
