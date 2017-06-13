<?php
namespace CPSIT\T3importExport;

use TYPO3\CMS\Extbase\Service\TypoScriptService;
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
           $GLOBALS['TSFE'] = new TypoScriptFrontendController($GLOBALS['TYPO3_CONF_VARS'], 0, 0);
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
    public function  renderContent(array $record, array $configuration)
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
