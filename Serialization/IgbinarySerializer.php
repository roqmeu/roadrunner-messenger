<?php

declare(strict_types=1);

namespace Roqmeu\Symfony\Messenger\Bridge\RoadRunner\Serialization;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\MessageDecodingFailedStamp;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class IgbinarySerializer implements SerializerInterface
{
    public function decode(array $encodedEnvelope): Envelope
    {
        if (empty($encodedEnvelope['body'])) {
            throw new MessageDecodingFailedException(
                'Encoded envelope should have at least a "body", or maybe you should implement your own serializer.'
            );
        }

        return $this->unserialize($encodedEnvelope['body']);
    }

    public function encode(Envelope $envelope): array
    {
        $envelope = $envelope->withoutStampsOfType(NonSendableStampInterface::class);

        $body = igbinary_serialize($envelope);

        return [
            'body' => $body,
        ];
    }

    private function unserialize(string $contents): Envelope
    {
        if ('' === $contents) {
            throw new MessageDecodingFailedException('Could not decode an empty message using PHP serialization.');
        }

        try {
            /** @var Envelope $envelope */
            $envelope = igbinary_unserialize($contents);
        } catch (\Throwable $e) {
            if ($e instanceof MessageDecodingFailedException) {
                throw $e;
            }

            throw new MessageDecodingFailedException('Could not decode Envelope: ' . $e->getMessage(), 0, $e);
        }

        if (!$envelope instanceof Envelope) {
            throw new MessageDecodingFailedException('Could not decode message into an Envelope.');
        }

        if ($envelope->getMessage() instanceof \__PHP_Incomplete_Class) {
            $envelope = $envelope->with(new MessageDecodingFailedStamp());
        }

        return $envelope;
    }
}
