<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit;

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

use CPSIT\T3importExport\Resource\StorageRepositoryTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Class StorageRepositoryTraitTest
 */
class StorageRepositoryTraitTest extends TestCase
{
    /**
     * subject
     * @var object Class using the StorageRepositoryTrait
     */
    protected $subject;

    /**
     * set up subject
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function setUp(): void
    {
        // In PHPUnit 12, getMockForTrait is removed
        // Create an anonymous class that uses the trait instead
        $this->subject = new class() {
            use StorageRepositoryTrait;

            public function getStorageRepository()
            {
                return $this->storageRepository;
            }
        };
    }

    public function testStorageRepositoryCanBeInjected(): void
    {
        $storageRepository = $this->getMockBuilder(StorageRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subject->injectStorageRepository($storageRepository);

        self::assertSame(
            $storageRepository,
            $this->subject->getStorageRepository()
        );
    }
}
