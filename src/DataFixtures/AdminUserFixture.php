<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminUserFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@example.com');

        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'admin123'
        );

        $admin->setPassword($hashedPassword);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setName('Admin');
        $admin->setLastName('Admin');
        $admin->setUsername('admin');
        $admin->setBirthday(new \DateTimeImmutable('now'));



        $manager->persist($admin);
        $manager->flush();
    }
}
