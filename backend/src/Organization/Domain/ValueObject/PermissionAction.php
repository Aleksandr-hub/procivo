<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObject;

/**
 * Дія, яку дозвіл дозволяє виконувати над ресурсом.
 */
enum PermissionAction: string
{
    /** Перегляд — читання даних ресурсу */
    case View = 'view';

    /** Створення — додавання нових записів */
    case Create = 'create';

    /** Оновлення — редагування існуючих записів */
    case Update = 'update';

    /** Видалення — видалення записів */
    case Delete = 'delete';

    /** Повне управління — включає всі дії (view, create, update, delete) */
    case Manage = 'manage';
}
