<?php

namespace App\Validator;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
class UniqueEmailValidator extends ConstraintValidator
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validate($value, Constraint $constraint): void
    {
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $value->email]);

        if ($existingUser) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value->email)
                ->atPath('email')
                ->addViolation();
        }
    }
}
