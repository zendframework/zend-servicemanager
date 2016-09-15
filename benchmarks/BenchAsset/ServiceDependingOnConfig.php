<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendBench\ServiceManager\BenchAsset;

class ServiceDependingOnConfig
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }
}
