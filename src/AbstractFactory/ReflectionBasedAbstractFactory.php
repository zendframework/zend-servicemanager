<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\AbstractFactory;

use Interop\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\ServiceManager\Tool\FactoryCreator;

/**
 * Reflection-based factory.
 *
 * To ease development, this factory may be used for classes with
 * type-hinted arguments that resolve to services in the application
 * container; this allows omitting the step of writing a factory for
 * each controller.
 *
 * You may use it as either an abstract factory:
 *
 * <code>
 * 'service_manager' => [
 *     'abstract_factories' => [
 *         ReflectionBasedAbstractFactory::class,
 *     ],
 * ],
 * </code>
 *
 * Or as a factory, mapping a class name to it:
 *
 * <code>
 * 'service_manager' => [
 *     'factories' => [
 *         MyClassWithDependencies::class => ReflectionBasedAbstractFactory::class,
 *     ],
 * ],
 * </code>
 *
 * The latter approach is more explicit, and also more performant.
 *
 * There is also a hybrid approach where reflection factories are generated on demand
 * and then stored in a file cache. To enable this, instantiate ReflectionBasedAbstractFactory
 * manually in your application bootstrap and enable caching.
 * See https://zendframework.github.io/zend-servicemanager/reflection-abstract-factory/ for examples
 * of how to do this.
 *
 * <code>
 * $container->addAbstractFactory(new ReflectionBasedAbstractFactory([], true));
 * </code>
 *
 * The factory has the following constraints/features:
 *
 * - A parameter named `$config` typehinted as an array will receive the
 *   application "config" service (i.e., the merged configuration).
 * - Parameters type-hinted against array, but not named `$config` will
 *   be injected with an empty array.
 * - Scalar parameters will result in an exception being thrown, unless
 *   a default value is present; if the default is present, that will be used.
 * - If a service cannot be found for a given typehint, the factory will
 *   raise an exception detailing this.
 * - Some services provided by Zend Framework components do not have
 *   entries based on their class name (for historical reasons); the
 *   factory allows defining a map of these class/interface names to the
 *   corresponding service name to allow them to resolve.
 *
 * `$options` passed to the factory are ignored in all cases, as we cannot
 * make assumptions about which argument(s) they might replace.
 *
 * Based on the LazyControllerAbstractFactory from zend-mvc.
 */
class ReflectionBasedAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Maps known classes/interfaces to the service that provides them; only
     * required for those services with no entry based on the class/interface
     * name.
     *
     * Extend the class if you wish to add to the list.
     *
     * Example:
     *
     * <code>
     * [
     *     \Zend\Filter\FilterPluginManager::class       => 'FilterManager',
     *     \Zend\Validator\ValidatorPluginManager::class => 'ValidatorManager',
     * ]
     * </code>
     *
     * @var string[]
     */
    protected $aliases = [];

    /**
     * @var FactoryCreator
     */
    private $factoryCreator;

    /**
     * @var bool
     */
    private $cacheEnabled;

    /**
     * @var string
     */
    protected $cacheDirectory;

    /**
     * Constructor.
     *
     * Allows overriding the internal list of aliases. These should be of the
     * form `class name => well-known service name`; see the documentation for
     * the `$aliases` property for details on what is accepted.
     *
     * @param string[] $aliases
     */
    public function __construct(
        array $aliases = [],
        $cacheEnabled = false,
        $cacheDirectory = '/data/cache/reflection-factory'
    ) {
        if (! empty($aliases)) {
            $this->aliases = $aliases;
        }
        $this->cacheEnabled = $cacheEnabled;
        $this->cacheDirectory = $cacheDirectory;
    }

    /**
     * {@inheritDoc}
     *
     * @return DispatchableInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if ($this->cacheEnabled) {
            $factoryName = $requestedName  . 'Factory';
            $filename = $this->cacheDirectory . DIRECTORY_SEPARATOR
                . str_replace('\\', DIRECTORY_SEPARATOR, $requestedName) . 'Factory.php';

            if (file_exists($filename)) {
                include $filename;
                $factory = new $factoryName;
                return $factory($container);
            }

            if (is_null($this->factoryCreator)) {
                $this->factoryCreator = new FactoryCreator;
            }

            $factoryCode = $this->factoryCreator->createFactory($requestedName);
            $parentDir = dirname($filename);
            if (! file_exists($parentDir)) {
                mkdir($parentDir, 0755, true);
            }

            file_put_contents($filename, $factoryCode);
            // eval() is evil, but we just generated this code and can assume it to be safe
            eval(substr($factoryCode, 5));
            $factory = new $factoryName;
            return $factory($container);
        } else {
            $reflectionClass = new ReflectionClass($requestedName);

            if (null === ($constructor = $reflectionClass->getConstructor())) {
                return new $requestedName();
            }

            $reflectionParameters = $constructor->getParameters();

            if (empty($reflectionParameters)) {
                return new $requestedName();
            }

            $resolver = $container->has('config')
                ? $this->resolveParameterWithConfigService($container, $requestedName)
                : $this->resolveParameterWithoutConfigService($container, $requestedName);

            $parameters = array_map($resolver, $reflectionParameters);

            return new $requestedName(...$parameters);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return class_exists($requestedName);
    }

    /**
     * Resolve a parameter to a value.
     *
     * Returns a callback for resolving a parameter to a value, but without
     * allowing mapping array `$config` arguments to the `config` service.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return callable
     */
    private function resolveParameterWithoutConfigService(ContainerInterface $container, $requestedName)
    {
        /**
         * @param ReflectionClass $parameter
         * @return mixed
         * @throws ServiceNotFoundException If type-hinted parameter cannot be
         *   resolved to a service in the container.
         */
        return function (ReflectionParameter $parameter) use ($container, $requestedName) {
            return $this->resolveParameter($parameter, $container, $requestedName);
        };
    }

    /**
     * Returns a callback for resolving a parameter to a value, including mapping 'config' arguments.
     *
     * Unlike resolveParameter(), this version will detect `$config` array
     * arguments and have them return the 'config' service.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return callable
     */
    private function resolveParameterWithConfigService(ContainerInterface $container, $requestedName)
    {
        /**
         * @param ReflectionClass $parameter
         * @return mixed
         * @throws ServiceNotFoundException If type-hinted parameter cannot be
         *   resolved to a service in the container.
         */
        return function (ReflectionParameter $parameter) use ($container, $requestedName) {
            if ($parameter->isArray() && $parameter->getName() === 'config') {
                return $container->get('config');
            }
            return $this->resolveParameter($parameter, $container, $requestedName);
        };
    }

    /**
     * Logic common to all parameter resolution.
     *
     * @param ReflectionClass $parameter
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return mixed
     * @throws ServiceNotFoundException If type-hinted parameter cannot be
     *   resolved to a service in the container.
     */
    private function resolveParameter(ReflectionParameter $parameter, ContainerInterface $container, $requestedName)
    {
        if ($parameter->isArray()) {
            return [];
        }

        if (! $parameter->getClass()) {
            if (! $parameter->isDefaultValueAvailable()) {
                throw new ServiceNotFoundException(sprintf(
                    'Unable to create service "%s"; unable to resolve parameter "%s" '
                    . 'to a class, interface, or array type',
                    $requestedName,
                    $parameter->getName()
                ));
            }

            return $parameter->getDefaultValue();
        }

        $type = $parameter->getClass()->getName();
        $type = isset($this->aliases[$type]) ? $this->aliases[$type] : $type;

        if (! $container->has($type)) {
            throw new ServiceNotFoundException(sprintf(
                'Unable to create service "%s"; unable to resolve parameter "%s" using type hint "%s"',
                $requestedName,
                $parameter->getName(),
                $type
            ));
        }

        return $container->get($type);
    }
}
