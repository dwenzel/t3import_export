<?php
namespace CPSIT\T3importExport\Component\Finisher;

use CPSIT\T3importExport\Component\Finisher\AbstractFinisher;
use CPSIT\T3importExport\Component\Finisher\FinisherInterface;
use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\Domain\Model\TaskResult;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\CacheService;
use TYPO3\CMS\Extbase\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/***************************************************************
 *  Copyright notice
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
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
class DownloadFileStream extends AbstractFinisher implements FinisherInterface, ConfigurableInterface
{

    /**
     * Tells whether the given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        return parent::isConfigurationValid($configuration);
    }

    /**
     *
     * @param array $configuration
     * @param array $records
     * @param array $result
     * @return bool
     */
    public function process($configuration, &$records, &$result)
    {
        if (is_a($result, TaskResult::class)) {
            /** @var TaskResult $taskResult */
            $taskResult = $result;
            $tempFile = $taskResult->getInfo();
            $this->prepareFileToDownload($tempFile, $configuration);
        }
    }

    protected function prepareFileToDownload($filePath, $configuration)
    {
        if (file_exists($filePath)) {
            $cType = 'application/octet-stream';
            $fileName = 'file_' . time();
            $fileExt = '.dat';

            if (!empty($configuration['type'])) {
                $cType = $configuration['type'];
            }

            if (!empty($configuration['filename'])) {
                $fileName = $configuration['filename'];
            }

            if (!empty($configuration['fileExt'])) {
                $fileExt = $configuration['fileExt'];
                if ($fileExt{0} !== '.') {
                    $fileExt = '.' . $fileExt;
                }
            }

            header('Content-Description: File Transfer');
            header('Content-Type: ' . $cType);
            header('Content-Disposition: attachment; filename="'.  $fileName . $fileExt .'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
    }
}
