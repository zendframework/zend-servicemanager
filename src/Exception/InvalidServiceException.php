<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Exception;

use RuntimeException as SplRuntimeException;

/**
 * This exception is thrown by plugin managers when the created object does not match
 * the plugin manager's conditions
 */
class InvalidServiceException extends SplRuntimeException implements ExceptionInterface
{
}
