<?php

declare(strict_types=1);

namespace Roqmeu\Symfony\Messenger\Bridge\RoadRunner\Transport;

use Spiral\RoadRunner\Jobs\Task\Factory\ReceivedTaskFactoryInterface;
use Spiral\RoadRunner\WorkerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class RoadRunnerReceiver implements ReceiverInterface
{
    public function __construct(
        private WorkerInterface $worker,
        private ReceivedTaskFactoryInterface $receivedTaskFactory,
        private SerializerInterface $serializer,
    ) {
    }

    public function get(): iterable
    {
        return $this->getEnvelope();
    }

    private function getEnvelope(): iterable
    {
        try {
            $payload = $this->worker->waitPayload();

            if (null === $payload) {
                return [];
            }

            $task = $this->receivedTaskFactory->create($payload);

            try {
                $envelope = $this->serializer->decode([
                    'body' => $task->getPayload(),
                    'headers' => $task->getHeaders(),
                ]);
            } catch (MessageDecodingFailedException $exception) {
                try {
                    $task->nack($exception->getMessage());
                } catch (\Exception $exception) {
                    throw new TransportException($exception->getMessage(), 0, $exception);
                }

                throw $exception;
            }

            return [$envelope->with(new RoadRunnerReceivedStamp($task))];
        } catch (\Exception $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    public function ack(Envelope $envelope): void
    {
        $stamp = $this->findStamp($envelope);

        try {
            $stamp->getTask()->ack();
        } catch (\Exception $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    public function reject(Envelope $envelope): void
    {
        $stamp = $this->findStamp($envelope);

        $errorDetails = $envelope->last(ErrorDetailsStamp::class);

        try {
            if (null !== $errorDetails) {
                $stamp->getTask()->nack($errorDetails->getExceptionMessage());
            } else {
                $stamp->getTask()->nack('');
            }
        } catch (\Exception $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    private function findStamp(Envelope $envelope): RoadRunnerReceivedStamp
    {
        $receivedStamp = $envelope->last(RoadRunnerReceivedStamp::class);

        if (null === $receivedStamp) {
            throw new LogicException('No "RoadrunnerReceivedStamp" stamp found on the Envelope.');
        }

        return $receivedStamp;
    }
}
