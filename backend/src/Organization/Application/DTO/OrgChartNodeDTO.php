<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

final readonly class OrgChartNodeDTO implements \JsonSerializable
{
    /**
     * @param 'department'|'person' $type
     * @param list<OrgChartNodeDTO> $children
     */
    public function __construct(
        public string $type,
        public string $id,
        public string $label,
        public ?string $departmentCode = null,
        public ?string $employeeNumber = null,
        public ?string $email = null,
        public ?string $positionName = null,
        public ?string $departmentName = null,
        public ?bool $isHead = null,
        public ?string $managerId = null,
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
