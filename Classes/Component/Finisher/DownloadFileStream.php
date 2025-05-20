<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Component\Finisher;

use CPSIT\T3importExport\Domain\Model\TaskResult;

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
class DownloadFileStream extends AbstractFinisher implements FinisherInterface
{
    /** @noinspection ReferencingObjectsInspection */
    public function process(array $configuration, array &$records, array|object &$result): bool
    {
        if ($result instanceof TaskResult) {
            $taskResult = $result;
            $tempFile = $taskResult->getInfo();
            $this->prepareFileToDownload($tempFile, $configuration);
        }
        // we expect $this->prepareFileToDownload to exit if the file was downloaded
        return false;
    }

    protected function prepareFileToDownload($filePath, $configuration): void
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
                if ($fileExt[0] !== '.') {
                    $fileExt = '.' . $fileExt;
                }
            }

            header('Content-Description: File Transfer');
            header('Content-Type: ' . $cType);
            header('Content-Disposition: attachment; filename="' . $fileName . $fileExt . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
    }
}
