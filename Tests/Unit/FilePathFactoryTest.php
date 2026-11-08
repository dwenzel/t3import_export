<?php

namespace CPSIT\T3importExport\Tests\Unit\Factory;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use CPSIT\T3importExport\Factory\FilePathFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class FilePathFactoryTest
 */
class FilePathFactoryTest extends TestCase
{

    /**
     * @var FilePathFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * set up subject
     */
    public function setUp(): void
    {
        $this->subject = $this->getMockBuilder(FilePathFactory::class)->setMethods(['dummy'])->getMock();
    }

    /**
     * @test
     */
    public function createFromPartsSanitizesTrailingSlashes()
    {
        $parts = [
            'foo/',
            'bar//'
        ];

        $expectedPath = 'foo/bar/';

        $this->assertSame(
            $expectedPath,
            $this->subject->createFromParts($parts)
        );
    }
}
