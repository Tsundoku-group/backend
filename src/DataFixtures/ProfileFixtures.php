<?php

namespace App\DataFixtures;

use App\Entity\Profile;
use App\Entity\User;
use DateTime;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProfileFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $connection = $manager->getConnection();
        $connection->executeStatement('ALTER SEQUENCE profile_id_seq RESTART WITH 1');

        $adminUser = $this->getReference('user_entity', User::class);

        // Profil actif pour l'admin
        $adminActiveProfile = new Profile();
        $adminActiveProfile->setUser($adminUser);
        $adminActiveProfile->setRole('ROLE_ADMIN');
        $adminActiveProfile->setFirstName('AdminFirstName_1');
        $adminActiveProfile->setLastName('AdminLastName_1');
        $adminActiveProfile->setUsername('admin_username_1');
        $adminActiveProfile->setBirthday(new DateTime('1985-01-01'));
        $adminActiveProfile->setGender('Féminin');
        $adminActiveProfile->setPhoneNumber('0001112221');
        $adminActiveProfile->setBio('Admin profile 1 bio.');
        $adminActiveProfile->setFacebook('admin_facebook_1');
        $adminActiveProfile->setInstagram('admin_instagram_1');
        $adminActiveProfile->setX('admin_x_1');
        $adminActiveProfile->setCreatedAt(new DateTimeImmutable());
        $adminActiveProfile->setType('admin');
        $adminActiveProfile->setActiveProfile(true);
        $adminActiveProfile->setStatus('offline');

        $manager->persist($adminActiveProfile);

        // Profil inactif pour l'admin
        $adminInactiveProfile = new Profile();
        $adminInactiveProfile->setUser($adminUser);
        $adminInactiveProfile->setRole('ROLE_ADMIN');
        $adminInactiveProfile->setFirstName('AdminFirstName_2');
        $adminInactiveProfile->setLastName('AdminLastName_2');
        $adminInactiveProfile->setUsername('admin_username_2');
        $adminInactiveProfile->setBirthday(new DateTime('1985-01-01'));
        $adminInactiveProfile->setGender('Féminin');
        $adminInactiveProfile->setPhoneNumber('0001112222');
        $adminInactiveProfile->setBio('Admin profile 2 bio.');
        $adminInactiveProfile->setFacebook('admin_facebook_2');
        $adminInactiveProfile->setInstagram('admin_instagram_2');
        $adminInactiveProfile->setX('admin_x_2');
        $adminInactiveProfile->setCreatedAt(new DateTimeImmutable());
        $adminInactiveProfile->setType('admin');
        $adminInactiveProfile->setActiveProfile(false);
        $adminInactiveProfile->setStatus('offline');

        $manager->persist($adminInactiveProfile);

        // Ajouter des utilisateurs avec deux profils chacun
        for ($i = 1; $i <= 20; ++$i) {
            // Profil actif
            $activeProfile = new Profile();
            $activeProfile->setUser($this->getReference('user_' . $i, User::class));
            $activeProfile->setRole('ROLE_USER');
            $activeProfile->setFirstName('FirstName' . $i . '_1');
            $activeProfile->setLastName('LastName' . $i . '_1');
            $activeProfile->setUsername('username' . $i . '_1');
            $activeProfile->setBirthday(new DateTime('1990-01-01'));
            $activeProfile->setGender('Masculin');
            $activeProfile->setPhoneNumber('123456789' . $i . '1');
            $activeProfile->setBio('A brief bio about user ' . $i . ' (profile 1)');
            $activeProfile->setFacebook('facebook' . $i . '_1');
            $activeProfile->setInstagram('instagram' . $i . '_1');
            $activeProfile->setX('x' . $i . '_1');
            $activeProfile->setCreatedAt(new DateTimeImmutable());
            $activeProfile->setType('lecteur');
            $activeProfile->setActiveProfile(true);
            $activeProfile->setStatus('offline');

            $manager->persist($activeProfile);

            // Profil inactif
            $inactiveProfile = new Profile();
            $inactiveProfile->setUser($this->getReference('user_' . $i, User::class));
            $inactiveProfile->setRole('ROLE_USER');
            $inactiveProfile->setFirstName('FirstName' . $i . '_2');
            $inactiveProfile->setLastName('LastName' . $i . '_2');
            $inactiveProfile->setUsername('username' . $i . '_2');
            $inactiveProfile->setBirthday(new DateTime('1990-01-01'));
            $inactiveProfile->setGender('Masculin');
            $inactiveProfile->setPhoneNumber('123456789' . $i . '2');
            $inactiveProfile->setBio('A brief bio about user ' . $i . ' (profile 2)');
            $inactiveProfile->setFacebook('facebook' . $i . '_2');
            $inactiveProfile->setInstagram('instagram' . $i . '_2');
            $inactiveProfile->setX('x' . $i . '_2');
            $inactiveProfile->setCreatedAt(new DateTimeImmutable());
            $inactiveProfile->setType('lecteur');
            $inactiveProfile->setActiveProfile(false);
            $inactiveProfile->setStatus('offline');

            $manager->persist($inactiveProfile);
            $this->addReference('profile_entity_' . $i . '_active', $activeProfile);
            $this->addReference('profile_entity_' . $i . '_inactive', $inactiveProfile);
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
