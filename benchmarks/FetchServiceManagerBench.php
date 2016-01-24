<?php

namespace ZendBench\ServiceManager;

use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use Zend\ServiceManager\ServiceManager;

/**
 * @BeforeMethods({"initConfig"})
 */
class FetchServiceManagerBench
{
    const NUM_SERVICES = 1000;

    private $config = [];

    public function initConfig()
    {
        $config  = [];
        $service = new \stdClass();

        for ($i = 0; $i <= self::NUM_SERVICES; $i++) {
            $config['factories']["factory_$i"]    = BenchAsset\FactoryFoo::class;
            $config['invokables']["invokable_$i"] = BenchAsset\Foo::class;
            $config['services']["service_$i"]     = $service;
            $config['aliases']["alias_$i"]        = "service_$i";
        }

        $config['abstract_factories'] = [ BenchAsset\AbstractFactoryFoo::class ];

        $this->config = $config;
    }

    /**
     * @Revs(1000)
     * @Iterations(20)
     */
    public function benchFetchServiceManagerCreation()
    {
        $result = new ServiceManager($this->config);
    }
}
