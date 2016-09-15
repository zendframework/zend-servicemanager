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

Generates to STDOUT a replacement configuration file containing dependency
configuration for the named class with which to configure the
ConfigAbstractFactory.
```

This utility will generate dependency configuration for the named class for use
with the [ConfigAbstractFactory](config-abstract-factory.md). When doing so, it
will read the named configuration file, and merge any configuration it generates
with the return values of that file, emitting the updated version to STDOUT.
This allows you to pipe it back to the original:

```bash
$ ./vendor/bin/generate-deps-for-config-factory \
> ./config/autoload/dependencies.local.php \
> "Application\\Model\\AlbumModel" > ./config/autoload/dependencies.local.php
```

Alternately, you can pipe them to a new file, so that you can diff the original
to the generated file.

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
