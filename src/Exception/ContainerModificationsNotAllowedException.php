<?php
/**
 * @link      http://github.com/zendframework/zend-servicemanager for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\ServiceManager\Exception;

use DomainException;

use function sprintf;

/**
 * @inheritDoc
 */
class ContainerModificationsNotAllowedException extends DomainException implements ExceptionInterface
{
    /**
     * @param string $service Name of service that already exists.
     */
    public static function fromExistingService(string $service) : self
    {
        return new self(sprintf(
            'The container does not allow replacing or updating a service'
            . ' with existing instances; the following service'
            . ' already exists in the container: %s',
            $service
        ));
    }
}
