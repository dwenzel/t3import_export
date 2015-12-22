<?php
namespace CPSIT\T3import\Component\PreProcessor;

use CPSIT\T3import\Component\AbstractComponent;
use TYPO3\CMS\Extbase\Service\TypoScriptService;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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
abstract class AbstractPreProcessor extends AbstractComponent {
	/**
	 * @param array $configuration
	 * @param array $record
	 * @return bool
	 */
	abstract function process($configuration, &$record);

	/**
	 * Tells whether a given configuration is valid
	 *
	 * @param array $configuration
	 * @return bool
	 */
	public function isConfigurationValid($configuration) {
		return TRUE;
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
	 * @param string $sourceField
	 * @param string $targetField
	 * @param array $record
	 */
	protected function mapField($sourceField, $targetField, &$record) {
		$record[$targetField] = $record[$sourceField];
	}

	/**
	 * @param $sourceField
	 * @param $record
	 */
	protected function unsetField($sourceField, &$record) {
		unset($record[$sourceField]);
	}
}