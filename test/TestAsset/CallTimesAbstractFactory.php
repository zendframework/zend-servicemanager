<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace MxcTest\ServiceManager\TestAsset;

use Interop\Container\ContainerInterface;
use Mxc\ServiceManager\Factory\AbstractFactoryInterface;

class CallTimesAbstractFactory implements AbstractFactoryInterface
{
    protected static $callTimes = 0;

    /**
     * {@inheritDoc}
     */
    public function canCreate(ContainerInterface $container, $name)
    {
        self::$callTimes++;

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(ContainerInterface $container, $className, array $options = null)
    {
    }

    /**
     * @return int
     */
    public static function getCallTimes()
    {
        return self::$callTimes;
    }

    /**
     * @param int $callTimes
     */
    public static function setCallTimes($callTimes)
    {
        self::$callTimes = $callTimes;
    }
}
