<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\SearchUsers;

use App\Identity\Application\DTO\UserDTO;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class SearchUsersHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @return list<UserDTO>
     */
    public function __invoke(SearchUsersQuery $query): array
    {
        $users = $this->userRepository->search($query->search, $query->limit);

        return array_map(UserDTO::fromEntity(...), $users);
    }
}
