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
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class LoggingTraitTest
 *
 * @package CPSIT\T3importExport\Tests\Unit
 */
class LoggingTraitTest extends UnitTestCase
{
    /**
     * @var LoggingTrait|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var MessageContainer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageContainer;

    /**
     * set up subject
     */
    public function setUp()
    {
        $this->subject = $this->getMockBuilder(LoggingTrait::class)
            ->setMethods(['getErrorCodes', 'getNoticeCodes'])
            ->getMockForTrait();

        $this->objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(['get'])->getMock();

        $this->subject->injectObjectManager($this->objectManager);
        $this->messageContainer = $this->getMockBuilder(MessageContainer::class)
            ->setMethods(['addMessage'])->getMock();
        $this->subject->injectMessageContainer($this->messageContainer);
    }

    /**
     * @test
     */
    public function subjectHasAttributeMessageContainer()
    {
        $this->assertObjectHasAttribute(
            'messageContainer',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function subjectHasAttributeObjectManager()
    {
        $this->assertObjectHasAttribute(
            'objectManager',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function logErrorCreatesDefaultMessage()
    {
        $fooErrorId = 0;
        $mockMessage = $this->getMockBuilder(Message::class)->disableOriginalConstructor()->getMock();
        $this->subject->expects($this->any())->method('getErrorCodes')
            ->willReturn([]);
        $expectedDescription = LoggingInterface::ERROR_UNKNOWN_MESSAGE . PHP_EOL . 'Message ID ' . $fooErrorId
            . ' in component ' . get_class($this->subject);

        $this->objectManager->expects($this->once())
            ->method('get')
            ->with(
                Message::class,
                $expectedDescription,
                LoggingInterface::ERROR_UNKNOWN_TITLE,
                Message::ERROR)
            ->will($this->returnValue($mockMessage));
        $this->messageContainer->expects($this->once())
            ->method('addMessage')
            ->with($mockMessage);

        $this->subject->logError($fooErrorId);
    }

    /**
     * @test
     */
    public function logErrorCreatesMessageFromExistingErrorEntry()
    {
        $fooErrorId = 1498948185;
        $arguments = ['bar'];

        $errorCodes = [
            $fooErrorId => ['Foo title', 'bar message with argument %s']
        ];
        $this->subject->expects($this->once())
            ->method('getErrorCodes')
            ->will($this->returnValue($errorCodes));

        $mockMessage = $this->getMockBuilder(Message::class)->disableOriginalConstructor()->getMock();

        $expectedTitle = $errorCodes[$fooErrorId][0];
        $expectedDescription = $errorCodes[$fooErrorId][1];
        $expectedDescription = sprintf($expectedDescription, $arguments[0]);
        $expectedDescription .= PHP_EOL . 'Message ID ' . $fooErrorId
            . ' in component ' . get_class($this->subject);

        $this->objectManager->expects($this->once())
            ->method('get')
            ->with(
                Message::class,
                $expectedDescription,
                $expectedTitle,
                Message::ERROR)
            ->will($this->returnValue($mockMessage));
        $this->messageContainer->expects($this->once())
            ->method('addMessage')
            ->with($mockMessage);

        $this->subject->logError($fooErrorId, $arguments);
    }

    /**
     * @test
     */
    public function getNoticeCodesInitiallyReturnsEmptyArray() {
        $this->subject = $this->getMockBuilder(LoggingTrait::class)
            ->setMethods(['dummy'])
            ->getMockForTrait();
        $expected = [];
        $this->assertSame(
            $expected,
            $this->subject->getNoticeCodes()
        );
    }

    /**
     * @test
     */
    public function logNoticeCreatesDefaultMessage()
    {
        $fooNoticeId = 0;
        $mockMessage = $this->getMockBuilder(Message::class)->disableOriginalConstructor()->getMock();
        $this->subject->expects($this->any())->method('getNoticeCodes')
            ->willReturn([]);
        $expectedDescription = LoggingInterface::NOTICE_UNKNOWN_MESSAGE . PHP_EOL . 'Message ID ' . $fooNoticeId
            . ' in component ' . get_class($this->subject);

        $this->objectManager->expects($this->once())
            ->method('get')
            ->with(
                Message::class,
                $expectedDescription,
                LoggingInterface::NOTICE_UNKNOWN_TITLE,
                Message::NOTICE)
            ->will($this->returnValue($mockMessage));
        $this->messageContainer->expects($this->once())
            ->method('addMessage')
            ->with($mockMessage);

        $this->subject->logNotice($fooNoticeId);
    }

    /**
     * @test
     */
    public function logNoticeCreatesMessageFromExistingNoticeEntry()
    {
        $fooNoticeId = 1498948185;
        $arguments = ['bar'];

        $noticeCodes = [
            $fooNoticeId => ['Foo title', 'bar message with argument %s']
        ];
        $this->subject->expects($this->once())
            ->method('getNoticeCodes')
            ->will($this->returnValue($noticeCodes));

        $mockMessage = $this->getMockBuilder(Message::class)->disableOriginalConstructor()->getMock();

        $expectedTitle = $noticeCodes[$fooNoticeId][0];
        $expectedDescription = $noticeCodes[$fooNoticeId][1];
        $expectedDescription = sprintf($expectedDescription, $arguments[0]);
        $expectedDescription .= PHP_EOL . 'Message ID ' . $fooNoticeId
            . ' in component ' . get_class($this->subject);

        $this->objectManager->expects($this->once())
            ->method('get')
            ->with(
                Message::class,
                $expectedDescription,
                $expectedTitle,
                Message::NOTICE)
            ->will($this->returnValue($mockMessage));
        $this->messageContainer->expects($this->once())
            ->method('addMessage')
            ->with($mockMessage);

        $this->subject->logNotice($fooNoticeId, $arguments);
    }

    /**
     * @test
     */
    public function logMessageAddsMessageToContainer() {
        $mockMessage = $this->getMockBuilder(Message::class)->disableOriginalConstructor()->getMock();

        $expectedTitle = 'bar';
        $expectedDescription = 'foo';

        $this->objectManager->expects($this->once())
            ->method('get')
            ->with(
                Message::class,
                $expectedDescription,
                $expectedTitle,
                Message::OK)
            ->will($this->returnValue($mockMessage));
        $this->messageContainer->expects($this->once())
            ->method('addMessage')
            ->with($mockMessage);

        $this->subject->logMessage($expectedTitle, $expectedDescription);
    }
}
