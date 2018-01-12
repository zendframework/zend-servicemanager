<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Exception;

use InvalidArgumentException as SplInvalidArgumentException;
use Zend\ServiceManager\Initializer\InitializerInterface;
use Zend\ServiceManager\AbstractFactoryInterface;

/**
 * @inheritDoc
 */
class InvalidArgumentException extends SplInvalidArgumentException implements ExceptionInterface
{
    public static function fromInvalidInitializer($initializer)
    {
        return new self(sprintf(
            'An invalid initializer was registered. Expected a callable, or an instance of '
            . '(or valid class name or function name resolving to) "%s", '
            . 'but "%s" was received',
            InitializerInterface::class,
            (is_object($initializer) ? get_class($initializer) : gettype($initializer))
        ));
    }

    public static function fromInvalidAbstractFactory($abstractFactory)
    {
        return new self(sprintf(
            'An invalid abstract factory was registered. Expected an instance of or a '
            . 'valid class name resolving to an implementation of "%s", but "%s" was received.',
            AbstractFactoryInterface::class,
            (is_object($abstractFactory) ? get_class($abstractFactory) : gettype($abstractFactory))
        ));
    }
}
