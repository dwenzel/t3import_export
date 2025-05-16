<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence\Factory;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Dirk Wenzel <wenzel@cps-it.de>
 *  All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

/**
 * Class FileReferenceFactory
 */
class FileReferenceFactory
{
    public function __construct(protected ResourceFactory $resourceFactory) {}
    /**
     * Creates a new file reference for a file
     *
     * @param int $fileId Id of file record
     * @param array $configuration Configuration of this post processor
     * @return FileReference
     */
    public function createFileReferenceObject($fileId, array $configuration, $foreignUid = null)
    {
        $pageId = 0;
        if ($foreignUid === null) {
            $foreignUid = uniqid('NEW_');
        }
        if (isset($configuration['targetPage'])) {
            $pageId = (int)$configuration['targetPage'];
        }

        $tableName = $configuration['tableName'] ?? '';
        $fieldName = $configuration['fieldName'] ?? '';

        /** @var \TYPO3\CMS\Core\Resource\FileReference $coreReference */
        $coreReference = $this->resourceFactory->createFileReferenceObject(
            [
                'uid_local' => $fileId,
                'uid_foreign' => $foreignUid,
                'tablenames' => $tableName,
                'fieldname' => $fieldName,
                'uid' => uniqid('NEW_'),
                'crop' => null,
            ]
        );

        /** @var FileReference $fileReference */
        $fileReference = GeneralUtility::makeInstance(FileReference::class);
        $fileReference->setOriginalResource($coreReference);
        $fileReference->setPid($pageId);

        return $fileReference;
    }
}
