<?php

namespace App\Controller;

use App\DTO\LoginRequest;
use App\DTO\RegisterUserRequest;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    private UserService $userService;

    private Security $security;

    public function __construct(
        UserService $userService,
        Security $security
    )
    {
        $this->userService = $userService;
        $this->security = $security;
    }

    #[Route('/api/register', name: 'register', methods: ['POST'])]
    public function registerUser(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        $registerUserRequest = $serializer->deserialize(
            $request->getContent(),
            RegisterUserRequest::class,
            'json'
        );
        $errors = $validator->validate($registerUserRequest);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], 400);
        }

        $user = $this->userService->registerUser($registerUserRequest);
        $jwt = $this->userService->generateJWT($user);
        $this->userService->saveJWT($jwt, $user);

        return new JsonResponse(['token' => $jwt], 201);
    }


    #[Route('/api/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        $loginRequest = $serializer->deserialize(
            $request->getContent(),
            LoginRequest::class,
            'json'
        );

        $errors = $validator->validate($loginRequest);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], 400);
        }

        $user = $this->userService->validateUserCredentials($loginRequest->email, $loginRequest->password);
        if (!$user) {
            return new JsonResponse(['status' => false], 401);
        }
        $jwt = $this->userService->generateJWT($user);
        $this->userService->saveJWT($jwt, $user);

        return new JsonResponse(['token' => $jwt], 200);
    }

    #[Route('/api/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        $authHeader = $request->headers->get('Authorization');
        $token = substr($authHeader, 7);
        $this->userService->revokeJWT($token, $user);

        return new JsonResponse(['status' => true], 200);
    }
}
