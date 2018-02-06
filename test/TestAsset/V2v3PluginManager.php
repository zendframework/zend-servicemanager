<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcTest\ServiceManager\TestAsset;

use Mxc\ServiceManager\AbstractPluginManager;
use Mxc\ServiceManager\Exception\InvalidServiceException;
use Mxc\ServiceManager\Factory\InvokableFactory;

use function get_class;
use function sprintf;

class V2v3PluginManager extends AbstractPluginManager
{
    protected $aliases = [
        'foo' => InvokableObject::class,
    ];

    protected $factories = [
        InvokableObject::class                           => InvokableFactory::class,
        // Legacy (v2) due to alias resolution
        'Mxctestservicemanagertestassetinvokableobject' => InvokableFactory::class,
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
