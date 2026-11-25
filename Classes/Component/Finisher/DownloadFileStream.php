<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Component\Finisher;

use CPSIT\ImportExportCore\Component\Finisher\FinisherInterface;
use CPSIT\ImportExportCore\Domain\Model\ResultInterface;

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
        if ($result instanceof ResultInterface) {
            $filePath = $this->getFilePath($result->getInfo());
            if ($filePath !== null) {
                $this->prepareFileToDownload($filePath, $configuration);
            }
        }
        // we expect $this->prepareFileToDownload to exit if the file was downloaded
        return false;
    }

    /**
     * Returns the path of the file in the info of the result. Targets set a
     * file info object or a path.
     */
    protected function getFilePath(mixed $info): ?string
    {
        if ($info instanceof \SplFileInfo) {
            return $info->getPathname();
        }
        if (is_string($info) && $info !== '') {
            return $info;
        }

        return null;
    }

    protected function prepareFileToDownload(string $filePath, array $configuration): void
    {
        if (is_file($filePath)) {
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

            $this->sendHeader('Content-Description: File Transfer');
            $this->sendHeader('Content-Type: ' . $cType);
            $this->sendHeader('Content-Disposition: attachment; filename="' . $fileName . $fileExt . '"');
            $this->sendHeader('Expires: 0');
            $this->sendHeader('Cache-Control: must-revalidate');
            $this->sendHeader('Pragma: public');
            $this->sendHeader('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            $this->terminate();
        }
    }

    protected function sendHeader(string $header): void
    {
        header($header);
    }

    /**
     * Ends the request after the file has been sent
     */
    protected function terminate(): never
    {
        exit;
    }
}
