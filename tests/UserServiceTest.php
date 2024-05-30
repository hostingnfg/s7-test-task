<?php

namespace App\Tests;

use App\DTO\RegisterUserRequest;
use App\Entity\Auth;
use App\Entity\User;
use App\Repository\AuthRepository;
use App\Repository\UserRepository;
use App\Service\UserService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Mockery;

class UserServiceTest extends TestCase
{
    private UserRepository $userRepository;
    private AuthRepository $authRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private JWTTokenManagerInterface $jwtManager;
    private UserService $userService;

    protected function setUp(): void
    {
        $this->userRepository = Mockery::mock(UserRepository::class);
        $this->authRepository = Mockery::mock(AuthRepository::class);
        $this->passwordHasher = Mockery::mock(UserPasswordHasherInterface::class);
        $this->jwtManager = Mockery::mock(JWTTokenManagerInterface::class);

        $this->userService = new UserService(
            $this->userRepository,
            $this->passwordHasher,
            $this->jwtManager,
            $this->authRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testRegisterUser(): void
    {
        $request = new RegisterUserRequest();
        $request->email = 'user@user.com';
        $request->firstName = 'fName';
        $request->lastName = 'lName';
        $request->password = 'pass';

        $this->userRepository->shouldReceive('count')->andReturn(0);
        $this->passwordHasher->shouldReceive('hashPassword')->andReturn('rndHash');
        $this->userRepository->shouldReceive('save')->once();

        $user = $this->userService->registerUser($request);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('user@user.com', $user->getEmail());
        $this->assertEquals('fName', $user->getFirstName());
        $this->assertEquals('lName', $user->getLastName());
        $this->assertEquals('rndHash', $user->getPassword());
        $this->assertTrue($user->isAdmin());
    }

    public function testGenerateJWT(): void
    {
        $user = new User();
        $this->jwtManager->shouldReceive('create')->with($user)->andReturn('JWT');

        $token = $this->userService->generateJWT($user);

        $this->assertEquals('JWT', $token);
    }

    public function testRevokeJWT(): void
    {
        $user = new User();
        $token = 'JWT';
        $auth = new Auth();
        $auth->setToken($token);
        $auth->setUser($user);
        $auth->setRevoked(false);

        $this->authRepository->shouldReceive('findOneBy')->with(['token' => $token, 'user' => $user])->andReturn($auth);
        $this->authRepository->shouldReceive('save')->once();

        $this->userService->revokeJWT($token, $user);

        $this->assertTrue($auth->isRevoked());
    }

    public function testValidateUserCredentials(): void
    {
        $email = 'test@example.com';
        $password = 'password';

        $user = new User();
        $user->setEmail($email);
        $user->setPassword('hashed_password');

        $this->userRepository->shouldReceive('findOneBy')->with(['email' => $email])->andReturn($user);
        $this->passwordHasher->shouldReceive('isPasswordValid')->with($user, $password)->andReturn(true);

        $validatedUser = $this->userService->validateUserCredentials($email, $password);

        $this->assertInstanceOf(User::class, $validatedUser);
        $this->assertEquals($email, $validatedUser->getEmail());
    }
}
