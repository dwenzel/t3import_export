<?php

namespace CPSIT\T3importExport\Tests\Unit;

/***************************************************************
 *  Copyright notice
 *  (c) 2017 Dirk Wenzel <dirk.wenzel@cps-it.de>
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

use CPSIT\T3importExport\Persistence\Factory\FileReferenceFactory;
use CPSIT\T3importExport\Resource\FileReferenceFactoryTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class FileReferenceFactoryTraitTest
 */
class FileReferenceFactoryTraitTest extends TestCase
{
    /**
     * @var \CPSIT\T3importExport\Resource\FileReferenceFactoryTrait
     */
    protected $subject;

    /**
     * set up subject
     */
    public function setUp()
    {
        $this->subject = $this->getMockForTrait(
            FileReferenceFactoryTrait::class, [], '', false
        );
    }

    /**
     * @test
     */
    public function fileReferenceFactoryCanBeInjected()
    {
        /** @var FileReferenceFactory|\PHPUnit_Framework_MockObject_MockObject $mockFactory */
        $mockFactory = $this->getMockBuilder(FileReferenceFactory::class)
            ->getMock();
        $this->subject->injectFileReferenceFactory($mockFactory);
        $this->assertAttributeSame(
            $mockFactory,
            'fileReferenceFactory',
            $this->subject
        );
    }
}
