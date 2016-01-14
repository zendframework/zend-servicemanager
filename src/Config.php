<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager;

use Zend\Stdlib\ArrayUtils\MergeRemoveKey;
use Zend\Stdlib\ArrayUtils\MergeReplaceKeyInterface;

class Config implements ConfigInterface
{
    /**
     * @var array
     */
    private $allowedKeys = [
        'abstract_factories' => true,
        'aliases' => true,
        'delegators' => true,
        'factories' => true,
        'initializers' => true,
        'invokables' => true,
        'lazy_services' => true,
        'services' => true,
        'shared' => true,
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
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        // Only merge keys we're interested in
        foreach (array_keys($this->allowedKeys) as $requiredKey) {
            if ($this->isValidValue($config, $requiredKey)) {
                if ($this->isValidValue($this->config, $requiredKey)) {
                    $this->config[$requiredKey] = $this->merge($this->config[$requiredKey], $config[$requiredKey]);
                } else {
                    $this->config[$requiredKey] = $config[$requiredKey];
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function configureServiceManager(ServiceManager $serviceManager)
    {
        return $serviceManager->configure($this->config);
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return $this->config;
    }

    /**
     * @param array $array
     * @param string $key
     *
     * @return bool
     */
    private function isValidValue(array $array, $key)
    {
        return isset($array[$key]) && is_array($array[$key]);
    }

    /**
     * Copy paste from https://github.com/zendframework/zend-stdlib/commit/26fcc32a358aa08de35625736095cb2fdaced090
     * to keep compatibility with previous version
     *
     * @link https://github.com/zendframework/zend-servicemanager/pull/68
     */
    private function merge(array $a, array $b)
    {
        foreach ($b as $key => $value) {
            if ($value instanceof MergeReplaceKeyInterface) {
                $a[$key] = $value->getData();
            } elseif (isset($a[$key]) || array_key_exists($key, $a)) {
                if ($value instanceof MergeRemoveKey) {
                    unset($a[$key]);
                } elseif (is_int($key)) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = $this->merge($a[$key], $value);
                } else {
                    $a[$key] = $value;
                }
            } else {
                if (!$value instanceof MergeRemoveKey) {
                    $a[$key] = $value;
                }
            }
        }
        return $a;
    }
}
