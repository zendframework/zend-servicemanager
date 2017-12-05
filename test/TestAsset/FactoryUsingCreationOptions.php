<?php
/**
 * @see       https://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-servicemanager/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\ServiceManager\TestAsset;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FactoryUsingCreationOptions implements FactoryInterface
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
     * @param array|null $creationOptions
     *
     * @return void
     */
    public function setCreationOptions(array $creationOptions = null)
    {
        $this->creationOptions = $creationOptions;
    }
}
