<?php

namespace ZendBench\ServiceManager;

use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use Zend\ServiceManager\ServiceManager;

/**
 * @BeforeMethods({"initServiceManager"})
 */
class FetchServicesBench
{
    const NUM_SERVICES = 1000;

    /**
     * @var ServiceManager
     */
    private $sm;

    public function initServiceManager()
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

        $this->sm = new ServiceManager($config);
    }

    /**
     * Fetch the factory services
     *
     * @Revs(1000)
     * @Iterations(20)
     */
    public function benchFetchFactoryService()
    {
        $result = $this->sm->get('factory_' . rand(0, self::NUM_SERVICES));
    }

    /**
     * Fetch the invokable services
     *
     * @Revs(1000)
     * @Iterations(20)
     */
    public function benchFetchInvokableService()
    {
        $result = $this->sm->get('invokable_' . rand(0, self::NUM_SERVICES));
    }

    /**
     * Fetch the services
     *
     * @Revs(1000)
     * @Iterations(20)
     */
    public function benchFetchService()
    {
        $result = $this->sm->get('service_' . rand(0, self::NUM_SERVICES));
    }

    /**
     * Fetch the alias services
     *
     * @Revs(1000)
     * @Iterations(20)
     */
    public function benchFetchAliasService()
    {
        $result = $this->sm->get('alias_' . rand(0, self::NUM_SERVICES));
    }

    /**
     * Fetch the abstract factory services
     *
     * @Revs(1000)
     * @Iterations(20)
     */
    public function benchFetchAbstractFactoryService()
    {
       $result = $this->sm->get('foo');
    }
}
