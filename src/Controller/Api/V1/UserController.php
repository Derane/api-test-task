<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\DTO\UserPayloadDTO;
use App\DTO\UserResponseDTO;
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
        return $this->json($this->userService->list($this->getUser()));
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
    public function create(#[MapRequestPayload] UserPayloadDTO $dto): JsonResponse
    {
        return $this->json($this->userService->create($dto), Response::HTTP_CREATED);
    }

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload] UserPayloadDTO $dto): JsonResponse
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
