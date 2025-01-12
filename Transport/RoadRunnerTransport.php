<?php

declare(strict_types=1);

namespace Roqmeu\Symfony\Messenger\Bridge\RoadRunner\Transport;

use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\Task\Factory\ReceivedTaskFactory;
use Spiral\RoadRunner\WorkerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class RoadRunnerTransport implements ReceiverInterface, TransportInterface
{
    private SerializerInterface $serializer;
    private RoadRunnerReceiver $receiver;
    private RoadRunnerSender $sender;

    public function __construct(
        private string $queue,
        private WorkerInterface $worker,
        private RPCInterface $rpc,
        ?SerializerInterface $serializer = null,
    ) {
        $this->serializer = $serializer ?? new PhpSerializer();
    }

    public function get(): iterable
    {
        return $this->getReceiver()->get();
    }

    public function ack(Envelope $envelope): void
    {
        $this->getReceiver()->ack($envelope);
    }

    public function reject(Envelope $envelope): void
    {
        $this->getReceiver()->reject($envelope);
    }

    public function send(Envelope $envelope): Envelope
    {
        return $this->getSender()->send($envelope);
    }

    private function getReceiver(): RoadRunnerReceiver
    {
        return $this->receiver ??= new RoadRunnerReceiver($this->worker, new ReceivedTaskFactory($this->worker), $this->serializer);
    }

    private function getSender(): RoadRunnerSender
    {
        return $this->sender ??= new RoadRunnerSender((new Jobs($this->rpc))->connect($this->queue), $this->serializer);
    }
}
