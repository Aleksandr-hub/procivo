<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\Query\SearchUsers\SearchUsersQuery;
use App\Shared\Application\Bus\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/users', name: 'api_v1_users_')]
final readonly class UserController
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route('', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $dto = $this->queryBus->ask(new SearchUsersQuery(
            search: $request->query->getString('search', ''),
            limit: min((int) $request->query->get('limit', '20'), 50),
        ));

        return new JsonResponse($dto);
    }
}
