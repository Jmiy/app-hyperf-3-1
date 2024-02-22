<?php

declare(strict_types=1);

namespace Business\Hyperf\Signal;

use Hyperf\Signal\Annotation\Signal;
use Hyperf\Signal\SignalHandlerInterface;

//#[Signal]
class TermSignalHandler implements SignalHandlerInterface
{
    public function listen(): array
    {
        return [
            [SignalHandlerInterface::WORKER, SIGTERM],
        ];
    }

    public function handle(int $signal): void
    {
        var_dump($signal);
    }
}
