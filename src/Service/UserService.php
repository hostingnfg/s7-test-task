<?php

namespace App\Service;

use App\DTO\RegisterUserRequest;
use App\Entity\Auth;
use App\Entity\User;
use App\Repository\AuthRepository;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    private UserRepository $userRepository;
    private AuthRepository $authRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager,
        AuthRepository $authRepository
    )
    {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->jwtManager = $jwtManager;
        $this->authRepository = $authRepository;
    }

    public function registerUser(RegisterUserRequest $registerUserRequest): User
    {
        $user = new User();
        $user->setEmail($registerUserRequest->email);
        $user->setFirstName($registerUserRequest->firstName);
        $user->setLastName($registerUserRequest->lastName);
        $user->setAdmin($this->isFirstUser());
        $user->setPassword($this->passwordHasher->hashPassword($user, $registerUserRequest->password));
        $this->userRepository->save($user);

        return $user;
    }

    /**
     * @param User $user
     * @return string
     */
    public function generateJWT(User $user): string
    {
        return $this->jwtManager->create($user);
    }

    public function saveJWT(string $jwt, User $user)
    {
        $auth = new Auth();
        $auth->setToken($jwt);
        $auth->setUser($user);
        $auth->setCreatedAt(new \DateTime());
        $auth->setExpiresAt((new \DateTime())->modify('+1 hour'));
        $auth->setRevoked(false);
        $this->authRepository->save($auth);
    }

    public function revokeJWT(string $token, User $user)
    {
        $auth = $this->authRepository->findOneBy(['token' => $token, 'user' => $user]);
        $auth->setRevoked(true);
        $this->authRepository->save($auth);
    }

    private function isFirstUser(): bool
    {
        return $this->userRepository->count([]) === 0;
    }

    public function validateUserCredentials(string $email, string $password): ?User
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return null;
        }

        return $user;
    }
}
