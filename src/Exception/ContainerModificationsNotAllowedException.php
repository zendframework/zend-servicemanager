<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Exception;

use DomainException;

/**
 * @inheritDoc
 */
class ContainerModificationsNotAllowedException extends DomainException implements ExceptionInterface
{
    public static function fromExistingService($service)
    {
        return new self(sprintf(
            'The container does not allow to replace/update a service'
            . ' with existing instances; the following '
            . 'already exist in the container: %s',
            $service
        ));
    }
}
