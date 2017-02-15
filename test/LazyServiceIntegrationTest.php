<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager;

use PHPUnit\Framework\TestCase;
use ProxyManager\Autoloader\AutoloaderInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use stdClass;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\Proxy\LazyServiceFactory;
use Zend\ServiceManager\ServiceManager;
use ZendTest\ServiceManager\TestAsset\InvokableObject;

/**
 * @covers \Zend\ServiceManager\ServiceManager
 */
class LazyServiceIntegrationTest extends TestCase
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
        foreach ($this->getRegisteredProxyAutoloadFunctions() as $autoloader) {
            spl_autoload_unregister($autoloader);
        }
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

    public function assertProxyDirEmpty($message = '')
    {
        $message = $message ?: 'Expected empty proxy directory; found files';
        // AssertEquals instead AssertEmpty because the first one prints the list of files.
        $this->assertEquals([], iterator_to_array($this->listProxyFiles()), $message);
    }

    public function assertProxyFileWritten($message = '')
    {
        $message = $message ?: 'Expected ProxyManager to write at least one class file; none found';
        // AssertNotEquals instead AssertNotEmpty because the first one prints the list of files.
        $this->assertNotEquals([], iterator_to_array($this->listProxyFiles()), $message);
    }

    /**
     * @covers \Zend\ServiceManager\ServiceManager::createLazyServiceDelegatorFactory
     */
    public function testCanUseLazyServiceFactoryFactoryToCreateLazyServiceFactoryToActAsDelegatorToCreateLazyService()
    {
        $config = [
            'lazy_services' => [
                'class_map' => [
                    InvokableObject::class => InvokableObject::class,
                ],
                'proxies_namespace'  => 'TestAssetProxy',
                'proxies_target_dir' => $this->proxyDir,
                'write_proxy_files'  => true,
            ],
            'factories' => [
                InvokableObject::class => InvokableFactory::class,
            ],
            'delegators' => [
                InvokableObject::class => [LazyServiceFactory::class],
            ],
        ];

        $this->assertProxyDirEmpty();

        $container = new ServiceManager($config);
        $instance  = $container->build(InvokableObject::class, ['foo' => 'bar']);

        $this->assertProxyFileWritten();

        // Test we got a usable proxy
        $this->assertInstanceOf(
            InvokableObject::class,
            $instance,
            'Service returned does not extend ' . InvokableObject::class
        );
        $this->assertContains(
            'TestAssetProxy',
            get_class($instance),
            'Service returned does not contain expected namespace'
        );

        // Test proxying works as expected
        $options = $instance->getOptions();
        $this->assertInternalType(
            'array',
            $options,
            'Expected an array of options'
        );
        $this->assertEquals(['foo' => 'bar'], $options, 'Options returned do not match configuration');

        $proxyAutoloadFunctions = $this->getRegisteredProxyAutoloadFunctions();
        $this->assertCount(1, $proxyAutoloadFunctions, 'Only 1 proxy autoloader should be registered');
    }

    /**
     * @covers \Zend\ServiceManager\ServiceManager::createLazyServiceDelegatorFactory
     */
    public function testMissingClassMapRaisesExceptionOnAttemptToRetrieveLazyService()
    {
        $config = [
            'lazy_services' => [
            ],
            'factories' => [
                InvokableObject::class => InvokableFactory::class,
            ],
            'delegators' => [
                InvokableObject::class => [LazyServiceFactory::class],
            ],
        ];

        $container = new ServiceManager($config);
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('class_map');
        $container->get(InvokableObject::class);
    }

    /**
     * @covers \Zend\ServiceManager\ServiceManager::createLazyServiceDelegatorFactory
     */
    public function testWillNotGenerateProxyClassFilesByDefault()
    {
        $config = [
            'lazy_services' => [
                'class_map' => [
                    InvokableObject::class => InvokableObject::class,
                ],
                'proxies_namespace'  => 'TestAssetProxy',
            ],
            'factories' => [
                InvokableObject::class => InvokableFactory::class,
            ],
            'delegators' => [
                InvokableObject::class => [LazyServiceFactory::class],
            ],
        ];

        $this->assertProxyDirEmpty();

        $container = new ServiceManager($config);
        $instance  = $container->build(InvokableObject::class, ['foo' => 'bar']);

        // This is the important test
        $this->assertProxyDirEmpty('Expected proxy directory to remain empty when write_proxy_files disabled');

        // Test we got a usable proxy
        $this->assertInstanceOf(
            InvokableObject::class,
            $instance,
            'Service returned does not extend ' . InvokableObject::class
        );
        $this->assertContains(
            'TestAssetProxy',
            get_class($instance),
            'Service returned does not contain expected namespace'
        );

        // Test proxying works as expected
        $options = $instance->getOptions();
        $this->assertInternalType(
            'array',
            $options,
            'Expected an array of options'
        );
        $this->assertEquals(['foo' => 'bar'], $options, 'Options returned do not match configuration');

        $proxyAutoloadFunctions = $this->getRegisteredProxyAutoloadFunctions();
        $this->assertCount(1, $proxyAutoloadFunctions, 'Only 1 proxy autoloader should be registered');
    }

    public function testOnlyOneProxyAutoloaderItsRegisteredOnSubsequentCalls()
    {
        $config = [
            'lazy_services' => [
                'class_map' => [
                    InvokableObject::class => InvokableObject::class,
                    stdClass::class => stdClass::class,
                ],
                'proxies_namespace'  => 'TestAssetProxy',
            ],
            'factories' => [
                InvokableObject::class => InvokableFactory::class,
            ],
            'delegators' => [
                InvokableObject::class => [LazyServiceFactory::class],
                stdClass::class => [LazyServiceFactory::class],
            ],
        ];

        $container = new ServiceManager($config);
        $instance  = $container->build(InvokableObject::class, ['foo' => 'bar']);
        $this->assertInstanceOf(
            InvokableObject::class,
            $instance,
            'Service returned does not extend ' . InvokableObject::class
        );
        $instance  = $container->build(stdClass::class, ['foo' => 'bar']);
        $this->assertInstanceOf(
            stdClass::class,
            $instance,
            'Service returned does not extend ' . stdClass::class
        );

        $proxyAutoloadFunctions = $this->getRegisteredProxyAutoloadFunctions();
        $this->assertCount(1, $proxyAutoloadFunctions, 'Only 1 proxy autoloader should be registered');
    }

    public function testRaisesServiceNotFoundExceptionIfRequestedLazyServiceIsNotInClassMap()
    {
        $config = [
            'lazy_services' => [
                'class_map' => [
                    stdClass::class => stdClass::class,
                ],
                'proxies_namespace'  => 'TestAssetProxy',
            ],
            'factories' => [
                InvokableObject::class => InvokableFactory::class,
            ],
            'delegators' => [
                InvokableObject::class => [LazyServiceFactory::class],
            ],
        ];

        $this->assertProxyDirEmpty();

        $container = new ServiceManager($config);

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('not found in the provided services map');
        $container->build(InvokableObject::class, ['foo' => 'bar']);
    }

    /**
     * @return AutoloaderInterface[]
     */
    protected function getRegisteredProxyAutoloadFunctions()
    {
        $filter = function ($autoload) {
            return ($autoload instanceof AutoloaderInterface);
        };

        return array_filter(spl_autoload_functions(), $filter);
    }
}
