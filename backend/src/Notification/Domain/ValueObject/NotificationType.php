<?php

declare(strict_types=1);

namespace App\Notification\Domain\ValueObject;

enum NotificationType: string
{
    case TaskAssigned = 'task_assigned';
    case TaskStatusChanged = 'task_status_changed';
    case CommentAdded = 'comment_added';
    case TaskCreated = 'task_created';
    case ProcessNotification = 'process_notification';
}
