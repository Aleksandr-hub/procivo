<?php

declare(strict_types=1);

namespace App\Notification\Domain\ValueObject;

enum NotificationType: string
{
    case TaskAssigned = 'task_assigned';
    case TaskCompleted = 'task_completed';
    case TaskStatusChanged = 'task_status_changed';
    case CommentAdded = 'comment_added';
    case ProcessStarted = 'process_started';
    case ProcessCompleted = 'process_completed';
    case ProcessCancelled = 'process_cancelled';
    case InvitationReceived = 'invitation_received';
}
