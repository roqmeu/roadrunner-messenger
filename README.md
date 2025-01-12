RoadRunner Messenger
==============

Provides RoadRunner integration for Symfony Messenger.

Limitations:

- RoadRunnerReceiver runs in blocking mode due to the implementation of WorkerInterface::waitPayload. When specifying multiple transports in `bin/console messenger:consume ...` RoadRunnerReceiver will block the other transports.

Using:

```yaml
framework:
  messenger:
    transports:
      default:
        dsn: 'roadrunner://'
        options:
          queue_name: memory # ... pipeline name in roadrunner, required

    routing:
      # ... yours routing parameters

services:
  _defaults:
    autowire: true
    autoconfigure: true

  messenger.transport.roadrunner.factory: # ... required
    class: Roqmeu\Symfony\Messenger\Bridge\RoadRunner\Transport\RoadRunnerTransportFactory
    tags: [ messenger.transport_factory ]
```

## Igbinary support

Added serializer with ext-igbinary support.

RoadRunner [recommends](https://docs.roadrunner.dev/docs/key-value/overview-kv#igbinary-value-serialization) using [ext-igbinary](https://pecl.php.net/package/igbinary) for data serialization. 

Using:

```yaml
framework:
  messenger:
    serializer:
      default_serializer: messenger.transport.igbinary_serializer

    transports:
      # ... yours transports parameters

    routing:
      # ... yours routing parameters

services:
  _defaults:
    autowire: true
    autoconfigure: true

  messenger.transport.igbinary_serializer:
    class: Roqmeu\Symfony\Messenger\Bridge\RoadRunner\Serialization\IgbinarySerializer
```
