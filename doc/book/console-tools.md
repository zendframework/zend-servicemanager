# Console Tools

Starting in 3.2.0, zend-servicemanager began shipping with console tools. This
document details each.

## generate-deps-for-config-factory

```bash
$ ./vendor/bin/generate-deps-for-config-factory
Usage:

  generate-deps-for-config-factory [-h|--help|help] <configFile> <className>

Arguments:

  -h|--help|help    This usage message
  <configFile>      Path to an existing config file for which to generate
                    additional configuration. Must return an array.
  <className>       Name of the class to reflect and for which to generate
                    dependency configuration.


Reads the provided configuration file, and injects it with
ConfigAbstractFactory dependency configuration for the provided class
name, writing the changes back to the file.
```

This utility will generate dependency configuration for the named class for use
with the [ConfigAbstractFactory](config-abstract-factory.md). When doing so, it
will read the named configuration file, and merge any configuration it generates
with the return values of that file, writing the changes back to the original
file.

## generate-factory-for-class

```bash
$ ./vendor/bin/generate-factory-for-class

Usage:

  ./bin/generate-factory-for-class [-h|--help|help] <className>

Arguments:

  -h|--help|help    This usage message
  <className>       Name of the class to reflect and for which to generate
                    a factory.

Generates to STDOUT a factory for creating the specified class; this may then
be added to your application, and configured as a factory for the class.
```

This utility generates a factory class for the given class, based on the
typehints in its constructor. The factory is emitted to STDOUT, and may be piped
to a file if desired:

```bash
$ ./vendor/bin/generate-factory-for-class \
> "Application\\Model\\AlbumModel" > ./module/Application/src/Model/AlbumModelFactory.php
```

The class generated implements `Zend\ServiceManager\Factory\FactoryInterface`,
and is generated within the same namespace as the originating class.

## create-factory-map

```bash
Usage:

  create-factory-map [-h|--help|help] <configFile> <className> <factoryName> [<key>]

Arguments:

  -h|--help|help    This usage message
  <configFile>      Path to an config file in which to map the factory.
                    If the file does not exist, it will be created. If
                    it does exist, it must return an array.
  <className>       Name of the class to map to a factory.
  <factoryName>     Name of the factory class to use with <className>.
  [<key>]           (Optional) The top-level configuration key under which
                    the factory map should appear; defaults to
                    "service_manager".

Reads the provided configuration file, creating it if necessary, and
injects it with a mapping of the given class to its factory. If key is
provided, the factory configuration will be injected under that key, and
not the default "service_manager" key.
```

This utility maps the given class to the given factory, writing it to the
specified configuration file. If a key is given, then the mapping will occur
under that top-level key (the default is the `service_manager` key).

As an example, if using this to map a controller for a zend-mvc application, you
might use:

```bash
$ ./vendor/bin/create-factory-map \
> module/Application/config/module.config.php \
> "Application\\Controller\\PingController" \
> "Zend\\Mvc\Controller\\LazyControllerAbstractFactory" \
> controllers
```

For Expressive, you might do the following to map middleware to a factory:

```bash
$ ./vendor/bin/create-factory-map \
> config/autoload/routes.global.php \
> "Application\\Middleware\\PingMiddleware" \
> "Zend\\ServiceManager\\AbstractFactory\\ReflectionBasedAbstractFactory" \
> dependencies
```
