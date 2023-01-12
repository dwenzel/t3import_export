<?php

namespace CPSIT\T3importExport;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
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
     * Get a ContentObjectRenderer
     */
    public function getContentObjectRenderer(): ContentObjectRenderer
    {
        if(!$this->contentObjectRenderer instanceof ContentObjectRenderer)
        {
            $this->assertTypoScriptFrontendController();
            $this->contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        }

        return $this->contentObjectRenderer;
    }


    public function getTypoScriptService(): TypoScriptService
    {
        if (!$this->typoScriptService instanceof TypoScriptService)
        {
            $this->typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        }

        return $this->typoScriptService;
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
     * only for testing
     *
     * @return TypoScriptFrontendController|null
     */
    public function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'] ?? null;
    }

    protected function assertTypoScriptFrontendController(): void
    {
        /**
         * initialize TypoScriptFrontendController (with page and type 0)
         * This is necessary for PreProcessor\RenderContent if configuration contains COA objects
         * ContentObjectRenderer fails in method cObjGetSingle since
         * getTypoScriptFrontendController return NULL instead of $GLOBALS['TSFE']
         */
        if (!$this->getTypoScriptFrontendController() instanceof TypoScriptFrontendController) {
            $fakeUri = new Uri('https://domain.org/page');
            $site = GeneralUtility::makeInstance(Site::class, 1, 1, []);
            $siteLanguage = GeneralUtility::makeInstance(
                SiteLanguage::class,
                0,
                'en-EN',
                $fakeUri,
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

            $context = GeneralUtility::makeInstance(Context::class);
            /** @var FrontendUserAuthentication $feUser */
            $feUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
            $userGroups = [0, -1];
            $feUser->user = ['uid' => 0, 'username' => '', 'usergroup' => implode(',', $userGroups) ];
            $feUser->fetchGroupData();
            $feUser->initializeUserSessionManager();
            $feUser->fetchUserSession();
            $context->setAspect('frontend.user', GeneralUtility::makeInstance(UserAspect::class, $feUser, $userGroups));

            $fakeRequest = new ServerRequest($fakeUri);
            $originalRequest = $GLOBALS['TYPO3_REQUEST'];
            $GLOBALS['TYPO3_REQUEST'] = $fakeRequest;
            $GLOBALS['TSFE'] = GeneralUtility::makeInstance(TypoScriptFrontendController::class,
                $context,
                $site,
                $siteLanguage,
                $pageArguments,
                $feUser
            );

            $GLOBALS['TYPO3_REQUEST'] = $originalRequest;

        }
    }
}
