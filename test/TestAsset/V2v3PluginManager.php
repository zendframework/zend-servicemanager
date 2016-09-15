<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\ServiceManager\TestAsset;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception\InvalidServiceException;
use Zend\ServiceManager\Factory\InvokableFactory;

class V2v3PluginManager extends AbstractPluginManager
{
    protected $aliases = [
        'foo' => InvokableObject::class,
    ];

    protected $factories = [
        InvokableObject::class                           => InvokableFactory::class,
        // Legacy (v2) due to alias resolution
        'zendtestservicemanagertestassetinvokableobject' => InvokableFactory::class,
    ];

    protected $instanceOf = InvokableObject::class;

    protected $shareByDefault = false;

    protected $sharedByDefault = false;


    public function validate($plugin)
    {
        if ($plugin instanceof $this->instanceOf) {
            return;
        }

        throw new InvalidServiceException(sprintf(
            "'%s' is not an instance of '%s'",
            get_class($plugin),
            $this->instanceOf
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function validatePlugin($plugin)
    {
        try {
            $this->validate($plugin);
        } catch (InvalidServiceException $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
