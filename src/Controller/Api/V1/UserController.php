<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\DTO\CreateUserDTO;
use App\DTO\UpdateUserDTO;
use App\DTO\UserResponseDTO;
use App\Entity\User;
use App\Service\UserServiceInterface;
use App\Voter\UserVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/v1/api/users')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($currentUser->hasRole('ROLE_ROOT')) {
            return $this->json($this->userService->list());
        }

        return $this->json([UserResponseDTO::fromEntity($currentUser)]);
    }

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->findOrFail($id);

        $this->denyAccessUnlessGranted(UserVoter::VIEW, $user);

        return $this->json(UserResponseDTO::fromEntity($user));
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted(UserVoter::CREATE)]
    public function create(#[MapRequestPayload] CreateUserDTO $dto): JsonResponse
    {
        $response = $this->userService->create($dto);

        return $this->json($response, Response::HTTP_CREATED);
    }

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload] UpdateUserDTO $dto): JsonResponse
    {
        $user = $this->userService->findOrFail($id);

        $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);

        return $this->json($this->userService->update($user, $dto));
    }

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->userService->findOrFail($id);

        $this->denyAccessUnlessGranted(UserVoter::DELETE, $user);

        $this->userService->delete($user);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
