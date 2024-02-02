<?php

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

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

use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\Persistence\DataTargetDB;
use CPSIT\T3importExport\Tests\Unit\Traits\MockDatabaseTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class DataTargetDBTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence
 */
class DataTargetDBTest extends TestCase
{
    use MockDatabaseTrait;

    public const VALID_CONFIG_EMPTY_FIELD = [
        DataTargetDB::FIELD_TABLE => 'foo',
        DataTargetDB::FIELD_SKIP => [
            DataTargetDB::FIELD_IF_EMPTY => [
                DataTargetDB::FIELD_FIELD => 'bar'
            ]
        ]
    ];

    public const VALID_CONFIG_NOT_EMPTY_FIELD = [
        DataTargetDB::FIELD_TABLE => 'foo',
        DataTargetDB::FIELD_SKIP => [
            DataTargetDB::FIELD_IF_NOT_EMPTY => [
                DataTargetDB::FIELD_FIELD => 'bar'
            ]
        ]
    ];

    /**
     * @var DataTargetDB
     */
    protected $subject;

    /**
     * set up subject
     */
    protected function setUp(): void
    {
        $this->mockConnectionService();
        $this->connectionPool->method('getConnectionForTable')
            ->willReturn($this->connection);

        $this->subject = new DataTargetDB($this->connectionPool, $this->connectionService);
    }

    public function invalidConfigurationDataProvider(): array
    {
        return [
            'missing field table' => [
                []
            ],
            'field `unsetKeys` is not string' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_UNSET_KEYS => []
                ]
            ],
            'skip must not be string' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => 'bar'
                ]
            ],
            'skip must not be empty' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => []
                ]
            ],
            'ifEmpty must not be string' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [
                        DataTargetDB::FIELD_IF_EMPTY => 'baz'
                    ]
                ]
            ],
            'ifEmpty must not be empty' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [
                        DataTargetDB::FIELD_IF_EMPTY => []
                    ]
                ]
            ],
            'ifNotEmpty must not be string' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [
                        DataTargetDB::FIELD_IF_NOT_EMPTY => 'baz'
                    ]
                ]
            ],
            'ifNotEmpty must not be empty' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [
                        DataTargetDB::FIELD_IF_NOT_EMPTY => []
                    ]
                ]
            ],
            'ifEmpty.field must not be array' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [
                        DataTargetDB::FIELD_IF_EMPTY => [
                            DataTargetDB::FIELD_FIELD => []
                        ]
                    ]
                ]
            ],
            'ifEmpty.field must not be empty' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [
                        DataTargetDB::FIELD_IF_EMPTY => [
                            DataTargetDB::FIELD_FIELD => ''
                        ]
                    ]
                ]
            ],
            'ifNotEmpty.field must not be array' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [
                        DataTargetDB::FIELD_IF_NOT_EMPTY => [
                            DataTargetDB::FIELD_FIELD => []
                        ]
                    ]
                ]
            ],
            'ifNotEmpty.field must not be empty' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_SKIP => [
                        DataTargetDB::FIELD_IF_NOT_EMPTY => [
                            DataTargetDB::FIELD_FIELD => ''
                        ]
                    ]
                ]
            ],

        ];
    }

    /**
     * @param array $invalidConfig
     * @dataProvider invalidConfigurationDataProvider
     */
    public function testIsConfigurationValidReturnsFalseForInvalidConfig(array $invalidConfig): void
    {
        self::assertFalse(
            $this->subject->isConfigurationValid($invalidConfig)
        );
    }

    public function validConfigurationDataProvider(): array
    {
        return [
            'minimal config' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo'
                ]
            ],
            'table + unsetKeys ' => [
                [
                    DataTargetDB::FIELD_TABLE => 'foo',
                    DataTargetDB::FIELD_UNSET_KEYS => 'bar,baz'
                ]
            ],
            'skip if field `bar` is empty' => [
                self::VALID_CONFIG_EMPTY_FIELD
            ],
            'skip if field `bar` is not empty' => [
                self::VALID_CONFIG_NOT_EMPTY_FIELD
            ],
        ];
    }

    /**
     * @param array $configuration
     * @dataProvider validConfigurationDataProvider
     */
    public function testIsConfigurationValidReturnsTrueForValidConfiguration(array $configuration): void
    {
        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function persistUnSetsConfiguredKeys()
    {

        $tableName = 'baz';
        $keyToUnset = 'foo';
        $configuration = [
            DataTargetDB::FIELD_TABLE => $tableName,
            DataTargetDB::FIELD_UNSET_KEYS => $keyToUnset
        ];

        $record = [
            $keyToUnset => 'bar'
        ];
        $expectedRecord = [];
        $this->connection->expects($this->once())
            ->method('insert')
            ->with(
                ...[
                    $tableName,
                    $expectedRecord
                ]
            );

        $this->subject->persist(
            $record,
            $configuration
        );
    }

    /**
     * @test
     */
    public function persistUpdatesRecordsWithIdentityKey()
    {
        $tableName = 'baz';
        $configuration = [
            DataTargetDB::FIELD_TABLE => $tableName,
        ];

        $identity = 'foo';
        $record = [
            DataTargetDB::DEFAULT_IDENTITY_FIELD => $identity,
            'barField' => 'boom'
        ];

        $expectedIdentifiers = ['uid' => $identity];
        $expectedRecord = [
            'barField' => 'boom'
        ];
        $this->connection->expects($this->once())
            ->method('update')
            ->with(
                ...[
                $tableName,
                $expectedRecord,
                $expectedIdentifiers
            ]);

        $this->subject->persist(
            $record,
            $configuration
        );
    }

    public function skipIfDataProvider(): array
    {
        return [
            // $configuration, $record
            'skip b/c field `bar` is empty string' => [
                self::VALID_CONFIG_EMPTY_FIELD,
                ['bar' => '']
            ],
            'skip b/c field `bar` is empty array' => [
                self::VALID_CONFIG_EMPTY_FIELD,
                ['bar' => []]
            ],
            'skip b/c field `bar` is not empty string' => [
                self::VALID_CONFIG_NOT_EMPTY_FIELD,
                ['bar' => 'lala']
            ],
            'skip b/c field `bar` is not empty array' => [
                self::VALID_CONFIG_NOT_EMPTY_FIELD,
                ['bar' => ['baz']]
            ],
            'skip b/c field `bar` is not empty but float' => [
                self::VALID_CONFIG_NOT_EMPTY_FIELD,
                ['bar' => 3.12]
            ],
        ];
    }

    /**
     * @param array $configuration
     * @param array $record
     * @throws InvalidConfigurationException
     * @dataProvider skipIfDataProvider
     */
    public function testPersistSkipsIfRecordMatchesCondition(array $configuration, array $record): void
    {
        self::assertFalse(
            $this->subject->persist($record, $configuration)
        );

        $this->connectionPool->expects(self::never())
            ->method('getConnectionForTable');
    }
}
