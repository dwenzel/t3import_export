<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

use CPSIT\T3importExport\Component\PreProcessor\LookUpDB;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
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
 * @coversDefaultClass \CPSIT\T3importExport\Component\PreProcessor\LookUpDB
 */
class LookUpDBTest extends TestCase
{
    /**
     * @var LookUpDB|MockObject
     */
    protected LookUpDB $subject;

    /**
     * @var ConnectionPool&MockObject
     */
    protected ConnectionPool $connectionPool;

    /**
     * @var DatabaseConnectionService&MockObject
     */
    protected DatabaseConnectionService $connectionService;

    /**
     * @var Connection&MockObject
     */
    protected Connection $connection;

    protected array $queryResult = [];

    /**
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function setUp(): void
    {
        /**
         * fixme: we mock the subject in order to prevent access to method performQuery
         * which uses now invalid database methods
         */
        // Create connection mock
        $this->connection = $this->createMock(Connection::class);

        // Create connection pool mock
        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->connectionPool->method('getConnectionForTable')
            ->willReturn($this->connection);

        // Create connection service mock
        $this->connectionService = $this->createMock(DatabaseConnectionService::class);
        $this->connectionService->method('getDatabase')->willReturn($this->connection);

        $this->subject = new LookUpDB(
            $this->connectionPool,
            $this->connectionService
        );
    }

    public function testProcess(): void
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
            'fields' => [],
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
            'select' => [],
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
                'table' => 1,
            ],
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
            'targetField' => 'foo',
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
            'source' => 'invalidStringValue',
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
                'table' => 'fooTable',
            ],
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $validConfiguration = [
            'targetField' => 'result_field',
            'select' => [
                'table' => 'tx_test_table',
                'fields' => 'uid,title',
            ],
        ];
        $this->assertTrue(
            $this->subject->isConfigurationValid($validConfiguration)
        );
    }

    /**
     * @covers ::getQueryConfiguration
     */
    public function testGetQueryConfigurationMergesDefaultConfiguration(): void
    {
        $inputConfiguration = [
            'select' => [
                'table' => 'tx_test_table',
                'fields' => 'uid,title',
                'limit' => '10',
            ],
        ];

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->subject);
        $method = $reflection->getMethod('getQueryConfiguration');
        $method->setAccessible(true);

        $result = $method->invoke($this->subject, $inputConfiguration);

        // Should contain merged configuration with defaults
        $this->assertEquals('tx_test_table', $result['table']);
        $this->assertEquals('uid,title', $result['fields']);
        $this->assertEquals('10', $result['limit']);
        // Should contain default values for unspecified fields
        $this->assertArrayHasKey('where', $result);
        $this->assertArrayHasKey('orderBy', $result);
        $this->assertArrayHasKey('groupBy', $result);
    }

    /**
     * @covers ::getQueryConfiguration
     */
    public function testGetQueryConfigurationOverridesDefaults(): void
    {
        $inputConfiguration = [
            'select' => [
                'table' => 'tx_test_table',
                'where' => 'deleted = 0',
                'orderBy' => 'title ASC',
            ],
        ];

        $reflection = new \ReflectionClass($this->subject);
        $method = $reflection->getMethod('getQueryConfiguration');
        $method->setAccessible(true);

        $result = $method->invoke($this->subject, $inputConfiguration);

        $this->assertEquals('deleted = 0', $result['where']);
        $this->assertEquals('title ASC', $result['orderBy']);
    }

    /**
     * @covers ::mapFields
     */
    public function testMapFieldsWithEmptyFieldsConfiguration(): void
    {
        $record = [];
        $source = ['uid' => 123, 'title' => 'Test Title'];
        $config = []; // No 'fields' key

        $reflection = new \ReflectionClass($this->subject);
        $method = $reflection->getMethod('mapFields');
        $method->setAccessible(true);

        $method->invoke($this->subject, $record, $source, $config);

        // Record should remain empty since no fields configuration is provided
        $this->assertEmpty($record);
    }

    /**
     * @covers ::mapFields
     */
    public function testMapFieldsWithInvalidFieldsConfiguration(): void
    {
        $record = [];
        $source = ['uid' => 123, 'title' => 'Test Title'];
        $config = ['fields' => 'invalid_string']; // fields should be array

        $reflection = new \ReflectionClass($this->subject);
        $method = $reflection->getMethod('mapFields');
        $method->setAccessible(true);

        $method->invoke($this->subject, $record, $source, $config);

        // Record should remain empty since fields configuration is invalid
        $this->assertEmpty($record);
    }

    /**
     * @covers ::mapFields
     */
    public function testMapFieldsMapsFieldsCorrectly(): void
    {
        $record = [];
        $source = [
            'uid' => 123,
            'title' => 'Test Title',
            'description' => 'Test Description',
        ];
        $config = [
            'fields' => [
                'uid' => ['mapTo' => 'id'],
                'title' => ['mapTo' => 'name'],
                'description' => ['mapTo' => 'desc'],
            ],
        ];

        $reflection = new \ReflectionClass($this->subject);
        $method = $reflection->getMethod('mapFields');
        $method->setAccessible(true);

        $method->invokeArgs($this->subject, [&$record, $source, $config]);

        $this->assertEquals(123, $record['id']);
        $this->assertEquals('Test Title', $record['name']);
        $this->assertEquals('Test Description', $record['desc']);
    }

    /**
     * @covers ::mapFields
     */
    public function testMapFieldsIgnoresFieldsWithoutMapTo(): void
    {
        $record = [];
        $source = ['uid' => 123, 'title' => 'Test Title'];
        $config = [
            'fields' => [
                'uid' => ['mapTo' => 'id'],
                'title' => ['someOtherConfig' => 'value'], // No mapTo
            ],
        ];

        $reflection = new \ReflectionClass($this->subject);
        $method = $reflection->getMethod('mapFields');
        $method->setAccessible(true);

        $method->invokeArgs($this->subject, [&$record, $source, $config]);

        $this->assertEquals(123, $record['id']);
        $this->assertArrayNotHasKey('title', $record);
        $this->assertCount(1, $record);
    }

    /**
     * @covers ::mapFields
     */
    public function testMapFieldsIgnoresFieldsWithInvalidMapTo(): void
    {
        $record = [];
        $source = ['uid' => 123, 'title' => 'Test Title'];
        $config = [
            'fields' => [
                'uid' => ['mapTo' => 'id'],
                'title' => ['mapTo' => 123], // mapTo should be string
            ],
        ];

        $reflection = new \ReflectionClass($this->subject);
        $method = $reflection->getMethod('mapFields');
        $method->setAccessible(true);

        $method->invokeArgs($this->subject, [&$record, $source, $config]);

        $this->assertEquals(123, $record['id']);
        $this->assertArrayNotHasKey('title', $record);
        $this->assertCount(1, $record);
    }

    /**
     * @covers ::parseQueryConstraints
     */
    public function testParseQueryConstraintsReturnsUnchangedConfigurationWhenNoWhere(): void
    {
        $record = ['name' => 'test'];
        $queryConfiguration = [
            'table' => 'tx_test_table',
            'fields' => '*',
        ];

        $reflection = new \ReflectionClass($this->subject);
        $method = $reflection->getMethod('parseQueryConstraints');
        $method->setAccessible(true);

        $result = $method->invoke($this->subject, $record, $queryConfiguration);

        $this->assertEquals($queryConfiguration, $result);
    }

    /**
     * @covers ::parseQueryConstraints
     */
    public function testParseQueryConstraintsReturnsUnchangedConfigurationWhenWhereIsNotArray(): void
    {
        $record = ['name' => 'test'];
        $queryConfiguration = [
            'table' => 'tx_test_table',
            'where' => 'deleted = 0', // string instead of array
        ];

        $reflection = new \ReflectionClass($this->subject);
        $method = $reflection->getMethod('parseQueryConstraints');
        $method->setAccessible(true);

        $result = $method->invoke($this->subject, $record, $queryConfiguration);

        $this->assertEquals($queryConfiguration, $result);
    }

    /**
     * @covers ::parseQueryConstraints
     */
    public function testParseQueryConstraintsBuildsSimpleAndCondition(): void
    {
        $record = ['name' => 'test_value'];
        $queryConfiguration = [
            'table' => 'tx_test_table',
            'where' => [
                'AND' => [
                    'condition' => 'name = ',
                    'value' => 'name',
                ],
            ],
        ];

        // Mock the connection to return a quoted value
        $this->connection->expects($this->once())
            ->method('quote')
            ->with('test_value')
            ->willReturn("'test_value'");

        $reflection = new \ReflectionClass($this->subject);
        $method = $reflection->getMethod('parseQueryConstraints');
        $method->setAccessible(true);

        $result = $method->invoke($this->subject, $record, $queryConfiguration);

        $this->assertEquals(" name = 'test_value'", $result['where']);
    }

    /**
     * @covers ::parseQueryConstraints
     */
    public function testParseQueryConstraintsBuildsAndConditionWithPrefix(): void
    {
        $record = ['category_id' => '123'];
        $queryConfiguration = [
            'table' => 'tx_test_table',
            'where' => [
                'AND' => [
                    'condition' => 'category = ',
                    'prefix' => 'cat_',
                    'value' => 'category_id',
                ],
            ],
        ];

        $this->connection->expects($this->once())
            ->method('quote')
            ->with('cat_123')
            ->willReturn("'cat_123'");

        $reflection = new \ReflectionClass($this->subject);
        $method = $reflection->getMethod('parseQueryConstraints');
        $method->setAccessible(true);

        $result = $method->invoke($this->subject, $record, $queryConfiguration);

        $this->assertEquals(" category = 'cat_123'", $result['where']);
    }
}
