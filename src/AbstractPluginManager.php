<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\InvalidServiceException;

/**
 * Abstract plugin manager.
 *
 * Abstract PluginManagerInterface implementation providing:
 *
 * - creation context support. The constructor accepts the parent container
 *   instance, which is then used when creating instances.
 * - plugin validation. Implementations may define the `$instanceOf` property
 *   to indicate what class types constitute valid plugins, omitting the
 *   requirement to define the `validate()` method.
 *
 * The implementation extends `ServiceManager`, thus providing the same set
 * of capabilities as found in that implementation.
 */
abstract class AbstractPluginManager extends ServiceManager implements PluginManagerInterface
{
    /**
     * An object type that the created instance must be instanced of
     *
     * @var null|string
     */
    protected $instanceOf = null;

    /**
     * Constructor.
     *
     * Sets the provided $parentLocator as the creation context for all
     * factories; for $config, {@see \Zend\ServiceManager\ServiceManager::configure()}
     * for details on its accepted structure.
     *
     * @param ContainerInterface $parentLocator
     * @param array              $config
     */
    public function __construct(ContainerInterface $parentLocator, array $config = [])
    {
        parent::__construct($config);
        $this->creationContext = $parentLocator;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $name Service name of plugin to retrieve.
     * @param null|array $options Options to use when creating the instance.
     * @return mixed
     * @throws InvalidServiceException if the plugin created is invalid for the
     *     plugin context.
     */
    public function get($name, array $options = null)
    {
        $instance = empty($options) ? parent::get($name) : $this->build($name, $options);
        $this->validate($instance);
        return $instance;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($instance)
    {
        if (empty($this->instanceOf) || $instance instanceof $this->instanceOf) {
            return;
        }

        throw new InvalidServiceException(sprintf(
            'Plugin manager "%s" expected an instance of type "%s", but "%s" was received',
            __CLASS__,
            $this->instanceOf,
            is_object($instance) ? get_class($instance) : gettype($instance)
        ));
    }
}
