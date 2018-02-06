<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace Mxc\ServiceManager\Exception;

use Interop\Container\Exception\NotFoundException;
use InvalidArgumentException as SplInvalidArgumentException;

/**
 * This exception is thrown when the service locator do not manage to find a
 * valid factory to create a service
 */
class ServiceNotFoundException extends SplInvalidArgumentException implements
    ExceptionInterface,
    NotFoundException
{
}
