<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager;

use Exception;
use Interop\Container\ContainerInterface;
use ProxyManager\Configuration as ProxyConfiguration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Initializer\InitializerInterface;

/**
 * Service Manager
 */
class ServiceManager implements ServiceLocatorInterface
{
    /**
     * A list of factories (either as string name or callable)
     *
     * @var string[]|callable[]
     */
    protected $factories = [];

    /**
     * @var AbstractFactoryInterface[]
     */
    protected $abstractFactories = [];

    /**
     * @var string[]|DelegatorFactoryInterface[]
     */
    protected $delegators = [];

    /**
     * @var array
     */
    protected $lazyServices = [];

    /**
     * @var Proxy\LazyServiceFactory
     */
    protected $lazyServicesDelegator;

    /**
     * @var InitializerInterface[]
     */
    protected $initializers = [];

    /**
     * A list of aliases
     *
     * Should map one alias to a service name, or another alias (aliases are recursively resolved)
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * A list of already loaded services (this act as a local cache)
     *
     * @var array
     */
    protected $services = [];

    /**
     * Should the services be shared by default?
     *
     * @var bool
     */
    protected $sharedByDefault = true;

    /**
     * Allow to activate/deactivate shared per service name
     *
     * Example configuration:
     *
     * 'shared' => [
     *     MyService::class => true, // will be shared, even if "sharedByDefault" is false
     *     MyOtherService::class => false // won't be shared, even if "sharedByDefault" is true
     * ]
     *
     * @var array
     */
    protected $shared = [];

    /**
     * @var ContainerInterface
     */
    protected $creationContext;

    /**
     * Constructor.
     *
     * See {@see \Zend\ServiceManager\ServiceManager::configure()} for details
     * on what $config accepts.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->creationContext = $this;
        $this->configure($config);
    }

    /**
     * Create a new service locator that merges the provided configuration.
     *
     * Note that the original service locator is left untouched (as with PSR-7
     * interfaces).
     *
     * See {@see \Zend\ServiceManager\ServiceManager::configure()} for details
     * on what $config accepts.
     *
     * @param  array $config
     * @return ContainerInterface
     */
    public function withConfig(array $config)
    {
        $container                  = clone $this;
        $container->creationContext = $container;
        $container->configure($config);
        return $container;
    }

    /**
     * {@inheritDoc}
     */
    public function get($name)
    {
        $name = $this->resolveAlias($name);

        // We start by checking if the service is cached (this is the fastest method).
        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        $object = $this->doCreate($name);

        if (($this->sharedByDefault && !isset($this->shared[$name]))
            || (isset($this->shared[$name]) && $this->shared[$name])) {
            $this->services[$name] = $object;
        }

        return $object;
    }

    /**
     * {@inheritDoc}
     */
    public function build($name, array $options = null)
    {
        // We never cache when using "build"
        return $this->doCreate($this->resolveAlias($name), $options);
    }

