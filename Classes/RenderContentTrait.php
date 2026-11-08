<?php

declare(strict_types=1);

namespace CPSIT\T3importExport;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class RenderContentTrait
 */
trait RenderContentTrait
{
    private ?ContentObjectRenderer $contentObjectRenderer;
    private ?TypoScriptService $typoScriptService;

    /**
     * Get a ContentObjectRenderer
     *
     * The renderer gets a request with an empty frontend TypoScript, so that
     * content objects can be rendered without a frontend controller (TSFE),
     * e.g. on the command line.
     */
    public function getContentObjectRenderer(): ContentObjectRenderer
    {
        $this->contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $this->contentObjectRenderer->setRequest($this->getContentRenderingRequest());

        return $this->contentObjectRenderer;
    }

    public function getTypoScriptService(): TypoScriptService
    {
        $this->typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        return $this->typoScriptService;
    }

    /**
     * Renders content using TypoScript objects
     * @param array $record Optional data array
     * @param array $configuration Plain or TypoScript array
     * @return mixed|null Returns rendered content for each valid TypoScript object or null.
     * @throws ContentRenderingException
     */
    public function renderContent(array $record, array $configuration): mixed
    {
        $typoScriptConf = $this->getTypoScriptService()
            ->convertPlainArrayToTypoScriptArray($configuration);
        /** @var AbstractContentObject $contentObject */
        $contentObject = $this->getContentObjectRenderer()
            ->getContentObject($configuration['_typoScriptNodeValue']);

        if ($contentObject !== null) {
            $this->contentObjectRenderer->start($record);

            return $contentObject->render($typoScriptConf);
        }

        return null;
    }

    /**
     * Gets the TypoScriptFrontendController
     *
     * @deprecated since 3.0.1, content rendering does not use the TSFE anymore. Will be removed in 4.0.
     * @return TypoScriptFrontendController|null
     */
    public function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'] ?? null;
    }

    /**
     * @deprecated since 3.0.1, content rendering does not need a TSFE anymore. Will be removed in 4.0.
     */
    protected function assertTypoScriptFrontendController(): void {}

    /**
     * Returns the current request with a frontend TypoScript attribute.
     * Without a current request (command line), a request for the base URL
     * of the installation is created.
     */
    protected function getContentRenderingRequest(): ServerRequestInterface
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface) {
            $request = new ServerRequest('http://localhost/', 'GET');
        }
        if ($request->getAttribute('frontend.typoscript') instanceof FrontendTypoScript) {
            return $request;
        }

        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $frontendTypoScript->setConfigArray([]);

        return $request->withAttribute('frontend.typoscript', $frontendTypoScript);
    }
}
