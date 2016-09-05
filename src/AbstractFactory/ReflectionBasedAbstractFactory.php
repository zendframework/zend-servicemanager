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
use Zend\Console\Adapter\AdapterInterface as ConsoleAdapterInterface;
use Zend\Filter\FilterPluginManager;
use Zend\Hydrator\HydratorPluginManager;
use Zend\InputFilter\InputFilterPluginManager;
use Zend\Log\FilterPluginManager as LogFilterManager;
use Zend\Log\FormatterPluginManager as LogFormatterManager;
use Zend\Log\ProcessorPluginManager as LogProcessorManager;
use Zend\Log\WriterPluginManager as LogWriterManager;
use Zend\Serializer\AdapterPluginManager as SerializerAdapterManager;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\Validator\ValidatorPluginManager;

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
 * The factory has the following constraints/features:
 *
 * - A parameter named `$config` typehinted as an array will receive the
 *   application "config" service (i.e., the merged configuration).
 * - Parameters type-hinted against array, but not named `$config` will
 *   be injected with an empty array.
 * - Scalar parameters will be resolved as null values.
 * - If a service cannot be found for a given typehint, the factory will
 *   raise an exception detailing this.
 * - Some services provided by Zend Framework components do not have
 *   entries based on their class name (for historical reasons); the
 *   factory contains a map of these class/interface names to the
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
     * @var string[]
     */
    protected $aliases = [
        ConsoleAdapterInterface::class  => 'ConsoleAdapter',
        FilterPluginManager::class      => 'FilterManager',
        HydratorPluginManager::class    => 'HydratorManager',
        InputFilterPluginManager::class => 'InputFilterManager',
        LogFilterManager::class         => 'LogFilterManager',
        LogFormatterManager::class      => 'LogFormatterManager',
        LogProcessorManager::class      => 'LogProcessorManager',
        LogWriterManager::class         => 'LogWriterManager',
        SerializerAdapterManager::class => 'SerializerAdapterManager',
        ValidatorPluginManager::class   => 'ValidatorManager',
    ];

    /**
     * Constructor.
     *
     * Allows overriding the internal list of aliases. These should be of the
     * form `class name => well-known service name`.
     *
     * @param string[] $aliases
     */
    public function __construct(array $aliases = [])
    {
        if (! empty($aliases)) {
            $this->aliases = $aliases;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return DispatchableInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $reflectionClass = new ReflectionClass($requestedName);

        if (null === ($constructor = $reflectionClass->getConstructor())) {
            return new $requestedName();
        }

        $reflectionParameters = $constructor->getParameters();

        if (empty($reflectionParameters)) {
            return new $requestedName();
        }

        $parameters = array_map(
            $this->resolveParameter($container, $requestedName),
            $reflectionParameters
        );

        return new $requestedName(...$parameters);
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
     * Returns a callback for resolving a parameter to a value.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return callable
     */
    private function resolveParameter(ContainerInterface $container, $requestedName)
    {
        $hasConfig = $container->has('config');

        /**
         * @param ReflectionClass $parameter
         * @return mixed
         * @throws ServiceNotFoundException If type-hinted parameter cannot be
         *   resolved to a service in the container.
         */
        return function (ReflectionParameter $parameter) use ($container, $requestedName, $hasConfig) {
            if ($parameter->isArray()
                && $parameter->getName() === 'config'
                && $hasConfig
            ) {
                return $container->get('config');
            }

            if ($parameter->isArray()) {
                return [];
            }

            if (! $parameter->getClass()) {
                return;
            }

            $type = $parameter->getClass()->getName();
            $type = isset($this->aliases[$type]) ? $this->aliases[$type] : $type;

            if (! $container->has($type)) {
                throw new ServiceNotFoundException(sprintf(
                    'Unable to create controller "%s"; unable to resolve parameter "%s" using type hint "%s"',
                    $requestedName,
                    $parameter->getName(),
                    $type
                ));
            }

            return $container->get($type);
        };
    }
}
