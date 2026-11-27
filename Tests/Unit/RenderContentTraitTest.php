<?php
namespace CPSIT\T3importExport\Tests;

use CPSIT\T3importExport\RenderContentTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockContentObjectRendererTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockTypoScriptFrontendControllerTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockTypoScriptServiceTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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

/**
 * Uses the trait with a replaceable TypoScriptFrontendController,
 * so that the request handling can be tested without a frontend.
 */
class RequestTestClassWithRenderContentTrait {
    use RenderContentTrait;

    public ?TypoScriptFrontendController $frontendController = null;

    public function callAssertTypoScriptFrontendController(): void
    {
        $this->assertTypoScriptFrontendController();
    }

    public function getRenderContentRequest(): ?ServerRequestInterface
    {
        return $this->renderContentRequest;
    }

    protected function createTypoScriptFrontendController(ServerRequestInterface $request): TypoScriptFrontendController
    {
        return $this->frontendController
            ?? throw new \LogicException('Set $frontendController before creating a TypoScriptFrontendController', 1764230400);
    }
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

    /**
     * @var array<string, mixed>
     */
    protected array $savedGlobals = [];

    protected function setUp(): void
    {
        foreach (['TYPO3_REQUEST', 'TSFE'] as $key) {
            if (array_key_exists($key, $GLOBALS)) {
                $this->savedGlobals[$key] = $GLOBALS[$key];
            }
            unset($GLOBALS[$key]);
        }
    }

