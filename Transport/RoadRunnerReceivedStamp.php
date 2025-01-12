<?php

declare(strict_types=1);

namespace Roqmeu\Symfony\Messenger\Bridge\RoadRunner\Transport;

use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

class RoadRunnerReceivedStamp implements NonSendableStampInterface
{
    public function __construct(
        private ReceivedTaskInterface $task,
    ) {
    }

    public function getTask(): ReceivedTaskInterface
    {
        return $this->task;
    }
}
