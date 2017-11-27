<?php

namespace ZendTest\ServiceManager\TestAsset;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @author Maximilian Bösing <max@boesing.email>
 */
class BazFactory implements FactoryInterface
{

    /**
     * @var array
     */
    private $creationOptions;

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Baz($this->creationOptions);
    }

    /**
     * @param array $creationOptions
     *
     * @return void
     */
    public function setCreationOptions(array $creationOptions)
    {
        $this->creationOptions = $creationOptions;
    }
}
