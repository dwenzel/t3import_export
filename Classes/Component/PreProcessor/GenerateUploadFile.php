<?php

namespace CPSIT\T3importExport\Component\PreProcessor;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use CPSIT\T3importExport\Resource\ResourceTrait;

/**
 * Class GenerateUploadFile
 * Generates a file path for a TYPO3 upload file field
 * (where the file name is stored and a path is prefixed - usually something like 'uploads/<extensionName>')
 */
class GenerateUploadFile extends AbstractPreProcessor implements PreProcessorInterface
{
    use GenerateFileTrait, ResourceTrait;

    /**
     * Generates a file path for a TYPO3 upload file field
     * (where the file name is stored and a path is prefixed - usually something like 'uploads/<extensionName>')
     *
     * @param array $configuration A valid configuration containing at least a targetDirectoryPath
     * @param string $sourceFilePath
     * @return string Returns a valid entry for a file upload field or an empty string
     */
    public function getFile($configuration, $sourceFilePath)
    {
        $targetPath = $this->getTargetPath($configuration, $sourceFilePath);

        if (!@copy($sourceFilePath, $this->getAbsoluteFilePath($targetPath))) {
            $targetPath = '';
            // @todo log error from error_get_last()
        }

        return $targetPath;
    }
}
