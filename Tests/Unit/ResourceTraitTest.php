<?php
namespace CPSIT\T3importExport\Tests;

use CPSIT\T3importExport\Resource\ResourceTrait;
use CPSIT\T3importExport\Validation\Configuration\ResourcePathConfigurationValidator;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2017 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class ResourceTraitTest extends TestCase
{

    /**
     * @var ResourceTrait
     */
    protected $subject;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function setUp(): void
    {
        $this->subject = $this->getMockForTrait(
            ResourceTrait::class, [], '', false, true, true, ['dummy', 'getAbsoluteFilePath']
        );
        vfsStreamWrapper::register();
    }

    /**
     * @test
     */
    public function loadResourceGetsFileResource(): void
    {
        $fileContent = 'foo';

        $fileDirectory = 'typo3temp';
        $fileName = 'foo.csv';
        $relativePath = $fileDirectory . '/' . $fileName;

        $configuration = [
            'file' => $relativePath,
        ];

        vfsStream::setup($fileDirectory);
        $mockFile = vfsStream::newFile($fileName);
        $mockFile->setContent($fileContent);
        vfsStreamWrapper::getRoot()->addChild($mockFile);

        $this->subject->expects($this->once())
            ->method('getAbsoluteFilePath')
            ->with(...[$relativePath])
            ->willReturn(vfsStream::url($relativePath));

        $this->assertSame(
            $fileContent,
            $this->subject->loadResource($configuration)
        );
    }
}
