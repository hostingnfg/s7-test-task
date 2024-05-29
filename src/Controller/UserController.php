<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    private UserService $userService;
    private Security $security;
    private UserRepository $userRepository;

    public function __construct(
        UserService $userService,
        Security $security,
        UserRepository $userRepository,
    )
    {
        $this->userService = $userService;
        $this->security = $security;
        $this->userRepository = $userRepository;
    }
    #[Route('/api/users', name: 'user_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $user = $this->security->getUser();

        if ($user->isAdmin()) {
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = max(1, (int) $request->query->get('limit', 10));
            $offset = ($page - 1) * $limit;

            $users = $this->userRepository->findBy([], null, $limit, $offset);
            $totalUsers = $this->userRepository->count([]);

            return $this->json([
                'data' => $users,
                'total' => $totalUsers,
                'page' => $page,
                'limit' => $limit
            ], 200, [], ['groups' => 'user:read']);
        } else {
            return $this->json($user, 200, [], ['groups' => 'user:read']);
        }
    }

    #[Route('/api/users/{id}', name: 'user_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $authUser = $this->security->getUser();
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['status' => false], 404);
        }
        if (!($authUser->isAdmin() || $authUser->getId() == $user->getId())) {
            return new JsonResponse(['status' => false], 403);
        }

        return $this->json($user, 200, [], ['groups' => 'user:read']);
    }

    #[Route('/api/users/{id}', name: 'user_update', methods: ['PUT'])]
    public function update(int $id, Request $request, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        $authUser = $this->security->getUser();
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['status' => false], 404);
        }
        if (!($authUser->isAdmin() || $authUser->getId() == $user->getId())) {
            return new JsonResponse(['status' => false], 403);
        }

        $serializer->deserialize($request->getContent(), User::class, 'json', [
            'object_to_populate' => $user,
            'groups' => 'user:write'
        ]);

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], 400);
        }

        $this->userRepository->save($user);

        return $this->json($user, 200, [], ['groups' => 'user:read']);
    }

    #[Route('/api/users/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $authUser = $this->security->getUser();
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['status' => false], 404);
        }
        if (!($authUser->isAdmin() || $authUser->getId() == $user->getId())) {
            return new JsonResponse(['status' => false], 403);
        }
        $this->userRepository->delete($user);
        return new JsonResponse(['status' => true], 200);
    }
}
