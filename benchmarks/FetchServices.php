<?php

namespace ZendBench\ServiceManager;

use Zend\ServiceManager\ServiceManager;
use Athletic\AthleticEvent;

class FetchServices extends AthleticEvent
{
    const NUM_SERVICES = 50;

    /**
     * @var ServiceManager
     */
    protected $sm;

    public function setUp()
    {
        $this->sm = new ServiceManager();
        // factories
        for ($i = 0; $i < self::NUM_SERVICES; $i++) {
            $this->sm->setFactory("factory_$i", 'ZendTest\ServiceManager\TestAsset\FooFactory');
            $this->sm->setInvokableClass("invokable_$i", 'ZendTest\ServiceManager\TestAsset\Foo');
            $this->sm->setService("service_$i", new \ZendTest\ServiceManager\TestAsset\Foo);
            $this->sm->setAlias("alias_$i", "service_$i");
        }
        // abstract factory
        $this->sm->addAbstractFactory('ZendTest\ServiceManager\TestAsset\FooAbstractFactory');
        $this->sm->addAbstractFactory('ZendTest\ServiceManager\TestAsset\BarAbstractFactory');
    }

    /**
     * Fetch the factory services
     *
     * @iterations 1000
     */
    public function fetchFactoryService()
    {
        $result = $this->sm->get(sprintf("factory_%d", rand(0, self::NUM_SERVICES - 1)));
    }

    /**
     * Fetch the invokable services
     *
     * @iterations 1000
     */
    public function fetchInvokableService()
    {
        $result = $this->sm->get(sprintf("invokable_%d", rand(0, self::NUM_SERVICES - 1)));
    }

    /**
     * Fetch the services
     *
     * @iterations 1000
     */
    public function fetchService()
    {
        $result = $this->sm->get(sprintf("service_%d", rand(0, self::NUM_SERVICES - 1)));
    }

    /**
     * Fetch the alias services
     *
     * @iterations 1000
     */
    public function fetchAliasService()
    {
        $result = $this->sm->get(sprintf("alias_%d", rand(0, self::NUM_SERVICES - 1)));
    }

    /**
     * Fetch the abstract factory services
     *
     * @iterations 1000
     */
    public function fetchAbstractFactoryService()
    {
       $result = $this->sm->get(rand(0,1) ? 'foo' : 'bar');
    }
}
