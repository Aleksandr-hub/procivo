<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Organization chart node (department or person)')]
final readonly class OrgChartNodeDTO implements \JsonSerializable
{
    /**
     * @param 'department'|'person' $type
     * @param list<OrgChartNodeDTO> $children
     */
    public function __construct(
        #[OA\Property(description: 'Node type', enum: ['department', 'person'])]
        public string $type,
        #[OA\Property(description: 'Node UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Display label')]
        public string $label,
        #[OA\Property(description: 'Department code (for department nodes)', nullable: true)]
        public ?string $departmentCode = null,
        #[OA\Property(description: 'Employee number (for person nodes)', nullable: true)]
        public ?string $employeeNumber = null,
        #[OA\Property(description: 'Employee email (for person nodes)', format: 'email', nullable: true)]
        public ?string $email = null,
        #[OA\Property(description: 'Position name (for person nodes)', nullable: true)]
        public ?string $positionName = null,
        #[OA\Property(description: 'Department name (for person nodes)', nullable: true)]
        public ?string $departmentName = null,
        #[OA\Property(description: 'Whether employee is department head (for person nodes)', nullable: true)]
        public ?bool $isHead = null,
        #[OA\Property(description: 'Manager employee UUID (for person nodes)', format: 'uuid', nullable: true)]
        public ?string $managerId = null,
        #[OA\Property(description: 'Child nodes', type: 'array', items: new OA\Items(ref: new \Nelmio\ApiDocBundle\Attribute\Model(type: self::class)))]
        public array $children = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'type' => $this->type,
            'id' => $this->id,
            'label' => $this->label,
            'children' => $this->children,
        ];

        if ('department' === $this->type) {
            $data['departmentCode'] = $this->departmentCode;
        } else {
            $data['employeeNumber'] = $this->employeeNumber;
            $data['email'] = $this->email;
            $data['positionName'] = $this->positionName;
            $data['departmentName'] = $this->departmentName;
            $data['isHead'] = $this->isHead;
            $data['managerId'] = $this->managerId;
        }

        return $data;
    }
}
