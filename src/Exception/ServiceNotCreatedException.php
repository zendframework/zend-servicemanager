<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace Mxc\ServiceManager\Exception;

use Interop\Container\Exception\ContainerException;
use RuntimeException as SplRuntimeException;

/**
 * This exception is thrown when the service locator do not manage to create
 * the service (factory that has an error...)
 */
class ServiceNotCreatedException extends SplRuntimeException implements
    ContainerException,
    ExceptionInterface
{
}
