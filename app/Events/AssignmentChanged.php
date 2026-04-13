<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class AssignmentChanged extends DomainBroadcastEvent implements ShouldBroadcastNow
{
    public function __construct(string $action, int|string $entityId, array $relatedIds = [])
    {
        parent::__construct($action, $entityId, $relatedIds, 'assignments');
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('assignments.updates')];
    }

    public function broadcastWith(): array
    {
        return [
            'event' => class_basename($this),
            'action' => $this->action,
            'entity_id' => $this->entityId,
            'related_ids' => $this->relatedIds ?? [],
            'module' => $this->module,
            'performed_by' => auth()->user()->name ?? 'System',
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
