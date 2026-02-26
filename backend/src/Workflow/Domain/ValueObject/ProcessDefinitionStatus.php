<?php

declare(strict_types=1);

namespace App\Workflow\Domain\ValueObject;

enum ProcessDefinitionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
