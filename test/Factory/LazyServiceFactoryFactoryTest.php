<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\Factory;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\LazyServiceFactoryFactory;
use Zend\ServiceManager\Proxy\LazyServiceFactory;
use ZendTest\ServiceManager\TestAsset\InvokableObject;

/**
 * @covers \Zend\ServiceManager\Factory\LazyServiceFactoryFactory
 * @covers \Zend\ServiceManager\Proxy\LazyServiceFactory
 */
class LazyServiceFactoryFactoryTest extends TestCase
{
    public $proxyDir;

    public function setUp()
    {
        $this->proxyDir = sys_get_temp_dir() . '/zend-servicemanager-proxy';
        if (! is_dir($this->proxyDir)) {
            mkdir($this->proxyDir);
        }
    }

    public function tearDown()
    {
        if (! is_dir($this->proxyDir)) {
            return;
        }

        $this->removeDir($this->proxyDir);
    }

    public function removeDir($directory)
    {
        $handle = opendir($directory);
        while (false !== ($item = readdir($handle))) {
            if ('.' === $item || '..' === $item) {
                continue;
            }
            $path = $directory . '/' . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
                continue;
            }

            if (is_file($path)) {
                unlink($path);
                continue;
            }
        }
        closedir($handle);
        rmdir($directory);
    }

    public function listProxyFiles()
    {
        $rdi = new RecursiveDirectoryIterator($this->proxyDir);
        $rii = new RecursiveIteratorIterator($rdi);
        return new RegexIterator($rii, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
    }

    public function assertProxyDirEmpty()
    {
        $count = 0;
        foreach ($this->listProxyFiles() as $file) {
            $this->assertFail('Expected empty proxy directory; found files');
        }
        $this->assertEquals(0, $count);
    }

    public function assertProxyFileWritten()
    {
        $count = 0;
        foreach ($this->listProxyFiles() as $file) {
            $count += 1;
            break;
        }
        $this->assertNotEquals(0, $count);
    }

    public function testExceptionThrownWhenLazyServiceConfigMissing()
    {
        $container = $this->getMock(ContainerInterface::class);
        $factory   = new LazyServiceFactoryFactory();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Missing "lazy_services" config key'
        );

        $object = $factory($container, InvokableObject::class);
    }

    public function testExceptionThrownWhenLazyServiceConfigMissingClassMap()
    {
        $container = $this->getMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->will($this->returnValue([
                'lazy_services' => []
            ]));

        $factory = new LazyServiceFactoryFactory();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Missing "class_map" config key in "lazy_services"'
        );

        $object = $factory($container, InvokableObject::class);
    }

    public function testCanLoadLazyService()
    {
        $container = $this->getMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->will($this->returnValue([
                'lazy_services' => [
                    'class_map' => [
                        TestAsset\Foo::class => TestAsset\Foo::class,
                    ],
                ],
            ]));

        $factory  = new LazyServiceFactoryFactory();
        $instance = $factory($container, TestAsset\Foo::class);
        $this->assertInstanceOf(LazyServiceFactory::class, $instance);
    }

    public function testCanSetAllProxyOptions()
    {
        $container = $this->getMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->will($this->returnValue([
                'lazy_services' => [
                    'class_map' => [
                        TestAsset\Foo::class => TestAsset\Foo::class,
                    ],
                    'proxies_namespace'  => 'TestAssetProxy',
                    'proxies_target_dir' => $this->proxyDir,
                    'write_proxy_files'  => true,
                ],
            ]));

        $this->assertProxyDirEmpty();

        $lazyFactory = new LazyServiceFactoryFactory();
        $factory     = $lazyFactory($container, TestAsset\Foo::class);
        $this->assertInstanceOf(LazyServiceFactory::class, $factory);

        $instance    = $factory($container, TestAsset\Foo::class, function () {
            return new TestAsset\Foo();
        });

        $this->assertRegexp('/^TestAssetProxy\\\\/', get_class($instance));
        $this->assertProxyFileWritten();
    }

    /**
     * @covers \Zend\ServiceManager\Proxy\LazyServiceFactory::__invoke
     */
    public function testLazyServicesProxyToOriginal()
    {
        $container = $this->getMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->will($this->returnValue([
                'lazy_services' => [
                    'class_map' => [
                        InvokableObject::class => InvokableObject::class,
                    ],
                ],
            ]));

        $lazyFactory = new LazyServiceFactoryFactory();
        $factory     = $lazyFactory($container, InvokableObject::class);
        $this->assertInstanceOf(LazyServiceFactory::class, $factory);

        $test = $factory($container, InvokableObject::class, function () {
            return new InvokableObject(['foo' => 'bar']);
        });
        $this->assertInstanceOf(InvokableObject::class, $test);
        $options = $test->getOptions();
        $this->assertInternalType('array', $options);
        $this->assertEquals(['foo' => 'bar'], $options);
    }

    public function testReturnedLazyFactoryThrowsExceptionIfClassNotFoundInMapWhenInvoked()
    {
        $container = $this->getMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->will($this->returnValue([
                'lazy_services' => [
                    'class_map' => [
                        InvokableObject::class => InvokableObject::class,
                    ],
                ],
            ]));

        $lazyFactory = new LazyServiceFactoryFactory();
        $factory     = $lazyFactory($container, stdClass::class);
        $this->assertInstanceOf(LazyServiceFactory::class, $factory);

        $this->setExpectedException(ServiceNotFoundException::class, 'not found');
        $test = $factory($container, stdClass::class, function () {
            $this->fail('Callback for lazy service should never be reached');
        });
    }
}
