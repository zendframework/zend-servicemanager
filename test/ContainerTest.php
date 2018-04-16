<?php
/**
 * @see       https://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-servicemanager/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\ServiceManager;

use Psr\Container\ContainerInterface;
use Zend\ContainerConfigTest\AbstractExpressiveContainerConfigTest;
use Zend\ContainerConfigTest\SharedTestTrait;
use Zend\ServiceManager\ServiceManager;

class ContainerTest extends AbstractExpressiveContainerConfigTest
{
    use SharedTestTrait;

    protected function createContainer(array $config) : ContainerInterface
    {
        return new ServiceManager($config);
    }
}
