<?php

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

use CPSIT\T3importExport\Resource\FileIndexRepositoryTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;

/**
 * Class FileIndexRepositoryTraitTest
 */
class FileIndexRepositoryTraitTest extends TestCase
{

    /**
     * subject
     * @var FileIndexRepositoryTrait|MockObject
     */
    protected $subject;

    /**
     * set up subject
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function setUp()
    {
        $this->subject = $this->getMockBuilder(FileIndexRepositoryTrait::class)
            ->getMockForTrait();
    }

    public function testFileIndexRepositoryCanBeInjected(): void
    {
        $fileIndexRepository = $this->getMockBuilder(FileIndexRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subject->injectFileIndexRepository($fileIndexRepository);

        $this->assertAttributeSame(
            $fileIndexRepository,
            'fileIndexRepository',
            $this->subject
        );
    }
}
