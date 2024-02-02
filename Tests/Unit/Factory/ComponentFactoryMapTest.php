<?php

namespace CPSIT\T3importExport\Tests\Unit\Factory;

use CPSIT\T3importExport\Component\ComponentInterface;
use CPSIT\T3importExport\Component\Factory\NullComponentFactory;
use CPSIT\T3importExport\Exception\InvalidClassException;
use CPSIT\T3importExport\Factory\ComponentFactoryMap;
use DummyClass;
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

/**
 * Class DummyProduct
 * Does not implement ComponentInterface
 */
class DummyProduct {

}

class MissingProductImplementingComponentInterface implements ComponentInterface
{

}
class ComponentFactoryMapTest extends TestCase
{
    protected ComponentFactoryMap $subject;

    protected function setUp(): void
    {
        $this->subject = new ComponentFactoryMap();
    }

    public function testResolveThrowsErrorForInvalidProductClass(): void
    {
        $this->expectException(InvalidClassException::class);
        $this->expectExceptionCode(ComponentFactoryMap::INVALID_CLASS_CODE);
        $invalidClass = DummyProduct::class;

        $this->subject->resolve($invalidClass);
    }

    public function testResolveReturnsNullFactoryForMissingClass(): void
    {
        $this->assertSame(
            NullComponentFactory::class,
            $this->subject->resolve(MissingProductImplementingComponentInterface::class)
        );
    }

    /**
     * @param string $productClass
     * @param string $factoryClass
     * @throws InvalidClassException
     * @dataProvider validClassDataProvider
     */
    public function testResolveReturnsValidFactoryClassForValidProductClass(string $productClass, string $factoryClass): void
    {
        $this->assertSame(
            $factoryClass,
            $this->subject->resolve($productClass)
        );
    }

    public function validClassDataProvider(): array
    {
        $data = [];
        foreach (ComponentFactoryMap::FACTORY_MAP as $product => $factory) {
            $data[$product] = [$product, $factory];
        }

        return $data;
    }
}
