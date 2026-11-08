<?php

namespace App\Events;

use App\Models\Core\SystemNotification;
use App\Models\Core\User;
use App\Services\Core\PermissionService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemNotificationCreated implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public SystemNotification $notification)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("users.{$this->notification->user_id}.notifications")];
    }

    public function broadcastAs(): string
    {
        return 'system.notification.created';
    }

    public function broadcastWith(): array
    {
        $notification = $this->notification;

        return [
            'notification' => [
                'id' => $notification->id,
                'store_id' => $notification->store_id,
                'branch_id' => $notification->branch_id,
                'user_id' => $notification->user_id,
                'module' => $notification->module,
                'entity_type' => $notification->entity_type,
                'entity_id' => $notification->entity_id,
                'action' => $notification->action,
                'title' => $notification->title,
                'message' => $notification->message,
                'data' => $notification->data,
                'link' => $notification->link,
                'severity' => $notification->severity,
                'is_read' => (bool) $notification->is_read,
                'read_at' => $notification->read_at?->toISOString(),
                'created_at' => $notification->created_at?->toISOString(),
            ],
        ];
    }

    public function broadcastWhen(): bool
    {
        $requiredPermission = data_get($this->notification->data, 'required_permission');
        if (! is_string($requiredPermission) || trim($requiredPermission) === '') {
            return true;
        }

        $recipient = User::query()->find($this->notification->user_id);
        if (! $recipient) {
            return false;
        }

        return app(PermissionService::class)->userHasPermission(
            $recipient,
            $requiredPermission,
            $this->notification->store_id ? (int) $this->notification->store_id : null,
        );
    }
}
