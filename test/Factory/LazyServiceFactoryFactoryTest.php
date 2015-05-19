<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace ZendTest\ServiceManager\Factory;

use Zend\ServiceManager\Factory\LazyServiceFactoryFactory;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use ZendTest\ServiceManager\Asset\InvokableObject;

class LazyServiceFactoryFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionThrownWhenLazyServiceConfigMissing()
    {
        $serviceLocator = $this->getMock(ServiceLocatorInterface::class);
        $factory        = new LazyServiceFactoryFactory();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Missing "lazy_services" config key'
        );

        $object = $factory($serviceLocator, InvokableObject::class);
    }

    public function testExceptionThrownWhenLazyServiceConfigMissingClassMap()
    {
        $serviceLocator = $this->getMock(ServiceLocatorInterface::class);
        $serviceLocator->expects($this->once())
            ->method('get')
            ->with('Config')
            ->will($this->returnValue([
                'lazy_services' => []
            ]));

        $factory = new LazyServiceFactoryFactory();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Missing "class_map" config key in "lazy_services"'
        );

        $object = $factory($serviceLocator, InvokableObject::class);
    }
}
