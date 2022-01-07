<?php

namespace CPSIT\T3importExport\Tests\Unit\Traits;

use CPSIT\T3importExport\Tests\Unit\Component\PostProcessor\GenerateFileReferenceTest;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

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
trait MockPersistenceManagerTrait
{

    /**
     * @var PersistenceManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $persistenceManager;

    protected function mockPersistenceManager(): void
    {
        $this->persistenceManager = $this->getMockBuilder(PersistenceManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['remove', 'add', 'isNewObject'])
            ->getMockForAbstractClass();
        $this->subject->injectPersistenceManager($this->persistenceManager);
    }
}
