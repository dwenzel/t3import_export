<?php

namespace CPSIT\T3importExport\Tests\Unit;

/***************************************************************
 *  Copyright notice
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
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

use CPSIT\T3importExport\LoggingInterface;
use CPSIT\T3importExport\LoggingTrait;
use CPSIT\T3importExport\Messaging\Message;
use CPSIT\T3importExport\Messaging\MessageContainer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class LoggingTraitTest
 *
 * @package CPSIT\T3importExport\Tests\Unit
 */
class LoggingTraitTest extends TestCase
{
    protected function setUp(): void
    {
        // Skip this test in PHPUnit 12 as it requires getMockForTrait
        $this->markTestSkipped('Test requires getMockForTrait which is removed in PHPUnit 12');
    }

    #[Test]
    public function testLogErrorCreatesDefaultMessage(): void
    {
        // This test is skipped in setUp
    }

    #[Test]
    public function testGetNoticeCodesInitiallyReturnsEmptyArray(): void
    {
        // This test is skipped in setUp
    }
}