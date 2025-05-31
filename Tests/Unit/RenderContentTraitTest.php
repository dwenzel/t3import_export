<?php

declare(strict_types=1);
namespace CPSIT\T3importExport\Tests;

use CPSIT\T3importExport\RenderContentTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Frontend\ContentObject\ContentContentObject;
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
class MockClassWithRenderContentTrait
{
    use RenderContentTrait;
    
    public function setTestContentObjectRenderer(ContentObjectRenderer $contentObjectRenderer): void
    {
        $this->contentObjectRenderer = $contentObjectRenderer;
    }
    
    public function setTestTypoScriptService(TypoScriptService $typoScriptService): void
    {
        $this->typoScriptService = $typoScriptService;
    }
    
    public function getContentObjectRenderer(): ContentObjectRenderer
    {
        if ($this->contentObjectRenderer !== null) {
            return $this->contentObjectRenderer;
        }
        
        // Use trait method directly
        $this->assertTypoScriptFrontendController();
        $this->contentObjectRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ContentObjectRenderer::class);
        return $this->contentObjectRenderer;
    }
    
    public function getTypoScriptService(): TypoScriptService
    {
        if ($this->typoScriptService !== null) {
            return $this->typoScriptService;
        }
        
        // Use trait method directly
        $this->typoScriptService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TypoScriptService::class);
        return $this->typoScriptService;
    }
}
class RenderContentTraitTest extends TestCase
{
    /**
     * @var ContentObjectRenderer|MockObject
     */
    protected ContentObjectRenderer|MockObject $contentObjectRenderer;

    /**
     * @var ContentContentObject|MockObject
     */
    protected ContentContentObject|MockObject $contentObject;

    /**
     * @var MockClassWithRenderContentTrait
     */
    protected MockClassWithRenderContentTrait $subject;

    protected TypoScriptService|MockObject $typoScriptService;

    protected function setUp(): void
    {
        // Create a real instance instead of mock to use the trait methods
        $this->subject = new MockClassWithRenderContentTrait();
        
        $this->mockTypoScriptService();
        $this->mockContentObjectRenderer();

        // Create TypoScriptFrontendController mock directly
        $typoScriptFrontendController = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['TSFE'] = $typoScriptFrontendController;

        // Set backup globals to preserve TSFE state
        $this->setBackupGlobals(true);

        // Inject mocked dependencies into the test subject
        $this->subject->setTestContentObjectRenderer($this->contentObjectRenderer);
        $this->subject->setTestTypoScriptService($this->typoScriptService);
    }

    /**
     * Mock TypoScript service
     */
    protected function mockTypoScriptService(): void
    {
        $this->typoScriptService = $this->getMockBuilder(TypoScriptService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['convertPlainArrayToTypoScriptArray'])
            ->getMock();
    }

    /**
     * Mock content object renderer
     */
    protected function mockContentObjectRenderer(): void
    {
        $this->contentObject = $this->getMockBuilder(ContentContentObject::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $this->contentObjectRenderer = $this->getMockBuilder(ContentObjectRenderer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getContentObject', 'wrap', 'noTrimWrap', 'start'])
            ->getMock();

        $this->contentObjectRenderer->method('getContentObject')
            ->willReturn($this->contentObject);
    }

    #[Test]
    public function renderContentConvertsPlainArrayToTypoScriptArray(): void
    {
        $configuration = [
            '_typoScriptNodeValue' => 'BAR',
        ];
        $this->typoScriptService->expects($this->once())
            ->method('convertPlainArrayToTypoScriptArray')
            ->with($configuration);
            
        $this->subject->renderContent([], $configuration);
    }

    #[Test]
    public function testRenderContentGetsContentObject(): void
    {
        $configuration = [
            '_typoScriptNodeValue' => 'FOO',
        ];
        $this->contentObjectRenderer->expects($this->once())
            ->method('getContentObject')
            ->with(...['FOO']);
        $this->subject->renderContent([], $configuration);
    }

    #[Test]
    public function renderContentReturnsContentFromObject(): void
    {
        $configuration = [
            '_typoScriptNodeValue' => 'FOO',
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
}
