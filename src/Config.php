<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager;

class Config implements ConfigInterface
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->config = $config;
    }

    /**
     * Configure service manager
     *
     * @param ServiceManager $serviceManager
     * @return ServiceManager Returns a new instance with the merged configuration.
     */
    public function configureServiceManager(ServiceManager $serviceManager)
    {
        $config            = [];
        $abstractFactories = isset($this->config['abstract_factories']) ? $this->config['abstract_factories'] : [];
        $aliases           = isset($this->config['aliases']) ? $this->config['aliases'] : [];
        $delegators        = isset($this->config['delegators']) ? $this->config['delegators'] : [];
        $factories         = isset($this->config['factories']) ? $this->config['factories'] : [];
        $initializers      = isset($this->config['initializers']) ? $this->config['initializers'] : [];
        $invokables        = isset($this->config['invokables']) ? $this->config['invokables'] : [];
        $lazyServices      = isset($this->config['lazy_services']) ? $this->config['lazy_services'] : [];
        $services          = isset($this->config['services']) ? $this->config['services'] : [];
        $shared            = isset($this->config['shared']) ? $this->config['shared'] : [];

        if (! empty($abstractFactories)) {
            $config['abstract_factories'] = $abstractFactories;
        }

        if (! empty($aliases)) {
            $config['aliases'] = $aliases;
        }

        if (! empty($delegators)) {
            $config['delegators'] = $delegators;
        }

        if (! empty($factories)) {
            $config['factories'] = $factories;
        }

        if (! empty($initializers)) {
            $config['initializers'] = $initializers;
        }

        if (! empty($invokables)) {
            $config['invokables'] = $invokables;
        }

        if (! empty($lazyServices)) {
            $config['lazy_services'] = $lazyServices;
        }

        if (! empty($services)) {
            $config['services'] = $services;
        }

        if (! empty($shared)) {
            $config['shared'] = $shared;
        }

        return $serviceManager->withConfig($config);
    }
}
