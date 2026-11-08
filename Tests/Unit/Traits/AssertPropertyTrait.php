<?php

namespace CPSIT\T3importExport\Tests\Unit\Traits;

/**
 * Replacement for assertAttributeSame(), which was removed in PHPUnit 9
 */
trait AssertPropertyTrait
{
    protected function assertPropertySame(mixed $expected, string $propertyName, object $object): void
    {
        if (!(new \ReflectionClass($object))->hasProperty($propertyName) && property_exists($object, $propertyName)) {
            // dynamic property, e.g. of stdClass
            self::assertSame($expected, $object->{$propertyName});
            return;
        }
        $class = new \ReflectionClass($object);
        while (!$class->hasProperty($propertyName) && $class->getParentClass() !== false) {
            $class = $class->getParentClass();
        }
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);

        self::assertSame($expected, $property->getValue($object));
    }
}