    /**
     * {@inheritDoc}
     */
    public function has($name, $checkAbstractFactories = false)
    {
        $name  = $this->resolveAlias($name);
        $found = isset($this->services[$name]) || isset($this->factories[$name]);

        if ($found || !$checkAbstractFactories) {
            return $found;
        }

        // Check abstract factories
        foreach ($this->abstractFactories as $abstractFactory) {
            if ($abstractFactory->canCreateServiceWithName($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Configure the service manager
     *
     * Valid top keys are:
     *
     * - services: service name => service instance pairs
     * - factories: service name => factory pairs; factories may be any
     *   callable, string name resolving to an invokable class, or string name
     *   resolving to a FactoryInterface instance.
     * - abstract_factories: an array of abstract factories; these may be
     *   instances of AbstractFactoryInterface, or string names resolving to
     *   classes that implement that interface.
     * - delegators: service name => list of delegator factories for the given
     *   service; each item in the list may be a callable, a string name
     *   resolving to an invokable class, or a string name resolving to a class
     *   implementing DelegatorFactoryInterface.
     * - shared: service name => flag pairs; the flag is a boolean indicating
     *   whether or not the service is shared.
     * - aliases: alias => service name pairs.
     * - lazy_services: lazy service configuration; can contain the keys:
     *   - class_map: service name => class name pairs.
     *   - proxies_namespace: string namespace to use for generated proxy
     *     classes.
     *   - proxies_target_dir: directory in which to write generated proxy
     *     classes; uses system temporary by default.
     *   - write_proxy_files: boolean indicating whether generated proxy
     *     classes should be written; defaults to boolean false.
     * - shared_by_default: boolean, indicating if services in this instance
     *   should be shared by default.
     *
     * @param  array $config
     * @return void
     */
    protected function configure(array $config)
    {
        $this->services        = (isset($config['services']) ? $config['services'] : []) + $this->services;
        $this->factories       = (isset($config['factories']) ? $config['factories'] : []) + $this->factories;
        $this->delegators      = array_merge_recursive($this->delegators, isset($config['delegators'])
            ? $config['delegators']
            : []);
        $this->shared          = (isset($config['shared']) ? $config['shared'] : []) + $this->shared;
        $this->aliases         = (isset($config['aliases']) ? $config['aliases'] : []) + $this->aliases;
        $this->lazyServices    = array_merge_recursive($this->lazyServices, isset($config['lazy_services'])
            ? $config['lazy_services']
            : []);
        $this->sharedByDefault = isset($config['shared_by_default'])
            ? $config['shared_by_default']
            : $this->sharedByDefault;

        // If lazy service configuration was provided, reset the lazy services
        // delegator factory.
        if (isset($config['lazy_services']) && ! empty($config['lazy_services'])) {
            $this->lazyServicesDelegator = null;
        }

        // For abstract factories and initializers, we always directly
        // instantiate them to avoid checks during service construction.
        if (isset($config['abstract_factories'])) {
            foreach ($config['abstract_factories'] as $abstractFactory) {
                if (is_string($abstractFactory)) {
                    $abstractFactory = new $abstractFactory();
                }

                if (is_callable($abstractFactory)) {
                    $this->abstractFactories[] = $abstractFactory;
                    continue;
                }

                throw new InvalidArgumentException(sprintf(
                    'An invalid abstract factory was registered. A callable or an instance of "%s" was expected, '
                    . 'but "%s" was received',
                    AbstractFactoryInterface::class,
                    is_object($abstractFactory) ? get_class($abstractFactory) : gettype($abstractFactory)
                ));
            }
        }

        if (isset($config['initializers'])) {
            foreach ($config['initializers'] as $initializer) {
                if (is_string($initializer)) {
                    $initializer = new $initializer();
                }

                if (is_callable($initializer)) {
                    $this->initializers[] = $initializer;
                    continue;
                }

                throw new InvalidArgumentException(sprintf(
                    'An invalid initializer was registered. A callable or an instance of "%s" was expected, but
                    "%s" was received',
                    InitializerInterface::class,
                    is_object($initializer) ? get_class($initializer) : gettype($initializer)
                ));
            }
        }
    }

    /**
     * Recursively resolve an alias name to a service name
     *
     * @param  string $alias
     * @return string
     */
    private function resolveAlias($alias)
    {
        $name = $alias;

        do {
            $canBeResolved = isset($this->aliases[$name]);
            $name          = $canBeResolved ? $this->aliases[$name] : $name;
        } while ($canBeResolved);

        return $name;
    }

    /**
     * Get a factory for the given service name
     *
     * @param  string $name
     * @return callable
     * @throws ServiceNotFoundException
     */
    private function getFactory($name)
    {
        $factory = isset($this->factories[$name]) ? $this->factories[$name] : null;

        if (is_string($factory)) {
            $this->factories[$name] = $factory = new $factory();
        }

        if (is_callable($factory)) {
            return $factory;
        }

        // Check abstract factories
        foreach ($this->abstractFactories as $abstractFactory) {
            if ($abstractFactory->canCreateServiceWithName($name)) {
                return $abstractFactory;
            }
        }

        throw new ServiceNotFoundException(sprintf(
            'An invalid or missing factory was given for creating service "%s". Did you make sure you added the service
             into the service manager configuration?',
            $name
        ));
    }

    /**
     * @param  string     $name
     * @param  null|array $options
     * @return object
     */
    private function createDelegatorFromName($name, array $options = null)
    {
        $delegatorsCount  = count($this->delegators[$name]);
        $creationCallback = function () use ($name, $options) {
            // Code is inlined for performance reason, instead of abstracting the creation
            $factory = $this->getFactory($name);
            return $factory($this->creationContext, $name, $options);
        };

        for ($i = 0; $i < $delegatorsCount; $i += 1) {
            $delegatorFactory = $this->delegators[$name][$i];

            if ($delegatorFactory === Proxy\LazyServiceFactory::class) {
                $delegatorFactory = $this->createLazyServiceDelegatorFactory();
            }

            if (is_string($delegatorFactory) && ! class_exists($delegatorFactory)) {
                throw new ServiceNotCreatedException(sprintf(
                    'An invalid delegator was provided. A callable or an instance of "%s" as expected, '
                    . 'but "%s" was received',
                    DelegatorFactoryInterface::class,
                    $delegatorFactory
                ));
            }

            if (is_string($delegatorFactory)) {
                $delegatorFactory = $this->delegators[$name][$i] = new $delegatorFactory();
            }

            if (! is_callable($delegatorFactory)) {
                throw new ServiceNotCreatedException(sprintf(
                    'An invalid delegator was provided. A callable or an instance of "%s" as expected, '
                    . 'but "%s" was received',
                    DelegatorFactoryInterface::class,
                    is_object($delegatorFactory) ? get_class($delegatorFactory) : gettype($delegatorFactory)
                ));
            }

            $creationCallback = function () use ($delegatorFactory, $name, $creationCallback, $options) {
                return $delegatorFactory($this->creationContext, $name, $creationCallback, $options);
            };
        }

        return $creationCallback($this->creationContext, $name, $creationCallback, $options);
    }

    /**
     * Create a new instance with an already resolved name
     *
     * This is a highly performance sensitive method, do not modify if you have not benchmarked it carefully
     *
     * @param  string     $resolvedName
     * @param  null|array $options
     * @return mixed
     */
    private function doCreate($resolvedName, array $options = null)
    {
        try {
            if (!isset($this->delegators[$resolvedName])) {
                // Let's create the service by fetching the factory
                $factory = $this->getFactory($resolvedName);
                $object  = $factory($this->creationContext, $resolvedName, $options);
            } else {
                $object = $this->createDelegatorFromName($resolvedName, $options);
            }
        } catch (Exception $exception) {
            throw new ServiceNotCreatedException(sprintf(
                'Service with name "%s" could not be created. Reason: %s',
                $resolvedName,
                $exception->getMessage()
            ), $exception->getCode(), $exception);
        }

        foreach ($this->initializers as $initializer) {
            $initializer($this->creationContext, $object);
        }

        return $object;
    }

    /**
     * Create the lazy services delegator factory.
     *
     * Creates the lazy services delegator factory based on the lazy_services
     * configuration present.
     *
     * @return Proxy\LazyServiceFactory
     */
    private function createLazyServiceDelegatorFactory()
    {
        if ($this->lazyServicesDelegator) {
            return $this->lazyServicesDelegator;
        }

        if (! isset($this->lazyServices['class_map'])) {
            throw new Exception\InvalidArgumentException('Missing "class_map" config key in "lazy_services"');
        }

        $factoryConfig = new ProxyConfiguration();

        if (isset($this->lazyServices['proxies_namespace'])) {
            $factoryConfig->setProxiesNamespace($this->lazyServices['proxies_namespace']);
        }

        if (isset($this->lazyServices['proxies_target_dir'])) {
            $factoryConfig->setProxiesTargetDir($this->lazyServices['proxies_target_dir']);
        }

        if (! isset($this->lazyServices['write_proxy_files']) || ! $this->lazyServices['write_proxy_files']) {
            $factoryConfig->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        }

        spl_autoload_register($factoryConfig->getProxyAutoloader());

        $this->lazyServicesDelegator = new Proxy\LazyServiceFactory(
            new LazyLoadingValueHolderFactory($factoryConfig),
            $this->lazyServices['class_map']
        );

        return $this->lazyServicesDelegator;
    }
}
