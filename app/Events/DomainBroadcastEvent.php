<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

abstract class DomainBroadcastEvent
{
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public string $action,
        public int|string $entityId,
        public array $relatedIds = [],
        public string $module = 'weapons',
    ) {}

    abstract public function broadcastOn(): array;

    public function broadcastAs(): string
    {
        return class_basename(static::class);
    }
}
