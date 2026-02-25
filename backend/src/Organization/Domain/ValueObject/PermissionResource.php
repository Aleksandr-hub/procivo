<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObject;

/**
 * Ресурс (сутність), до якого застосовується дозвіл.
 */
enum PermissionResource: string
{
    /** Працівники — профілі, статуси, призначення */
    case Employee = 'employee';

    /** Департаменти — структура підрозділів, дерево */
    case Department = 'department';

    /** Посади — довідник посад організації */
    case Position = 'position';

    /** Ролі — управління ролями та їхніми дозволами */
    case Role = 'role';

    /** Запрошення — інвайти для нових працівників */
    case Invitation = 'invitation';

    /** Організація — налаштування самої організації */
    case Organization = 'organization';
}
