<?php
namespace CPSIT\T3importExport\Tests\Unit\Persistence\Query;

use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\Persistence\Query\QueryInterface;
use CPSIT\T3importExport\Persistence\Query\SelectQuery;
use CPSIT\T3importExport\Tests\Unit\Traits\MockDatabaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

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

class SelectQueryTest extends TestCase
{
    use MockDatabaseTrait;

    protected SelectQuery $subject;

    /**
     * @var QueryBuilder|MockObject
     */
    protected QueryBuilder $builder;

    public function setUp(): void
    {
        $fluidBuilderMethods = [
            'select',
            'from',
            'where',
            'groupBy',
            'orderBy',
            'addOrderBy',
            'setMaxResults',
            'limit',
        ];

        $this->builder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods($fluidBuilderMethods)
            ->getMock();

        $this->mockConnectionService();
        $this->connection->method('createQueryBuilder')->willReturn($this->builder);
        $this->subject = new SelectQuery($this->connectionPool, $this->connectionService);
        foreach ($fluidBuilderMethods as $method) {
            $this->builder->method($method)->willReturn($this->builder);
        }
    }


    /**
     * valid config:
     * [
     *   'table' => 'foo',
     *   'where' => 'bar=4'
     *   'groupBy => 'baz'
     *   'orderBy' => 'boom'
     *   'limit' => '3'
     * ]
     * any key except `table` is optional
     *
     * @return array[]
      */
    public function configurationDataProvider(): array
    {
        return [
            'where' => [['table' => 'foo', 'where' => 'bar'], 'where', 'bar'],
            'limit' => [['table' => 'foo', 'limit' => '2'], 'setMaxResults', 2],
            'orderBy' => [['table' => 'foo', 'orderBy' => 'moo'], 'addOrderBy', 'moo'],
            'groupBy' => [['table' => 'foo', 'groupBy' => 'foo'], 'groupBy', 'foo']
        ];
    }

    public function testWithConfigurationThrowsExceptionForMissingTable(): void
    {
        $missingFieldName = QueryInterface::TABLE;
        $config = [];

        $expectedMessage = sprintf(SelectQuery::MESSAGE_MISSING_FIELD, $missingFieldName);
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(SelectQuery::CODE_MISSING_FIELD);
        $this->expectExceptionMessage($expectedMessage);

        $this->subject->withConfiguration($config);
    }
    /**
     * @param array $config
     * @param string $expectedMethod
     * @param $expectedValue
     * @dataProvider configurationDataProvider
     * @throws InvalidConfigurationException
     */
    public function testWithConfigurationConfiguresQueryBuilder(array $config, string $expectedMethod, $expectedValue): void
    {
        $this->connectionPool->expects($this->once())
            ->method('getConnectionForTable')
            ->with($config[QueryInterface::TABLE])
            ->willReturn($this->connection);

        $this->connection->expects($this->once())
            ->method('createQueryBuilder');

        $this->builder->expects($this->once())
            ->method($expectedMethod)
            ->with(...[$expectedValue]);

        $this->subject->withConfiguration($config)->setQuery();
    }
}
