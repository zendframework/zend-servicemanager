<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager;

use PHPUnit_Framework_TestCase as TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\Proxy\LazyServiceFactory;
use Zend\ServiceManager\ServiceManager;
use ZendTest\ServiceManager\TestAsset\InvokableObject;

/**
 * @covers \Zend\ServiceManager\Proxy\LazyServiceFactory
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
        $this->assertInstanceOf(InvokableObject::class, $instance);
        $this->assertContains('TestAssetProxy', get_class($instance));

        // Test proxying works as expected
        $options = $instance->getOptions();
        $this->assertInternalType('array', $options);
        $this->assertEquals(['foo' => 'bar'], $options);
    }
}
