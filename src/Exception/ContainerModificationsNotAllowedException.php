<?php
/**
 * @link      http://github.com/Mxcframework/Mxc-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Mxc Technologies USA Inc. (http://www.Mxc.com)
 * @license   http://framework.Mxc.com/license/new-bsd New BSD License
 */

namespace Mxc\ServiceManager\Exception;

use DomainException;

/**
 * @inheritDoc
 */
class ContainerModificationsNotAllowedException extends DomainException implements ExceptionInterface
{
    /**
     * @param string $service Name of service that already exists.
     * @return self
     */
    public static function fromExistingService($service)
    {
        return new self(sprintf(
            'The container does not allow replacing or updating a service'
            . ' with existing instances; the following service'
            . ' already exists in the container: %s',
            $service
        ));
    }
}
