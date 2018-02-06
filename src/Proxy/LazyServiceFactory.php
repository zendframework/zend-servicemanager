<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace Mxc\ServiceManager\Proxy;

use Interop\Container\ContainerInterface;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Mxc\ServiceManager\Exception;
use Mxc\ServiceManager\Factory\DelegatorFactoryInterface;

use function sprintf;

/**
 * Delegator factory responsible of instantiating lazy loading value holder proxies of
 * given services at runtime
 *
 * @link https://github.com/Ocramius/ProxyManager/blob/master/docs/lazy-loading-value-holder.md
 */
final class LazyServiceFactory implements DelegatorFactoryInterface
{
    /**
     * @var \ProxyManager\Factory\LazyLoadingValueHolderFactory
     */
    private $proxyFactory;

    /**
     * @var string[] map of service names to class names
     */
    private $servicesMap;

    /**
     * @param LazyLoadingValueHolderFactory $proxyFactory
     * @param string[]                      $servicesMap  a map of service names to class names of their
     *                                                    respective classes
     */
    public function __construct(LazyLoadingValueHolderFactory $proxyFactory, array $servicesMap)
    {
        $this->proxyFactory = $proxyFactory;
        $this->servicesMap  = $servicesMap;
    }

    /**
     * {@inheritDoc}
     *
     * @return \ProxyManager\Proxy\VirtualProxyInterface
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null)
    {
        $initializer = function (&$wrappedInstance, LazyLoadingInterface $proxy) use ($callback) {
            $proxy->setProxyInitializer(null);
            $wrappedInstance = $callback();

            return true;
        };

        if (isset($this->servicesMap[$name])) {
            return $this->proxyFactory->createProxy($this->servicesMap[$name], $initializer);
        }

        throw new Exception\ServiceNotFoundException(
            sprintf('The requested service "%s" was not found in the provided services map', $name)
        );
    }
}
