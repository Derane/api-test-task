<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixture extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $root = new User();
        $root->setLogin('root');
        $root->setPhone('00000000');
        $root->setPassword($this->passwordHasher->hashPassword($root, 'rootpass'));
        $root->setApiToken('root-api-token-for-testing-purposes');
        $root->setRoles([User::ROLE_ROOT]);
        $manager->persist($root);

        $user = new User();
        $user->setLogin('user');
        $user->setPhone('11111111');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'userpass'));
        $user->setApiToken('user-api-token-for-testing-purposes');
        $user->setRoles([User::ROLE_USER]);
        $manager->persist($user);

        $manager->flush();
    }
}
