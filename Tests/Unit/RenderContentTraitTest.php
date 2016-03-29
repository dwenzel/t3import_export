<?php
namespace CPSIT\T3import\Tests;

use CPSIT\T3import\RenderContentTrait;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Service\TypoScriptService;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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
class RenderContentTraitTest extends UnitTestCase {

	/**
	 * @var RenderContentTrait
	 */
	protected $subject;

	public function setUp() {
		$this->subject = $this->getMockForTrait(
			RenderContentTrait::class
		);
	}

    /**
     * @return mixed
     */
    protected function mockTypoScriptService()
    {
        $mockTypoScriptService = $this->getMock(
            TypoScriptService::class, ['convertPlainArrayToTypoScriptArray']
        );
        $this->subject->injectTypoScriptService($mockTypoScriptService);

        return $mockTypoScriptService;
    }

    /**
     * @return mixed
     */
    protected function mockContentObjectRenderer()
    {
        $mockContentObjectRenderer = $this->getMock(
            ContentObjectRenderer::class, ['getContentObject']
        );
        $this->subject->injectContentObjectRenderer($mockContentObjectRenderer);

        return $mockContentObjectRenderer;
    }

    /**
	 * @test
	 * @cover ::injectTypoScriptService
	 */
	public function injectTypoScriptServiceSetsTypoScriptService() {
        $mockTypoScriptService = $this->mockTypoScriptService();
		$this->assertAttributeSame(
			$mockTypoScriptService,
            'typoScriptService',
			$this->subject
		);
	}

    /**
     * @test
     * @cover ::injectContentObjectRenderer
     */
    public function injectContentObjectRendererInjectsObject() {
        $contentObjectRenderer = $this->getMock(
            ContentObjectRenderer::class
        );

        $this->subject->injectContentObjectRenderer($contentObjectRenderer);
        $this->assertAttributeSame(
            $contentObjectRenderer,
            'contentObjectRenderer',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function renderContentConvertsPlainArrayToTypoScriptArray()
    {
        $configuration = ['foo'];
        $mockTypoScriptService = $this->mockTypoScriptService();
        $mockTypoScriptService->expects($this->once())
            ->method('convertPlainArrayToTypoScriptArray')
            ->with($configuration);
        $this->mockContentObjectRenderer();
        $this->subject->renderContent([], $configuration);
    }

    /**
     * @test
     */
    public function renderContentGetsContentObject()
    {
        $configuration = [
            '_typoScriptNodeValue' => 'FOO'
        ];
        $mockContentObject = $this->getAccessibleMockForAbstractClass(
            AbstractContentObject::class, [], '', false
        );
        $this->mockTypoScriptService();
        $mockContentObjectRenderer = $this->mockContentObjectRenderer();
        $mockContentObjectRenderer->expects($this->once())
            ->method('getContentObject')
            ->with('FOO')
            ->will($this->returnValue($mockContentObject));
        $this->subject->renderContent([], $configuration);
    }

    /**
     * @test
     */
    public function renderContentReturnsContentFromObject()
    {
        $configuration = [
            '_typoScriptNodeValue' => 'FOO'
        ];
        $mockContentObject = $this->getAccessibleMockForAbstractClass(
            AbstractContentObject::class, ['render'], '', false
        );
        $mockContent = 'bar';
        $mockTypoScriptService = $this->mockTypoScriptService();
        $mockTypoScriptService->expects($this->once())
            ->method('convertPlainArrayToTypoScriptArray')
            ->with($configuration)
            ->will($this->returnValue($configuration));

        $mockContentObjectRenderer = $this->mockContentObjectRenderer();
        $mockContentObjectRenderer->expects($this->once())
            ->method('getContentObject')
            ->will($this->returnValue($mockContentObject));
        $mockContentObject->expects($this->once())
            ->method('render')
            ->with($configuration)
            ->will($this->returnValue($mockContent));
        $this->assertSame(
            $mockContent,
            $this->subject->renderContent([], $configuration)
        );
    }
}
