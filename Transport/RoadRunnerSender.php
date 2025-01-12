<?php

declare(strict_types=1);

namespace Roqmeu\Symfony\Messenger\Bridge\RoadRunner\Transport;

use Spiral\RoadRunner\Jobs\Exception\JobsException;
use Spiral\RoadRunner\Jobs\Options;
use Spiral\RoadRunner\Jobs\QueueInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class RoadRunnerSender implements SenderInterface
{
    public function __construct(
        private QueueInterface $queue,
        private SerializerInterface $serializer,
    ) {
    }

    public function send(Envelope $envelope): Envelope
    {
        $encodedMessage = $this->serializer->encode($envelope);

        /** @var DelayStamp|null $delayStamp */
        $delayStamp = $envelope->last(DelayStamp::class);

        $options = new Options($delayStamp ? (int)($delayStamp->getDelay() / 1000) : 0);

        foreach ($encodedMessage['headers'] ?? [] as $name => $value) {
            $options->withHeader($name, $value);
        }

        try {
            $this->queue->dispatch(
                $this->queue->create($envelope->getMessage()::class, $encodedMessage['body'], $options)
            );
        } catch (JobsException $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }

        return $envelope;
    }
}
