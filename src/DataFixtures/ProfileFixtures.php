<?php

namespace App\DataFixtures;

use App\Entity\Profile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ProfileFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $profile = new Profile();
        $profile->setUser($this->getReference('user_entity'));
        $profile->setRole('ROLE_ADMIN');
        $profile->setFirstName('Gauthier');
        $profile->setLastName('Auge');
        $profile->setUsername('Gaugau');
        $profile->setBirthday(new \DateTime('1990-01-01'));
        $profile->setGender('male');
        $profile->setPhoneNumber('1234567890');
        $profile->setBio('A brief bio about gaugau.');
        $profile->setFacebook('gau.aug');
        $profile->setInstagram('gau.aug');
        $profile->setX('gau.aug');
        $profile->setCreatedAt(new \DateTime());

        $manager->persist($profile);

        for ($i = 1; $i <= 20; $i++) {
            $profile = new Profile();
            $profile->setUser($this->getReference('user_' . $i));
            $profile->setRole('ROLE_USER');
            $profile->setFirstName('FirstName' . $i);
            $profile->setLastName('LastName' . $i);
            $profile->setUsername('username' . $i);
            $profile->setBirthday(new \DateTime('1990-01-01'));
            $profile->setGender('male');
            $profile->setPhoneNumber('123456789' . $i);
            $profile->setBio('A brief bio about user ' . $i);
            $profile->setFacebook('facebook' . $i);
            $profile->setInstagram('instagram' . $i);
            $profile->setX('x' . $i);
            $profile->setCreatedAt(new \DateTime());

            $manager->persist($profile);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}