    protected function tearDown(): void
    {
        foreach (['TYPO3_REQUEST', 'TSFE'] as $key) {
            unset($GLOBALS[$key]);
            if (array_key_exists($key, $this->savedGlobals)) {
                $GLOBALS[$key] = $this->savedGlobals[$key];
            }
        }
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    protected function setUpMockedSubject(): void
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
        $this->setUpMockedSubject();
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
        $this->setUpMockedSubject();
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
        $this->setUpMockedSubject();
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
        $this->setUpMockedSubject();
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

    public function testAssertTypoScriptFrontendControllerKeepsExistingRequest(): void
    {
        $existingRequest = $this->createBackendRequest();
        $GLOBALS['TYPO3_REQUEST'] = $existingRequest;
        $frontendController = $this->createMock(TypoScriptFrontendController::class);
        $subject = new RequestTestClassWithRenderContentTrait();
        $subject->frontendController = $frontendController;

        $subject->callAssertTypoScriptFrontendController();

        self::assertSame($existingRequest, $GLOBALS['TYPO3_REQUEST']);
        self::assertSame($frontendController, $GLOBALS['TSFE']);
        self::assertInstanceOf(ServerRequestInterface::class, $subject->getRenderContentRequest());
        self::assertNotSame($existingRequest, $subject->getRenderContentRequest());
    }

    public function testAssertTypoScriptFrontendControllerSetsFakeRequestIfMissing(): void
    {
        $frontendController = $this->createMock(TypoScriptFrontendController::class);
        $subject = new RequestTestClassWithRenderContentTrait();
        $subject->frontendController = $frontendController;

        $subject->callAssertTypoScriptFrontendController();

        self::assertInstanceOf(ServerRequestInterface::class, $GLOBALS['TYPO3_REQUEST']);
        self::assertSame($subject->getRenderContentRequest(), $GLOBALS['TYPO3_REQUEST']);
        self::assertNotNull($GLOBALS['TYPO3_REQUEST']->getAttribute('site'));
        self::assertNotNull($GLOBALS['TYPO3_REQUEST']->getAttribute('language'));
        self::assertSame($frontendController, $GLOBALS['TSFE']);
    }

    public function testAssertTypoScriptFrontendControllerKeepsExistingFrontendControllerAndRequest(): void
    {
        $existingRequest = $this->createBackendRequest();
        $existingFrontendController = $this->createMock(TypoScriptFrontendController::class);
        $GLOBALS['TYPO3_REQUEST'] = $existingRequest;
        $GLOBALS['TSFE'] = $existingFrontendController;
        $subject = new RequestTestClassWithRenderContentTrait();
        $subject->frontendController = $this->createMock(TypoScriptFrontendController::class);

        $subject->callAssertTypoScriptFrontendController();

        self::assertSame($existingRequest, $GLOBALS['TYPO3_REQUEST']);
        self::assertSame($existingFrontendController, $GLOBALS['TSFE']);
        self::assertNull($subject->getRenderContentRequest());
    }

    public function testGetContentObjectRendererPassesFakeRequestToRenderer(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = $this->createBackendRequest();
        $subject = new RequestTestClassWithRenderContentTrait();
        $subject->frontendController = $this->createMock(TypoScriptFrontendController::class);
        $contentObjectRenderer = $this->createMock(ContentObjectRenderer::class);
        GeneralUtility::addInstance(ContentObjectRenderer::class, $contentObjectRenderer);

        $contentObjectRenderer->expects(self::once())
            ->method('setRequest')
            ->with(self::callback(
                static fn (ServerRequestInterface $request): bool => $request->getAttribute('site') instanceof Site
                    && $request !== $GLOBALS['TYPO3_REQUEST']
            ));

        self::assertSame($contentObjectRenderer, $subject->getContentObjectRenderer());
    }

    public function testGetContentObjectRendererPassesFakeRequestToRendererOfFurtherInstances(): void
    {
        $backendRequest = $this->createBackendRequest();
        $GLOBALS['TYPO3_REQUEST'] = $backendRequest;
        $firstSubject = new RequestTestClassWithRenderContentTrait();
        $firstSubject->frontendController = $this->createMock(TypoScriptFrontendController::class);
        $secondSubject = new RequestTestClassWithRenderContentTrait();
        $firstRenderer = $this->createMock(ContentObjectRenderer::class);
        $secondRenderer = $this->createMock(ContentObjectRenderer::class);
        GeneralUtility::addInstance(ContentObjectRenderer::class, $firstRenderer);
        GeneralUtility::addInstance(ContentObjectRenderer::class, $secondRenderer);

        $hasSite = self::callback(
            static fn (ServerRequestInterface $request): bool => $request->getAttribute('site') instanceof Site
        );
        $firstRenderer->expects(self::once())->method('setRequest')->with($hasSite);
        $secondRenderer->expects(self::once())->method('setRequest')->with($hasSite);

        self::assertSame($firstRenderer, $firstSubject->getContentObjectRenderer());
        self::assertSame($secondRenderer, $secondSubject->getContentObjectRenderer());
        self::assertSame($backendRequest, $GLOBALS['TYPO3_REQUEST']);
    }

    public function testGetContentObjectRendererKeepsGlobalFrontendRequest(): void
    {
        $frontendRequest = (new ServerRequest('https://example.org/'))
            ->withAttribute('site', new Site('main', 1, []))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $GLOBALS['TYPO3_REQUEST'] = $frontendRequest;
        $GLOBALS['TSFE'] = $this->createMock(TypoScriptFrontendController::class);
        $subject = new RequestTestClassWithRenderContentTrait();
        $contentObjectRenderer = $this->createMock(ContentObjectRenderer::class);
        GeneralUtility::addInstance(ContentObjectRenderer::class, $contentObjectRenderer);

        $contentObjectRenderer->expects(self::never())->method('setRequest');

        $subject->getContentObjectRenderer();
        self::assertSame($frontendRequest, $GLOBALS['TYPO3_REQUEST']);
        self::assertNull($subject->getRenderContentRequest());
    }

    /**
     * A backend request as built by the backend middlewares (site resolver adds a NullSite for id 0)
     */
    private function createBackendRequest(): ServerRequestInterface
    {
        return (new ServerRequest('https://example.org/typo3/module/foo'))
            ->withAttribute('site', new NullSite())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }
}
