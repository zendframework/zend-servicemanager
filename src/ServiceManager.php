<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager;

use Exception;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use ProxyManager\Configuration as ProxyConfiguration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use Zend\ServiceManager\Exception\ContainerModificationsNotAllowedException;
use Zend\ServiceManager\Exception\CyclicAliasException;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\InvokableFactory;

use function array_intersect_key;
use function array_keys;
use function array_merge;
use function array_merge_recursive;
use function class_exists;
use function get_class;
use function gettype;
use function is_callable;
use function is_object;
use function is_string;
use function spl_autoload_register;
use function spl_object_hash;
use function sprintf;
use function trigger_error;

/**
 * Service Manager.
 *
 * Default implementation of the ServiceLocatorInterface, providing capabilities
 * for object creation via:
 *
 * - factories
 * - abstract factories
 * - delegator factories
 * - lazy service factories (generated proxies)
 * - initializers (interface injection)
 *
 * It also provides the ability to inject specific service instances and to
 * define aliases.
 */
class ServiceManager implements ServiceLocatorInterface
{
    /**
     * @var Factory\AbstractFactoryInterface[]
     */
    protected $abstractFactories = [];

    /**
     * A list of aliases
     *
     * Should map one alias to a service name, or another alias (aliases are recursively resolved)
     *
     * @var string[]
     */
    protected $aliases = [];

    /**
     * Whether or not changes may be made to this instance.
     *
     * @param bool
     */
    protected $allowOverride = false;

    /**
     * @var ContainerInterface
     */
    protected $creationContext;

    /**
     * @var string[][]|Factory\DelegatorFactoryInterface[][]
     */
    protected $delegators = [];

    /**
     * A list of factories (either as string name or callable)
     *
     * @var string[]|callable[]
     */
    protected $factories = [];

    /**
     * A list of invokable classes
     *
     * @var string[]|callable[]
     */
    protected $invokables = [];

    /**
     * @var Initializer\InitializerInterface[]|callable[]
     */
    protected $initializers = [];

    /**
     * @var array
     */
    protected $lazyServices = [];

    /**
     * @var null|Proxy\LazyServiceFactory
     */
    private $lazyServicesDelegator;

    /**
     * @var string[]
     */
    private $resolvedAliases = [];

    /**
     * A list of already loaded services (this act as a local cache)
     *
     * @var array
     */
    protected $services = [];

    /**
     * Enable/disable shared instances by service name.
     *
     * Example configuration:
     *
     * 'shared' => [
     *     MyService::class => true, // will be shared, even if "sharedByDefault" is false
     *     MyOtherService::class => false // won't be shared, even if "sharedByDefault" is true
     * ]
     *
     * @var boolean[]
     */
    protected $shared = [];

    /**
     * Should the services be shared by default?
     *
     * @var bool
     */
    protected $sharedByDefault = true;

    /**
     * Service manager was already configured?
     *
     * @var bool
     */
    protected $configured = false;

    /**
     * Cached abstract factories from string.
     *
     * @var array
     */
    private $cachedAbstractFactories = [];

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

        if (! empty($this->initializers)) {
            // null indicates that $this->initializers
            // should be used for configuration
            $this->resolveInitializers(null);
        }

        if (! empty($this->abstractFactories)) {
            // null indicates that $this->abstractFactories
            // should be used for configuration
            $this->resolveAbstractFactories(null);
        }

