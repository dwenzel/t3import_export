<?php

namespace CPSIT\T3importExport;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class RenderContentTrait
 *
 * @package CPSIT\T3importExport
 */
trait RenderContentTrait
{
    /**
     * @var ContentObjectRenderer
     */
    protected $contentObjectRenderer;

    /**
     * @var TypoScriptService
     */
    protected $typoScriptService;

    /**
     * injects the contentObjectRenderer
     *
     * @param ContentObjectRenderer $contentObjectRenderer
     */
    public function injectContentObjectRenderer(ContentObjectRenderer $contentObjectRenderer)
    {
        $this->contentObjectRenderer = $contentObjectRenderer;
        /**
         * initialize TypoScriptFrontendController (with page and type 0)
         * This is necessary for PreProcessor\RenderContent if configuration contains COA objects
         * ContentObjectRenderer fails in method cObjGetSingle since
         * getTypoScriptFrontendController return NULL instead of $GLOBALS['TSFE']
         */
        if (!$this->getTypoScriptFrontendController() instanceof TypoScriptFrontendController) {
            $site = GeneralUtility::makeInstance(Site::class, 1, 1, []);
            $siteLanguage = GeneralUtility::makeInstance(
                SiteLanguage::class,
                0,
                'en-EN',
                new Uri('https://domain.org/page'),
                []
            );
            $pageArguments = GeneralUtility::makeInstance(PageArguments::class, 1, 0, []);
            $nullFrontend = GeneralUtility::makeInstance(NullFrontend::class, 'pages');
            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
            try {
                $cacheManager->registerCache($nullFrontend);
            } catch (\Exception $exception) {
                unset($exception);
            }
            $GLOBALS['TSFE'] = new TypoScriptFrontendController(
                GeneralUtility::makeInstance(Context::class),
                $site,
                $siteLanguage,
                $pageArguments
            );
        }
    }

    /**
     * injects the typoScriptService
     *
     * @param TypoScriptService $typoScriptService
     */
    public function injectTypoScriptService(TypoScriptService $typoScriptService)
    {
        $this->typoScriptService = $typoScriptService;
    }

    /**
     * Renders content using TypoScript objects
     * @param array $record Optional data array
     * @param array $configuration Plain or TypoScript array
     * @return mixed|null Returns rendered content for each valid TypoScript object or null.
     * @throws \TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException
     */
    public function renderContent(array $record, array $configuration)
    {
        $typoScriptConf = $this->typoScriptService
            ->convertPlainArrayToTypoScriptArray($configuration);
        /** @var AbstractContentObject $contentObject */
        $contentObject = $this->contentObjectRenderer
            ->getContentObject($configuration['_typoScriptNodeValue']);

        if ($contentObject !== null) {
            $this->contentObjectRenderer->start($record);

            return $contentObject->render($typoScriptConf);
        }

        return null;
    }

    /**
     * Gets the TypoScriptFrontendController
     * only for testing
     *
     * @return TypoScriptFrontendController
     */
    public function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
