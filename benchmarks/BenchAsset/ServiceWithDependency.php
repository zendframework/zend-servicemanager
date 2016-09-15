<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendBench\ServiceManager\BenchAsset;

class ServiceWithDependency
{
    /**
     * @var Dependency
     */
    private $dependency;

    /**
     * @param Dependency $dependency
     */
    public function __construct(Dependency $dependency)
    {
        $this->dependency = $dependency;
    }
}
