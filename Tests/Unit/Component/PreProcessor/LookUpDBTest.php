<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

use CPSIT\T3importExport\Component\PreProcessor\LookUpDB;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use CPSIT\T3importExport\Tests\Unit\Traits\MockDatabaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/***************************************************************
 *  Copyright notice
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
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

/**
 * Class LookUpDBTest
 *
 * @package CPSIT\T3importExport\Tests\Service\PreProcessor
 * @coversDefaultClass \CPSIT\T3importExport\Component\PreProcessor\LookUpDB
 */
class LookUpDBTest extends TestCase
{
    use MockDatabaseTrait;

    /**
     * @var LookUpDB|MockObject
     */
    protected LookUpDB $subject;

    /**
     * @var array
     */
    protected array $queryResult = [];

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    protected function setUp(): void
    {
        /**
         * fixme: we mock the subject in order to prevent access to method performQuery
         * which uses now invalid database methods
         */
        $this->mockConnectionService();
        $this->subject = new LookUpDB(
            $this->connectionPool,
            $this->connectionService
        );
    }

    public function testProcess(): never
    {
        /**
         * @see LookUpDB::process() for details
         */
        $this->markTestIncomplete('method process is still to complex to test it. ');
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfTargetFieldIsNotSet(): void
    {
        $mockConfiguration = ['foo'];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfTargetFieldIsNotString(): void
    {
        $mockConfiguration = [
            'targetField' => 1,
            'fields' => []
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfTableIsNotSet(): void
    {
        $mockConfiguration = [
            'select' => []
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfTableIsNotString(): void
    {
        $mockConfiguration = [
            'select' => [
                'table' => 1
            ]
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfSourceIsNotSet(): void
    {
        $mockConfiguration = [
            'targetField' => 'foo'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfSourceIsNotArray(): void
    {
        $mockConfiguration = [
            'targetField' => 'foo',
            'source' => 'invalidStringValue'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function tesIsConfigurationValidReturnsFalseForInvalidIdentifier(): void
    {
        $mockConfiguration = [
            'identifier' => [],
            'select' => [
                'table' => 'fooTable'
            ],
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * Returns the subject with a connection which quotes values
     */
    protected function getSubjectWithQuotingConnection(): LookUpDB
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['quote'])
            ->getMock();
        $connection->method('quote')
            ->willReturnCallback(
                static fn($value): string => "'" . str_replace("'", "''", (string)$value) . "'"
            );
        $connectionPool = $this->getMockBuilder(ConnectionPool::class)
            ->onlyMethods(['getConnectionForTable'])
            ->getMock();
        $connectionPool->method('getConnectionForTable')->willReturn($connection);

        return new LookUpDB($connectionPool, $this->connectionService);
    }

    /**
     * @covers ::parseQueryConstraints
     */
    public function testParseQueryConstraintsQuotesValuesOfInConstraint(): void
    {
        $subject = $this->getSubjectWithQuotingConnection();
        $record = ['titles' => ['foo', 'bar") OR ("1"="1', "baz') OR ('1'='1"]];
        $queryConfiguration = [
            'table' => 'tx_test_table',
            'where' => [
                'IN' => [
                    'field' => 'title',
                    'values' => [
                        'field' => 'titles',
                        'prefix' => 'p_',
                    ],
                ],
            ],
        ];

        $method = new \ReflectionMethod($subject, 'parseQueryConstraints');
        $method->setAccessible(true);
        $result = $method->invoke($subject, $record, $queryConfiguration);

        $this->assertSame(
            " title IN ('p_foo','p_bar\") OR (\"1\"=\"1','p_baz'') OR (''1''=''1') ",
            $result['where']
        );
    }

    /**
     * @covers ::parseQueryConstraints
     */
    public function testParseQueryConstraintsQuotesValuesForKeepOrder(): void
    {
        $subject = $this->getSubjectWithQuotingConnection();
        $record = ['titles' => ["foo') OR ('1'='1", 'bar']];
        $queryConfiguration = [
            'table' => 'tx_test_table',
            'where' => [
                'IN' => [
                    'field' => 'title',
                    'keepOrder' => 1,
                    'values' => [
                        'field' => 'titles',
                    ],
                ],
            ],
        ];

        $method = new \ReflectionMethod($subject, 'parseQueryConstraints');
        $method->setAccessible(true);
        $result = $method->invoke($subject, $record, $queryConfiguration);

        $quotedList = "'foo'') OR (''1''=''1','bar'";
        $this->assertSame(
            ' title IN (' . $quotedList . ') ORDER BY FIELD (title,' . $quotedList . ')',
            $result['where']
        );
    }
}
