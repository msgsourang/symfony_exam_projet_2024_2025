<?php

namespace App\Security;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordEncoder
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function encodePassword($user, string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword($user, $plainPassword);
    }
}
