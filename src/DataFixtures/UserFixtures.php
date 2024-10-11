<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('admin@admin.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'testtest'));
        $user->setCreatedAt(new \DateTime('now'));
        $user->setTokenRegistration('');
        $user->setResetPwdToken('');
        $user->setVerified(true);
        $user->setTokenRegistrationLifetime(new \DateTime('+1 day'));
        $user->setResetPwdTokenLifetime(new \DateTime('+1 hour'));
        $user->setLastPasswordResetRequest(new \DateTime('now'));

        $manager->persist($user);
        $this->addReference('user_entity', $user);

        for ($i = 1; $i <= 20; $i++) {
            $user = new User();
            $user->setEmail("user{$i}@example.com");
            $user->setPassword($this->passwordHasher->hashPassword($user, "password{$i}"));
            $user->setCreatedAt(new \DateTime('now'));
            $user->setTokenRegistration('');
            $user->setResetPwdToken('');
            $user->setVerified(false);
            $user->setTokenRegistrationLifetime(new \DateTime('+1 day'));
            $user->setResetPwdTokenLifetime(new \DateTime('+1 hour'));
            $user->setLastPasswordResetRequest(new \DateTime('now'));

            $manager->persist($user);
            $this->addReference('user_' . $i, $user);
        }

        $manager->flush();
    }
}