<?php
namespace CPSIT\T3importExport\Tests\Unit;

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
use CPSIT\T3importExport\DatabaseTrait;
use CPSIT\T3importExport\Persistence\DataTargetDB;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class DatabaseTraitTest
 *
 * @package CPSIT\T3importExport\Tests\Unit
 */
class DatabaseTraitTest extends UnitTestCase
{
    /**
     * @var DatabaseTrait
     */
    protected $subject;

    /**
     * set up subject
     */
    public function setUp()
    {
        $this->subject = $this->getMockForTrait(
            DatabaseTrait::class, [], '', false
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockDatabase()
    {
        $mockDatabase = $this->getMock(
            DatabaseConnection::class,
            ['exec_INSERTquery', 'exec_UPDATEquery'],
            [],
            '', false
        );

        $this->inject(
            $this->subject,
            'database',
            $mockDatabase
        );

        return $mockDatabase;
    }

    /**
     * @test
     */
    public function constructorSetsDatabase()
    {
        $GLOBALS['TYPO3_DB'] = $this->getMock(
            DatabaseConnection::class, [], [], '', false
        );

        $this->subject->__construct();
        $this->assertAttributeSame(
            $GLOBALS['TYPO3_DB'],
            'database',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function databaseConnectionServiceCanBeInjected()
    {
        /** @var DatabaseConnectionService $expectedConnectionService */
        $expectedConnectionService = $this->getAccessibleMock(DatabaseConnectionService::class,
            ['dummy'], [], '', false);

        $this->subject->injectDatabaseConnectionService($expectedConnectionService);

        $this->assertAttributeSame(
            $expectedConnectionService,
            'connectionService',
            $this->subject
        );
    }
}
