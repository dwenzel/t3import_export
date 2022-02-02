<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Initializer;

use CPSIT\T3importExport\Component\Initializer\UpdateTable;
use CPSIT\T3importExport\Tests\Unit\Traits\MockDatabaseTrait;
use PHPUnit\Framework\TestCase;

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
class UpdateTableTest extends TestCase
{
    use MockDatabaseTrait;

    protected UpdateTable $subject;

    public function setUp()
    {
        $this->mockConnectionPool()
            ->mockConnection();
        $this->subject = new UpdateTable($this->connectionPool);
    }

    /**
     * @covers ::isConfigurationValid
     * @dataProvider validConfigurationDataProvider
     */
    public function testIsConfigurationValidReturnsTrueForValidConfiguration($configuration): void
    {
        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     * @dataProvider invalidConfigurationDataProvider
     */
    public function testIsConfigurationValidReturnsFalseForInvalidConfiguration($configuration): void
    {
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }


    public function invalidConfigurationDataProvider(): array
    {
        return [
            'empty configuration' => [
                []
            ],
            'table value is integer' => [
                [
                    UpdateTable::KEY_TABLE => 3,
                    UpdateTable::KEY_WHERE => [],
                    UpdateTable::KEY_SET_FIELDS => ['baz' => 5]
                ]
            ],
            'table is float' => [
                [
                    UpdateTable::KEY_TABLE => 1.5,
                    UpdateTable::KEY_WHERE => [],
                    UpdateTable::KEY_SET_FIELDS => ['baz' => 5]
                ]
            ],
            'table is array' => [
                [
                    UpdateTable::KEY_TABLE => [],
                    UpdateTable::KEY_WHERE => [],
                    UpdateTable::KEY_SET_FIELDS => ['baz' => 5]
                ]
            ],
            'table is empty string' => [
                [
                    UpdateTable::KEY_TABLE => '',
                    UpdateTable::KEY_SET_FIELDS => ['baz' => 5]
                ]
            ],
            'where is integer' => [
                [
                    UpdateTable::KEY_TABLE => 'foo',
                    UpdateTable::KEY_WHERE => 1,
                    UpdateTable::KEY_SET_FIELDS => ['baz' => 5]
                ]
            ],
            'where is float' => [
                [
                    UpdateTable::KEY_TABLE => 'foo',
                    UpdateTable::KEY_WHERE => 1.7,
                    UpdateTable::KEY_SET_FIELDS => ['baz' => 5]
                ]
            ],
        ];
    }

    public function validConfigurationDataProvider(): array
    {
        return [
            'minimal' => [
                [
                    UpdateTable::KEY_TABLE => 'foo',
                    UpdateTable::KEY_SET_FIELDS => ['baz' => 5]
                ]
            ]
        ];
    }
}
