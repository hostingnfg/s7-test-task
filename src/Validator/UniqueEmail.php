<?php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute] class UniqueEmail extends Constraint
{
    public string $message = 'The email "{{ value }}" is already used.';

    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
