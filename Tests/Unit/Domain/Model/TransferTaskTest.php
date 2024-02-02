<?php
namespace CPSIT\T3importExport\Tests\Domain\Model;

use CPSIT\T3importExport\Domain\Model\TransferSet;
use CPSIT\T3importExport\Domain\Model\TransferTask;
use CPSIT\T3importExport\Persistence\DataSourceInterface;
use CPSIT\T3importExport\Persistence\DataTargetInterface;
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
class TransferTaskTest extends TestCase
{

    protected TransferTask $subject;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    protected function setUp(): void
    {
        $this->subject = new TransferTask();
    }

    public function testGetIdentifierInitiallyReturnsNull(): void
    {
        $this->assertNull(
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

    public function testGetTargetClassInitiallyReturnsNull(): void
    {
        $this->assertNull(
            $this->subject->getTargetClass()
        );
    }

    public function testSetTargetClassForStringSetsTargetClass(): void
    {
        $identifier = 'foo';
        $this->subject->setTargetClass($identifier);

        $this->assertSame(
            $identifier,
            $this->subject->getTargetClass()
        );
    }

    public function testGetTargetInitiallyReturnsNull(): void
    {
        $this->assertNull(
            $this->subject->getTarget()
        );
    }

    public function testGetSourceInitiallyReturnsNull(): void
    {
        $this->assertNull(
            $this->subject->getSource()
        );
    }

    public function testSetTargetForObjectSetsTarget(): void
    {
        $target = $this->getMockForAbstractClass(DataTargetInterface::class);
        $this->subject->setTarget($target);
        $this->assertSame(
            $target,
            $this->subject->getTarget()
        );
    }

    public function testSetSourceForObjectSetsSource(): void
    {
        $source = $this->getMockForAbstractClass(DataSourceInterface::class);
        $this->subject->setSource($source);
        $this->assertSame(
            $source,
            $this->subject->getSource()
        );
    }

    public function testGetPreProcessorsInitiallyReturnsEmptyArray(): void
    {
        $this->assertSame(
            [],
            $this->subject->getPreProcessors()
        );
    }

    public function testPreProcessorsCanBeSet(): void
    {
        $processors = ['foo'];
        $this->subject->setPreProcessors($processors);
        $this->assertSame(
            $processors,
            $this->subject->getPreProcessors()
        );
    }

    public function testGetPostProcessorsInitiallyReturnsEmptyArray(): void
    {
        $this->assertSame(
            [],
            $this->subject->getPostProcessors()
        );
    }

    public function testPostProcessorsCanBeSet(): void
    {
        $processors = ['foo'];
        $this->subject->setPostProcessors($processors);
        $this->assertSame(
            $processors,
            $this->subject->getPostProcessors()
        );
    }

    public function testGetConvertersInitiallyReturnsEmptyArray(): void
    {
        $this->assertSame(
            [],
            $this->subject->getConverters()
        );
    }

    public function testConvertersCanBeSet(): void
    {
        $processors = ['foo'];
        $this->subject->setConverters($processors);
        $this->assertSame(
            $processors,
            $this->subject->getConverters()
        );
    }

    public function testGetFinishersInitiallyReturnsEmptyArray(): void
    {
        $this->assertSame(
            [],
            $this->subject->getFinishers()
        );
    }

    public function testFinishersCanBeSet(): void
    {
        $finishers = ['foo'];
        $this->subject->setFinishers($finishers);

        $this->assertSame(
            $finishers,
            $this->subject->getFinishers()
        );
    }

    public function testGetInitializersInitiallyReturnsEmptyArray(): void
    {
        $this->assertSame(
            [],
            $this->subject->getInitializers()
        );
    }

    public function testInitializersCanBeSet(): void
    {
        $initializers = ['foo'];
        $this->subject->setInitializers($initializers);

        $this->assertSame(
            $initializers,
            $this->subject->getInitializers()
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
