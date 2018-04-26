<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Exception;

use InvalidArgumentException as SplInvalidArgumentException;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Initializer\InitializerInterface;

use function get_class;
use function gettype;
use function is_object;
use function sprintf;

/**
 * @inheritDoc
 */
class InvalidArgumentException extends SplInvalidArgumentException implements ExceptionInterface
{
    /**
     * @param mixed $initializer
     * @return self
     */
    public static function fromInvalidInitializer($initializer) : self
    {
        return new self(sprintf(
            'An invalid initializer was registered. Expected a callable or an'
            . ' instance of "%s"; received "%s"',
            InitializerInterface::class,
            is_object($initializer) ? get_class($initializer) : gettype($initializer)
        ));
    }

    /**
     * @param mixed $abstractFactory
     * @return self
     */
    public static function fromInvalidAbstractFactory($abstractFactory) : self
    {
        return new self(sprintf(
            'An invalid abstract factory was registered. Expected an instance of or a valid'
            . ' class name resolving to an implementation of "%s", but "%s" was received.',
            AbstractFactoryInterface::class,
            is_object($abstractFactory) ? get_class($abstractFactory) : gettype($abstractFactory)
        ));
    }
}