        $this->configure($config);
    }

    /**
     * Implemented for backwards compatibility with previous plugin managers only.
     *
     * Returns the creation context.
     *
     * @deprecated since 3.0.0. Factories using 3.0 should use the container
     *     instance passed to the factory instead.
     * @return ContainerInterface
     */
    public function getServiceLocator()
    {
        trigger_error(sprintf(
            'Usage of %s is deprecated since v3.0.0; please use the container passed to the factory instead',
            __METHOD__
        ), E_USER_DEPRECATED);
        return $this->creationContext;
    }

    /**
     * {@inheritDoc}
     */
    public function get($name)
    {
        // We start by checking if we have cached the requested service (this
        // is the fastest method).
        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        // Determine if the service should be shared
        $sharedService = isset($this->shared[$name]) ? $this->shared[$name] : $this->sharedByDefault;

        // We achieve better performance if we can let all alias
        // considerations out
        if (empty($this->resolvedAliases)) {
            $object = $this->doCreate($name);

            // Cache the object for later, if it is supposed to be shared.
            if (($sharedService)) {
                $this->services[$name] = $object;
            }
            return $object;
        }

        // Here we have to deal with requests which may be aliases
        $resolvedName = isset($this->resolvedAliases[$name]) ? $this->resolvedAliases[$name] : $name;

        // Can only become true, if the requested service is an shared alias
        $sharedAlias = $sharedService && isset($this->services[$resolvedName]);
        // If the alias is configured as shared service, we are done.
        if ($sharedAlias) {
            $this->services[$name] = $this->services[$resolvedName];
            return $this->services[$resolvedName];
        }

        // At this point we have to create the object. We use the
        // resolved name for that.
        $object = $this->doCreate($resolvedName);

        // Cache the object for later, if it is supposed to be shared.
        if (($sharedService)) {
            $this->services[$resolvedName] = $object;
        }
        // Also do so for aliases, this allows sharing based on service name used.
        // $serviceAvailable is true if and only if we have an alias
        if ($sharedAlias) {
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
        $name = $this->resolvedAliases[$name] ?? $name;
        return $this->doCreate($name, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function has($name)
    {
        $name  = isset($this->resolvedAliases[$name]) ? $this->resolvedAliases[$name] : $name;
        if(isset($this->services[$name]) 
			|| isset($this->factories[$name]) 
			|| isset($this->invokables[$name])) 
		{
			return true;
		}

        // Check abstract factories
        foreach ($this->abstractFactories as $abstractFactory) {
            if ($abstractFactory->canCreate($this->creationContext, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicate whether or not the instance is immutable.
     *
     * @param bool $flag
     */
    public function setAllowOverride($flag)
    {
        $this->allowOverride = (bool) $flag;
    }

    /**
     * Retrieve the flag indicating immutability status.
     *
     * @return bool
     */
    public function getAllowOverride()
    {
        return $this->allowOverride;
    }

    /**
     * Configure the service manager
     *
     * Valid top keys are:
     *
     * - services: service name => service instance pairs
     * - invokables: service name => class name pairs for classes that do not
     *   have required constructor arguments; internally, maps the class to an
     *   InvokableFactory instance, and creates an alias if the service name
     *   and class name do not match.
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
     * @return self
     * @throws ContainerModificationsNotAllowedException if the allow
     *     override flag has been toggled off, and a service instance
     *     exists for a given service.
     */
    public function configure(array $config)
    {
        $this->validateConfig($config);

        if (isset($config['services'])) {
            $this->services = $config['services'] + $this->services;
        }

        if (! empty($config['invokables'])) {
            $this->invokables = $config['invokables'] + $this->invokables;
        }

        if (isset($config['factories'])) {
            $this->factories = $config['factories'] + $this->factories;
        }

        if (isset($config['delegators'])) {
            $this->delegators = \array_merge_recursive($this->delegators, $config['delegators']);
        }

        if (isset($config['shared'])) {
            $this->shared = $config['shared'] + $this->shared;
        }

        if (isset($config['aliases'])) {
            $this->configureAliases($config['aliases']);
        } elseif (! $this->configured && ! empty($this->aliases)) {
            $this->resolveAliases($this->aliases);
        }

        if (isset($config['shared_by_default'])) {
            $this->sharedByDefault = $config['shared_by_default'];
        }

        // If lazy service configuration was provided, reset the lazy services
        // delegator factory.
        if (isset($config['lazy_services']) && ! empty($config['lazy_services'])) {
            $this->lazyServices          = \array_merge_recursive($this->lazyServices, $config['lazy_services']);
            $this->lazyServicesDelegator = null;
        }

        // For abstract factories and initializers, we always directly
        // instantiate them to avoid checks during service construction.
        if (isset($config['abstract_factories'])) {
            $this->resolveAbstractFactories($config['abstract_factories']);
        }

        if (isset($config['initializers'])) {
            $this->resolveInitializers($config['initializers']);
        }
        $this->configured = true;
        return $this;
    }

    /**
     * @param string[] $aliases
     *
     * @return void
     */
    private function configureAliases(array $aliases)
    {
        if (! $this->configured) {
            $this->aliases = $aliases + $this->aliases;

            $this->resolveAliases($this->aliases);

            return;
        }

        // Performance optimization. If there are no collisions, then we don't need to recompute loops
        $intersecting  = $this->aliases && array_intersect_key($this->aliases, $aliases);
        $this->aliases = $this->aliases ? array_merge($this->aliases, $aliases) : $aliases;

        if ($intersecting) {
            $this->resolveAliases($this->aliases);

            return;
        }

        $this->resolveAliases($aliases);
        $this->resolveNewAliasesWithPreviouslyResolvedAliases($aliases);
    }

    /**
     * Add an alias.
     *
     * @param string $alias
     * @param string $target
     */
    public function setAlias($alias, $target)
    {
        $this->validate($alias);
        if(isset($this->resolvedAliases[$target])) {
            $target = $this->resolvedAliases[$target];
        }
        $this->resolvedAliases[$alias] = $target;
        if (in_array($alias, $this->resolvedAliases)) {
            $r = array_intersect($this->resolvedAliases, [ $alias ]);
            foreach($r as $name => $service) {
                print($name." ".$service. " ".$target."\n");
                $this->resolvedAliases[$name] = $target;
            }
        }
    }

    /**
     * Add an invokable class mapping.
     *
     * @param string $name Service name
     * @param null|string $class Class to which to map; if omitted, $name is
     *     assumed.
     */
    public function setInvokableClass($name, $class = null)
    {
        $this->configure(['invokables' => [ $name => (isset($class) ? $class : $name)]]);
    }

    /**
     * Specify a factory for a given service name.
     *
     * @param string $name Service name
     * @param string|callable|Factory\FactoryInterface $factory Factory to which
     *     to map.
     */
    public function setFactory($name, $factory)
    {
        $this->validate($name);
        $this->factories[$name] = $factory;
    }

    /**
     * Create a lazy service mapping to a class.
     *
     * @param string $name Service name to map
     * @param null|string $class Class to which to map; if not provided, $name
     *     will be used for the mapping.
     */
    public function mapLazyService($name, $class = null)
    {
        $this->configure(['lazy_services' => ['class_map' => [$name => $class ?: $name]]]);
    }

    /**
     * Add an abstract factory for resolving services.
     *
     * @param string|Factory\AbstractFactoryInterface $factory Service name
     */
    public function addAbstractFactory($factory)
    {
        $this->resolveAbstractFactory($factory);
    }

    /**
     * Add a delegator for a given service.
     *
     * @param string $name Service name
     * @param string|callable|Factory\DelegatorFactoryInterface $factory Delegator
     *     factory to assign.
     */
    public function addDelegator($name, $factory)
    {
        $this->configure(['delegators' => [$name => [$factory]]]);
    }

    /**
     * Add an initializer.
     *
     * @param string|callable|Initializer\InitializerInterface $initializer
     */
    public function addInitializer($initializer)
    {
        $this->configure(['initializers' => [$initializer]]);
    }

    /**
     * Map a service.
     *
     * @param string $name Service name
     * @param array|object $service
     */
    public function setService($name, $service)
    {
        $this->validate($name);
        $this->services[$name] = $service;
    }

    /**
     * Add a service sharing rule.
     *
     * @param string $name Service name
     * @param boolean $flag Whether or not the service should be shared.
     */
    public function setShared($name, $flag)
    {
        $this->validate($name);
        $this->shared[$name] = (bool) $flag;
    }

    private function resolveAbstractFactory($abstractFactory)
    {
        if (\is_string($abstractFactory) && \class_exists($abstractFactory)) {
            //Cached string
            if (! isset($this->cachedAbstractFactories[$abstractFactory])) {
                $this->cachedAbstractFactories[$abstractFactory] = new $abstractFactory();
            }

            $abstractFactory = $this->cachedAbstractFactories[$abstractFactory];
        }

        if ($abstractFactory instanceof Factory\AbstractFactoryInterface) {
            $abstractFactoryObjHash = \spl_object_hash($abstractFactory);
            $this->abstractFactories[$abstractFactoryObjHash] = $abstractFactory;
            return;
        }

        // Error condition; let's find out why.

        // If we still have a string, we have a class name that does not resolve
        if (\is_string($abstractFactory)) {
            throw new InvalidArgumentException(
                sprintf(
                    'An invalid abstract factory was registered; resolved to class "%s" ' .
                    'which does not exist; please provide a valid class name resolving ' .
                    'to an implementation of %s',
                    $abstractFactory,
                    AbstractFactoryInterface::class
                )
            );
        }

        // Otherwise, we have an invalid type.
        throw new InvalidArgumentException(
            sprintf(
                'An invalid abstract factory was registered. Expected an instance of "%s", ' .
                'but "%s" was received',
                AbstractFactoryInterface::class,
                (is_object($abstractFactory) ? get_class($abstractFactory) : gettype($abstractFactory))
            )
        );
    }

    /**
     * Instantiate abstract factories for to avoid checks during service construction.
     *
     * @param string[]|Factory\AbstractFactoryInterface[] $abstractFactories
     *
     * @return void
     */
    private function resolveAbstractFactories(array $abstractFactories = null)
    {
        if ($abstractFactories === null) {
            $abstractFactories = $this->abstractFactories;
            $this->abstractFactories = [];
        }

        foreach ($abstractFactories as $abstractFactory) {
            $this->resolveAbstractFactory($abstractFactory);
        }
    }

    /**
     * Instantiate initializers for to avoid checks during service construction.
     *
     * @param string[]|Initializer\InitializerInterface[]|callable[] $initializers
     *
     * @return void
     */
    private function resolveInitializer($initializer)
    {
        if (\is_string($initializer) && \class_exists($initializer)) {
            $initializer = new $initializer();
        }

        if (\is_callable($initializer)) {
            $this->initializers[] = $initializer;
            return;
        }

        // Error condition; let's find out why.

        if (\is_string($initializer)) {
            throw new InvalidArgumentException(
                sprintf(
                    'An invalid initializer was registered; resolved to class or function "%s" ' .
                    'which does not exist; please provide a valid function name or class ' .
                    'name resolving to an implementation of %s',
                    $initializer,
                    Initializer\InitializerInterface::class
                )
            );
        }

        // Otherwise, we have an invalid type.
        throw new InvalidArgumentException(
            sprintf(
                'An invalid initializer was registered. Expected a callable, or an instance of ' .
                '(or string class name resolving to) "%s", ' .
                'but "%s" was received',
                Initializer\InitializerInterface::class,
                (is_object($initializer) ? get_class($initializer) : gettype($initializer))
            )
        );
    }

    /**
     * Instantiate initializers for to avoid checks during service construction.
     *
     * @param string[]|Initializer\InitializerInterface[]|callable[] $initializers
     *
     * @return void
     */
    private function resolveInitializers(array $initializers = null)
    {
        if ($initializers === null) {
            $initializers = $this->initializers;
            $this->initializers = [];
        }
        foreach ($initializers as $initializer) {
            $this->resolveInitializer($initializer);
        }
    }

    /**
     * Resolve aliases to their canonical service names.
     *
     * @param string[] $aliases
     *
     * @returns void
     */
    private function resolveAliases(array $aliases)
    {
        foreach ($aliases as $alias => $service) {
            $visited = [];
            $name    = $alias;

            while (isset($this->aliases[$name])) {
                if (isset($visited[$name])) {
                    throw CyclicAliasException::fromAliasesMap($aliases);
                }

                $visited[$name] = true;
                $name           = $this->aliases[$name];
            }

            $this->resolvedAliases[$alias] = $name;
        }
    }

    /**
     * Rewrites the map of aliases by resolving the given $aliases with the existing resolved ones.
     * This is mostly done for performance reasons.
     *
     * @param string[] $aliases
     *
     * @return void
     */
    private function resolveNewAliasesWithPreviouslyResolvedAliases(array $aliases)
    {
        foreach ($this->resolvedAliases as $name => $target) {
            if (isset($aliases[$target])) {
                $this->resolvedAliases[$name] = $this->resolvedAliases[$target];
            }
        }
    }

    /**
     * Get a factory for the given service name and create an object using
     * that factory or create invokable if service is invokable
     *
     * @param  string $name
     * @return object
     * @throws ServiceNotFoundException
     */
    private function createServiceThroughFactory($name, array $options = null)
    {
        $factory = $this->factories[$name] ?? null;

        if (is_string($factory) && class_exists($factory)) {
            $factory = new $factory();
            if (is_callable($factory)) {
                $this->factories[$name] = $factory;
            }
            return $factory($this->creationContext, $name, $options);
        }

        if (! is_callable($factory)) {
            if (isset($this->invokables[$name])) {
                $invokable = $this->invokables[$name];
                return $options === null ? new $invokable() : new $invokable($options);
            }
        } else {
            return $factory($this->creationContext, $name, $options);
        }

        // Check abstract factories
        foreach ($this->abstractFactories as $abstractFactory) {
            if ($abstractFactory->canCreate($this->creationContext, $name)) {
                return $abstractFactory($this->creationContext, $name, $options);
            }
        }

        throw new ServiceNotFoundException(sprintf(
            'Unable to resolve service "%s" to a factory; are you certain you provided it during configuration?',
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
        $creationCallback = function () use ($name, $options) {
            // Code is inlined for performance reason, instead of abstracting the creation
            return $this->createServiceThroughFactory($name, $options);
        };

        foreach ($this->delegators[$name] as $index => $delegatorFactory) {
            $delegatorFactory = $this->delegators[$name][$index];

            if ($delegatorFactory === Proxy\LazyServiceFactory::class) {
                $delegatorFactory = $this->createLazyServiceDelegatorFactory();
            }

            if (\is_string($delegatorFactory) && \class_exists($delegatorFactory)) {
                $delegatorFactory = new $delegatorFactory();
            }

            if (! \is_callable($delegatorFactory)) {
                if (\is_string($delegatorFactory)) {
                    throw new ServiceNotCreatedException(\sprintf(
                        'An invalid delegator factory was registered; resolved to class or function "%s" '
                        . 'which does not exist; please provide a valid function name or class name resolving '
                        . 'to an implementation of %s',
                        $delegatorFactory,
                        DelegatorFactoryInterface::class
                    ));
                }

                throw new ServiceNotCreatedException(\sprintf(
                    'A non-callable delegator, "%s", was provided; expected a callable or instance of "%s"',
                    \is_object($delegatorFactory) ? \get_class($delegatorFactory) : \gettype($delegatorFactory),
                    DelegatorFactoryInterface::class
                ));
            }

            $this->delegators[$name][$index] = $delegatorFactory;

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
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    private function doCreate($resolvedName, array $options = null)
    {
        try {
            if (! isset($this->delegators[$resolvedName])) {
                $object = $this->createServiceThroughFactory($resolvedName, $options);
            } else {
                $object = $this->createDelegatorFromName($resolvedName, $options);
            }
        } catch (ContainerException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw new ServiceNotCreatedException(sprintf(
                'Service with name "%s" could not be created. Reason: %s',
                $resolvedName,
                $exception->getMessage()
            ), (int) $exception->getCode(), $exception);
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
     * @throws ServiceNotCreatedException when the lazy service class_map
     *     configuration is missing
     */
    private function createLazyServiceDelegatorFactory()
    {
        if ($this->lazyServicesDelegator) {
            return $this->lazyServicesDelegator;
        }

        if (! isset($this->lazyServices['class_map'])) {
            throw new ServiceNotCreatedException('Missing "class_map" config key in "lazy_services"');
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
        } else {
            $factoryConfig->setGeneratorStrategy(new FileWriterGeneratorStrategy(
                new FileLocator($factoryConfig->getProxiesTargetDir())
            ));
        }

        \spl_autoload_register($factoryConfig->getProxyAutoloader());

        $this->lazyServicesDelegator = new Proxy\LazyServiceFactory(
            new LazyLoadingValueHolderFactory($factoryConfig),
            $this->lazyServices['class_map']
        );

        return $this->lazyServicesDelegator;
    }

    /**
     * Determine if one or more services already exist in the container.
     *
     * Validation in the context of this class means, that for
     * a given service name we do not have a service instance
     * in the cache OR override is explicitly allowed.
     *
     * @param string $service
     * @throws ContainerModificationsNotAllowedException if the
     *     provided service name is invalid.
     */
    private function validate($service)
    {
        // Important: Next three lines must kept equal to the three
        // lines of validateArray (see below) which are marked as code
        // duplicate!
        if (! isset($this->services[$service]) ?: $this->allowOverride) {
            return;
        }
        throw new ContainerModificationsNotAllowedException(sprintf(
            'The container does not allow to replace/update a service'
            . ' with existing instances; the following '
            . 'already exist in the container: %s',
            $service
        ));
    }

    /**
     * Determine if a service instance for any of the provided array's
     * keys already exists, and if it exists, determine if is it allowed
     * to get overriden.
     *
     * Validation in the context of this class means, that for
     * a given service name we do not have a service instance
     * in the cache OR override is explicitly allowed.
     *
     * @param string[] $services
     * @param string $type Type of service being checked.
     * @throws ContainerModificationsNotAllowedException if any
     *     array keys is invalid.
     */
    private function validateArray(array $services)
    {
        $keys = \array_keys($services);
        foreach ($keys as $service) {
            // This is a code duplication from validate (see above).
            // validate is almost a one liner, so we reproduce it
            // here for the sake of performance of aggregated service
            // manager configurations (we save the overhead the function
            // call would produce)
            //
            // Important: Next three lines MUST kept equal to the first
            // three lines of validate!
            if (! isset($this->services[$service]) ?: $this->allowOverride) {
                return;
            }
            throw new ContainerModificationsNotAllowedException(sprintf(
                'The container does not allow to replace/update a service'
                . ' with existing instances; the following '
                . 'already exist in the container: %s',
                $service
            ));
        }
    }

    /**
     * Determine if a service for any name provided by a service
     * manager configuration(services, aliases, factories, ...)
     * already exists, and if it exists, determine if is it allowed
     * to get overriden.
     *
     * Validation in the context of this class means, that for
     * a given service name we do not have a service instance
     * in the cache OR override is explicitly allowed.
     *
     * @param array $config
     * @throws ContainerModificationsNotAllowedException if any
     *     service key is invalid.
     */
    private function validateConfig(array $config)
    {
        if ($this->allowOverride || ! $this->configured) {
            return;
        }

        $sections = ['services', 'aliases', 'invokables', 'factories', 'delegators', 'shared'];

        foreach ($sections as $section) {
            if (isset($config[$section])) {
                $this->validateArray($config[$section]);
            }
        }

        if (isset($config['lazy_services']['class_map'])) {
            $this->validateArray($config['lazy_services']['class_map']);
        }
    }
}
