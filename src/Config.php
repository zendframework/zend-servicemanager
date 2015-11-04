<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager;

use Zend\Stdlib\ArrayUtils;

class Config implements ConfigInterface
{
    /**
     * Allowed configuration keys
     *
     * @var array
     */
    protected $allowedKeys = [
        'abstract_factories' => true,
        'aliases'            => true,
        'delegators'         => true,
        'factories'          => true,
        'initializers'       => true,
        'invokables'         => true,
        'lazy_services'      => true,
        'services'           => true,
        'shared'             => true,
    ];

    /**
     * @var array
     */
    protected $config = [
        'abstract_factories' => [],
        'aliases'            => [],
        'delegators'         => [],
        'factories'          => [],
        'initializers'       => [],
        'invokables'         => [],
        'lazy_services'      => [],
        'services'           => [],
        'shared'             => [],
    ];

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        // Only merge keys we're interested in
        foreach (array_keys($config) as $key) {
            if (! isset($this->allowedKeys[$key])) {
                unset($config[$key]);
            }
        }

        $this->config = ArrayUtils::merge($this->config, $config);
    }

    /**
     * Configure service manager
     *
     * @param ServiceManager $serviceManager
     * @return ServiceManager Returns a new instance with the merged configuration.
     */
    public function configureServiceManager(ServiceManager $serviceManager)
    {
        return $serviceManager->withConfig($this->config);
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return $this->config;
    }
}
