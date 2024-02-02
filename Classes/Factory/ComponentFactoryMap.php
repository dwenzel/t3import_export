<?php

namespace CPSIT\T3importExport\Factory;

use CPSIT\T3importExport\Component\Converter\ConverterInterface;
use CPSIT\T3importExport\Component\Factory\ConverterFactory;
use CPSIT\T3importExport\Component\Factory\FinisherFactory;
use CPSIT\T3importExport\Component\Factory\InitializerFactory;
use CPSIT\T3importExport\Component\Factory\NullComponentFactory;
use CPSIT\T3importExport\Component\Factory\PostProcessorFactory;
use CPSIT\T3importExport\Component\Factory\PreProcessorFactory;
use CPSIT\T3importExport\Component\Finisher\FinisherInterface;
use CPSIT\T3importExport\Component\Initializer\InitializerInterface;
use CPSIT\T3importExport\Component\PostProcessor\PostProcessorInterface;
use CPSIT\T3importExport\Component\PreProcessor\PreProcessorInterface;
use CPSIT\T3importExport\Exception\InvalidClassException;
use CPSIT\T3importExport\Component\ComponentInterface;
use CPSIT\T3importExport\Persistence\DataSourceInterface;
use CPSIT\T3importExport\Persistence\DataTargetInterface;
use CPSIT\T3importExport\Persistence\Factory\DataSourceFactory;
use CPSIT\T3importExport\Persistence\Factory\DataTargetFactory;

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
 * Class ComponentFactoryMap
 *
 * Provides a class name for a factory capable of producing a product
 */
class ComponentFactoryMap implements FactoryMapInterface
{
    final public const INVALID_CLASS_MESSAGE = 'Cannot resolve factory for product class "%s". Product must implement "%s"!';
    final public const INVALID_CLASS_CODE = 1_643_977_630;
    final public const FACTORY_MAP = [
        ConverterInterface::class => ConverterFactory::class,
        DataSourceInterface::class => DataSourceFactory::class,
        DataTargetInterface::class => DataTargetFactory::class,
        FinisherInterface::class => FinisherFactory::class,
        InitializerInterface::class => InitializerFactory::class,
        PostProcessorInterface::class => PostProcessorFactory::class,
        PreProcessorInterface::class => PreProcessorFactory::class,
    ];

    /**
     * @inheritDoc
     */
    public function resolve(string $productClass): string
    {
        $factoryClass = NullComponentFactory::class;
        if (!in_array(ComponentInterface::class, class_implements($productClass))) {
            $message = sprintf(self::INVALID_CLASS_MESSAGE, $productClass, ComponentInterface::class);
            throw new InvalidClassException(
                $message,
                self::INVALID_CLASS_CODE
            );
        }

        if(array_key_exists($productClass, static::FACTORY_MAP))
        {
            $factoryClass = static::FACTORY_MAP[$productClass];
        }

        return $factoryClass;
    }
}
