<?php
namespace CPSIT\T3importExport\Tests;

use CPSIT\T3importExport\RenderContentTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockContentObjectRendererTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockTypoScriptFrontendControllerTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockTypoScriptServiceTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\Service\TypoScriptService;
use TYPO3\CMS\Form\Controller\FrontendController;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
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
class MockClassWithRenderContentTrait {
    use RenderContentTrait;
}
class RenderContentTraitTest extends TestCase
{
    use MockContentObjectRendererTrait,
        MockTypoScriptServiceTrait,
        MockTypoScriptFrontendControllerTrait;

    /**
     * @var MockClassWithRenderContentTrait|MockObject
     */
    protected $subject;

    public function setUp()
    {
        $this->markTestIncomplete('test fails due to dependency injection issues');
        $this->subject = $this->getMockBuilder(MockClassWithRenderContentTrait::class)
            ->getMock();
        $this->mockTypoScriptService();
        $this->mockTypoScriptFrontendController();
        $this->mockContentObjectRenderer();

        $this->subject->method('getTypoScriptFrontendController')->willReturn($this->typoScriptFrontendController);
    }

    /**
     * @test
     */
    public function renderContentConvertsPlainArrayToTypoScriptArray(): void
    {
        $configuration = [
            '_typoScriptNodeValue' => 'BAR'
        ];
        $this->typoScriptService->expects($this->once())
            ->method('convertPlainArrayToTypoScriptArray')
            ->with($configuration);
        $this->mockContentObjectRenderer();
        $this->subject->renderContent([], $configuration);
    }

    public function testRenderContentGetsContentObject(): void
    {
        $configuration = [
            '_typoScriptNodeValue' => 'FOO'
        ];
        $this->contentObjectRenderer->expects($this->once())
            ->method('getContentObject')
            ->with(...['FOO']);
        $this->subject->renderContent([], $configuration);
    }

    /**
     * @test
     */
    public function renderContentReturnsContentFromObject(): void
    {
        $configuration = [
            '_typoScriptNodeValue' => 'FOO'
        ];
        $mockContent = 'bar';
        $this->typoScriptService->expects($this->once())
            ->method('convertPlainArrayToTypoScriptArray')
            ->with($configuration)
            ->willReturn($configuration);

        $this->contentObjectRenderer->expects($this->once())
            ->method('getContentObject')
            ->willReturn($this->contentObject);
        $this->contentObject->expects($this->once())
            ->method('render')
            ->with($configuration)
            ->willReturn($mockContent);
        $this->assertSame(
            $mockContent,
            $this->subject->renderContent([], $configuration)
        );
    }

    /**
     * @test
     */
    public function getTypoScriptFrontendControllerReturnsObjectFromGlobals(): void
    {
        // setup mocks method 'getTypoScriptFrontendController
        $this->subject = $this->getMockForTrait(
            RenderContentTrait::class,
            [], '', true, true, true, []
        );

        $GLOBALS['TSFE'] = new \stdClass();
        $this->assertSame(
            $GLOBALS['TSFE'],
            $this->subject->getTypoScriptFrontendController()
        );
    }
}
