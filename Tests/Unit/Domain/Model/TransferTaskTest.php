<?php

declare(strict_types=1);
namespace CPSIT\T3importExport\Tests\Domain\Model;

use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\T3importExport\Domain\Model\TransferSet;
use CPSIT\T3importExport\Domain\Model\TransferTask;
use CPSIT\ImportExportCore\Persistence\DataSourceInterface;
use CPSIT\ImportExportCore\Persistence\DataTargetInterface;
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
class TransferTaskTest extends TestCase
{
    protected TransferTask $subject;

    protected function setUp(): void
    {
        $this->subject = new TransferTask();
    }

    #[Test]
    public function getIdentifierInitiallyReturnsEmptyString(): void
    {
        $this->assertEquals(
            '',
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
    public function getDescriptionForStringSetsDescription(): void
    {
        $identifier = 'foo';
        $this->subject->setDescription($identifier);

        $this->assertSame(
            $identifier,
            $this->subject->getDescription()
        );
    }

    #[Test]
    public function getTargetClassInitiallyReturnsEmptyString(): void
    {
        $this->assertEquals(
            '',
            $this->subject->getTargetClass()
        );
    }

    #[Test]
    public function getTargetClassForStringSetsTargetClass(): void
    {
        $identifier = 'foo';
        $this->subject->setTargetClass($identifier);

        $this->assertSame(
            $identifier,
            $this->subject->getTargetClass()
        );
    }

    #[Test]
    public function getTargetInitiallyReturnsNull(): void
    {
        $this->assertNull(
            $this->subject->getTarget()
        );
    }

    #[Test]
    public function getSourceInitiallyReturnsNull(): void
    {
        $this->assertNull(
            $this->subject->getSource()
        );
    }

    #[Test]
    public function setTargetForObjectSetsTarget(): void
    {
        $target = $this->getMockBuilder(DataTargetInterface::class)
            ->getMock();
        $this->subject->setTarget($target);
        $this->assertSame(
            $target,
            $this->subject->getTarget()
        );
    }

    #[Test]
    public function setSourceForObjectSetsSource(): void
    {
        $source = $this->getMockBuilder(DataSourceInterface::class)
            ->getMock();
        $this->subject->setSource($source);
        $this->assertSame(
            $source,
            $this->subject->getSource()
        );
    }

    #[Test]
    public function getPreProcessorsInitiallyReturnsEmptyArray(): void
    {
        $this->assertSame(
            [],
            $this->subject->getPreProcessors()
        );
    }

    #[Test]
    public function preProcessorsCanBeSet(): void
    {
        $processors = ['foo'];
        $this->subject->setPreProcessors($processors);
        $this->assertSame(
            $processors,
            $this->subject->getPreProcessors()
        );
    }

    #[Test]
    public function getPostProcessorsInitiallyReturnsEmptyArray(): void
    {
        $this->assertSame(
            [],
            $this->subject->getPostProcessors()
        );
    }

    #[Test]
    public function postProcessorsCanBeSet(): void
    {
        $processors = ['foo'];
        $this->subject->setPostProcessors($processors);
        $this->assertSame(
            $processors,
            $this->subject->getPostProcessors()
        );
    }

    #[Test]
    public function getConvertersInitiallyReturnsEmptyArray(): void
    {
        $this->assertSame(
            [],
            $this->subject->getConverters()
        );
    }

    #[Test]
    public function convertersCanBeSet(): void
    {
        $processors = ['foo'];
        $this->subject->setConverters($processors);
        $this->assertSame(
            $processors,
            $this->subject->getConverters()
        );
    }

    #[Test]
    public function getFinishersInitiallyReturnsEmptyArray(): void
    {
        $this->assertSame(
            [],
            $this->subject->getFinishers()
        );
    }

    public function finishersCanBeSet(): void
    {
        $finishers = ['foo'];
        $this->subject->setFinishers($finishers);

        $this->assertSame(
            $finishers,
            $this->subject->getFinishers()
        );
    }

    #[Test]
    public function getInitializersInitiallyReturnsEmptyArray(): void
    {
        $this->assertSame(
            [],
            $this->subject->getInitializers()
        );
    }

    #[Test]
    public function initializersCanBeSet(): void
    {
        $initializers = ['foo'];
        $this->subject->setInitializers($initializers);

        $this->assertSame(
            $initializers,
            $this->subject->getInitializers()
        );
    }

    #[Test]
    public function getLabelReturnsInitiallyNull(): void
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
