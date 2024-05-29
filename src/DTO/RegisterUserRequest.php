<?php

namespace App\DTO;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\UniqueEmail;

#[UniqueEmail]
class RegisterUserRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    public string $firstName;

    #[Assert\NotBlank]
    public string $lastName;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public string $password;

    #[Assert\Type('boolean')]
    public bool $isAdmin;
}
