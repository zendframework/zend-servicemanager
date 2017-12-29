<?php

namespace ZendTest\ServiceManager\TestAsset;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class CarFactory implements FactoryInterface
{
	public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
	{
		$car = new Car();
		$car->classifier = 'I was created by a car factory, no diesel issues, I promise';
		return $car;

	}

}
