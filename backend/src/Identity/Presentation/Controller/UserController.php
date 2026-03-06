<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\DTO\UserDTO;
use App\Identity\Application\Query\SearchUsers\SearchUsersQuery;
use App\Shared\Application\Bus\QueryBusInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Users')]
#[Route('/api/v1/users', name: 'api_v1_users_')]
final readonly class UserController
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {
    }

    #[OA\Get(summary: 'Search users by name or email')]
    #[OA\Parameter(name: 'search', in: 'query', description: 'Search term', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'limit', in: 'query', description: 'Max results (max 50)', schema: new OA\Schema(type: 'integer', default: 20, maximum: 50))]
    #[OA\Response(
        response: 200,
        description: 'List of matching users',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: UserDTO::class))),
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
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
