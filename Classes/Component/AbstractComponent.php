<?php

namespace CPSIT\T3importExport\Component;

use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\ConfigurableTrait;
use CPSIT\T3importExport\Domain\Model\TaskResult;
use CPSIT\T3importExport\RenderContentInterface;
use CPSIT\T3importExport\RenderContentTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

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

/**
 * Class AbstractComponent
 *
 * @package CPSIT\T3importExport\Component
 */
abstract class AbstractComponent implements ConfigurableInterface, RenderContentInterface
{
    use ConfigurableTrait, RenderContentTrait;

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @param Dispatcher $signalSlotDispatcher
     */
    public function injectSignalSlotDispatcher(Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * Tells if the component is disabled
     *
     * @param array $configuration
     * @param array $record
     * @param TaskResult $result
     * @return bool
     */
    public function isDisabled($configuration, $record = [], TaskResult $result = null)
    {
        if (!isset($configuration['disable'])) {
            return false;
        }

        if ($configuration['disable'] === '1'
        ) {
            return true;
        }
        if (is_array($configuration['disable'])) {

            $localConfiguration = $configuration['disable'];
            if (isset($localConfiguration['if']['result']['hasMessage'])) {
                $messageIds = GeneralUtility::intExplode(
                    ',',
                    $localConfiguration['if']['result']['hasMessage'],
                    true
                );
                foreach ($messageIds as $id) {
                    if ($result->hasMessageWithId($id)) {
                        return true;
                    }
                }
            }

            return ($this->renderContent($record, $localConfiguration) === '1');
        }

        return false;
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
    protected function emitSignal($name, array &$arguments)
    {

        /**
         * Wrap arguments into array in order to allow changing the arguments
         * count. Dispatcher throws InvalidSlotReturnException if slotResult count
         * differs.
         */
        $slotResult = $this->signalSlotDispatcher->dispatch(get_class($this), $name, [$arguments]);
        $arguments = $slotResult[0];
    }
}
