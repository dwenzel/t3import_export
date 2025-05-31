<?php

declare(strict_types=1);

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

use CPSIT\ImportExportCore\LoggingInterface;
use CPSIT\ImportExportCore\LoggingTrait;
use CPSIT\ImportExportCore\Messaging\Message;
use CPSIT\ImportExportCore\Messaging\MessageContainer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Concrete class for testing LoggingTrait
 */
class LoggingTraitTestClass
{
    use LoggingTrait;
    
    protected const ERROR_CODES = [
        123 => ['Test Error', 'Test error description']
    ];
}

/**
 * Class LoggingTraitTest
 */
class LoggingTraitTest extends TestCase
{
    protected LoggingTraitTestClass $subject;
    protected MessageContainer|MockObject $messageContainer;

    protected function setUp(): void
    {
        $this->messageContainer = $this->createMock(MessageContainer::class);
        $this->subject = new LoggingTraitTestClass($this->messageContainer);
    }

    #[Test]
    public function testLogErrorCreatesDefaultMessage(): void
    {
        $errorId = 123;
        
        $this->messageContainer->expects($this->once())
            ->method('addMessage')
            ->with($this->callback(function (Message $message) {
                return $message->getTitle() === 'Test Error' 
                    && $message->getSeverity() === Message::SEVERITY_ERROR;
            }));
            
        $this->subject->logError($errorId);
    }

    #[Test]
    public function testGetNoticeCodesInitiallyReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->subject->getNoticeCodes());
    }
}
