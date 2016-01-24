<?php

namespace ZendBench\ServiceManager;

use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;
use Zend\ServiceManager\ServiceManager;

/**
 * @Revs(100)
 * @Iterations(20)
 * @Warmup(2)
 */
class FetchNewServiceManagerBench
{
    const NUM_SERVICES = 1000;

    /**
     * @var array
     */
    private $config = [];

    public function __construct()
    {
        $config = [
            'factories'          => [],
            'invokables'         => [],
            'services'           => [],
            'aliases'            => [],
            'abstract_factories' => [
                BenchAsset\AbstractFactoryFoo::class,
            ],
        ];

        $service = new \stdClass();

        for ($i = 0; $i <= self::NUM_SERVICES; $i++) {
            $config['factories']["factory_$i"]    = BenchAsset\FactoryFoo::class;
            $config['invokables']["invokable_$i"] = BenchAsset\Foo::class;
            $config['services']["service_$i"]     = $service;
            $config['aliases']["alias_$i"]        = "service_$i";
        }
        $this->config = $config;
    }

    public function benchFetchServiceManagerCreation()
    {
        new ServiceManager($this->config);
    }
}
