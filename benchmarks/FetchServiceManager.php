<?php

namespace ZendBench\ServiceManager;

use Athletic\AthleticEvent;
use Zend\ServiceManager\ServiceManager;

class FetchServiceManager extends AthleticEvent
{
    const NUM_SERVICES = 1000;

    protected $config;

    protected function getConfig()
    {
        $config = [];
        for ($i = 0; $i <= self::NUM_SERVICES; $i++) {
            $config['factories']["factory_$i"]    = BenchAsset\FactoryFoo::class;
            $config['invokables']["invokable_$i"] = BenchAsset\Foo::class;
            $config['services']["service_$i"]     = $this;
            $config['aliases']["alias_$i"]        = "service_$i";
        }
        $config['abstract_factories'] = [ BenchAsset\AbstractFactoryFoo::class ];
        return $config;
    }

    public function classSetUp()
    {
        $this->config = $this->getConfig();
    }

    /**
     * @iterations 500
     */
    public function fetchServiceManagerCreation()
    {
        $retult = new ServiceManager($this->config);
    }
}
