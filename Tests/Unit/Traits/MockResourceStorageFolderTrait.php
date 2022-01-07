<?php

namespace CPSIT\T3importExport\Tests\Unit\Traits;

use CPSIT\T3importExport\Tests\Unit\Component\Finisher\MoveFileTest;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2022 Dirk Wenzel <wenzel@cps-it.de>
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
trait MockResourceStorageFolderTrait
{

    /**
     * @var Folder|MockObject
     */
    protected $folder;
    /**
     * @var ResourceStorage|MockObject
     */
    protected $storage;

    protected function mockStorageFolder(): void
    {
        $this->folder = $this->getMockBuilder(Folder::class)
            ->disableOriginalConstructor()->getMock();
        $this->storage->method('getDefaultFolder')
        ->willReturn($this->folder);
    }

    protected function mockResourceStorage(): void
    {
        $this->storage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getDefaultFolder',
                'hasFolder',
                'hasFileInFolder',
                'getFolder',
                'createFolder',
                'moveFile'
            ])
            ->getMock();
    }
}
