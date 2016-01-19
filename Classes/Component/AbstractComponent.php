<?php
namespace CPSIT\T3import\Component;

use CPSIT\T3import\ConfigurableTrait;
use CPSIT\T3import\InvalidConfigurationException;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Service\TypoScriptService;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/***************************************************************
 *  Copyright notice
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
abstract class AbstractComponent {
	use ConfigurableTrait;

	/**
	 * @var ContentObjectRenderer
	 */
	protected $contentObjectRenderer;

	/**
	 * @var TypoScriptService
	 */
	protected $typoScriptService;

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * @var ObjectManager
	 */
	protected $objectManager;

	/**
	 * injects the contentObjectRenderer
	 *
	 * @param ContentObjectRenderer $contentObjectRenderer
	 */
	public function injectContentObjectRenderer(ContentObjectRenderer $contentObjectRenderer) {
		$this->contentObjectRenderer = $contentObjectRenderer;
		/**
		 * initialize TypoScriptFrontendController (with page and type 0)
		 * This is necessary for PreProcessor\RenderContent if configuration contains COA objects
		 * ContentObjectRenderer fails in method cObjGetSingle since
		 * getTypoScriptFrontendController return NULL instead of $GLOBALS['TSFE']
		 */
		if (!$GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
			$GLOBALS['TSFE'] = new TypoScriptFrontendController($GLOBALS['TYPO3_CONF_VARS'], 0, 0);
		}
	}

	/**
	 * injects the typoScriptService
	 *
	 * @param TypoScriptService $typoScriptService
	 */
	public function injectTypoScriptService(TypoScriptService $typoScriptService) {
		$this->typoScriptService = $typoScriptService;
	}

	/**
	 * @param Dispatcher $signalSlotDispatcher
	 */
	public function injectSignalSlotDispatcher(Dispatcher $signalSlotDispatcher) {
		$this->signalSlotDispatcher = $signalSlotDispatcher;
	}

	/**
	 * injects the object manager
	 *
	 * @param ObjectManager $objectManager
	 */
	public function injectObjectManager(ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Tells if the component is disabled
	 *
	 * @param array $configuration
	 * @param array $record
	 * @return bool
	 */
	public function isDisabled($configuration, $record = []) {
		if (
			isset($configuration['disable'])
			AND $configuration['disable'] === '1'
		) {
			return TRUE;
		}
		if (is_array($configuration['disable'])) {
			$localConfiguration = $configuration['disable'];

			return ($this->renderContent($record, $localConfiguration) === '1');
		}

		return FALSE;
	}

	/**
	 * @param $record
	 * @param $localConfiguration
	 * @return mixed
	 * @throws \TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException
	 */
	protected function renderContent($record, $localConfiguration) {
		$typoScriptConf = $this->typoScriptService
			->convertPlainArrayToTypoScriptArray($localConfiguration);
		/** @var AbstractContentObject $contentObject */
		$contentObject = $this->contentObjectRenderer
			->getContentObject($localConfiguration['_typoScriptNodeValue']);

		if ($contentObject !== NULL) {
			$this->contentObjectRenderer->start($record);

			return $contentObject->render($typoScriptConf);
		}

		return NULL;
	}

	/**
	 * Emits signals
	 *
	 * @param string $name Signal name
	 * @param array $arguments Signal arguments
	 * @codeCoverageIgnore
	 * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
	 * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
	 */
	protected function emitSignal($name, array &$arguments) {

		/**
		 * Wrap arguments into array in order to allow changing the arguments
		 * count. Dispatcher throws InvalidSlotReturnException if slotResult count
		 * differs.
		 */
		$slotResult = $this->signalSlotDispatcher->dispatch(get_class($this), $name, [$arguments]);
		$arguments = $slotResult[0];
	}

}
