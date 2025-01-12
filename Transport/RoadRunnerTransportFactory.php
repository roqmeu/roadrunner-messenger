<?php

declare(strict_types=1);

namespace Roqmeu\Symfony\Messenger\Bridge\RoadRunner\Transport;

use Baldinof\RoadRunnerBundle\Exception\BadConfigurationException;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\WorkerInterface;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class RoadRunnerTransportFactory implements TransportFactoryInterface
{
    public function __construct(
        private ?WorkerInterface $worker,
        private ?RPCInterface $rpc,
    ) {
    }

    public function createTransport(#[\SensitiveParameter] string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $queue = $options['queue_name'] ?? '';

        if (!is_string($queue) || '' === $queue) {
            throw new LogicException("Empty queue_name \"$queue\" passed to the RoadRunner Messenger transport.");
        }

        if (null === $this->rpc) {
            throw BadConfigurationException::rpcNotEnabled();
        }

        return new RoadRunnerTransport($options['queue_name'], $this->worker, $this->rpc, $serializer);
    }

    public function supports(#[\SensitiveParameter] string $dsn, array $options): bool
    {
        return str_starts_with($dsn, 'roadrunner://');
    }
}
