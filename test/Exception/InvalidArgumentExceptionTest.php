<?php
/**
 * @link      https://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\Exception;

use PHPUnit\Framework\TestCase;
use stdClass;
use Zend\ServiceManager\Initializer\InitializerInterface;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use Zend\ServiceManager\AbstractFactoryInterface;

/**
 * @covers \Zend\ServiceManager\Exception\InvalidArgumentException
 */
class InvalidArgumentExceptionTest extends TestCase
{

    public function testFromInvalidInitializer()
    {
        $exception = InvalidArgumentException::fromInvalidInitializer(new stdClass());
        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertSame(
            'An invalid initializer was registered. Expected a valid function name, '
            . 'class name, a callable or an instance of "'. InitializerInterface::class
            . '", but "stdClass" was received.'
            , $exception->getMessage());

        // because the named constructor does not check if classes or functions exist
        // or the argument is_callable or an instance of InitializerInterface
        // we are done here
    }

    public function testFromInvalidAbstractFactory()
    {
        $exception = InvalidArgumentException::fromInvalidAbstractFactory(new stdClass());
        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertSame('An invalid abstract factory was registered. Expected an instance of or a valid'
            . ' class name resolving to an implementation of "'. AbstractFactoryInterface::class
            . '", but "stdClass" was received.', $exception->getMessage());

        // because the named constructor does not check if classes or functions exist
        // or the argument is_callable or an instance of InitializerInterface
        // we are done here
    }
}
