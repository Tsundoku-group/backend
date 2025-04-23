<?php

namespace App\DataFixtures;

use App\Entity\Friendship;
use App\Entity\Profile;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class FriendshipFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $statuses = [
            Friendship::STATUS_PENDING,
            Friendship::STATUS_ACCEPTED,
            Friendship::STATUS_REJECTED,
        ];

        // Ajouter des relations pour les profils
        for ($i = 1; $i <= 20; ++$i) {
            for ($j = 1; $j <= 20; ++$j) {
                // Éviter de créer une relation entre le même profil
                if ($i === $j) {
                    continue;
                }

                // Récupérer des profils distincts pour chaque amitié
                $requester = $this->getReference('profile_entity_' . $i . '_active', Profile::class);
                $receiver = $this->getReference('profile_entity_' . $j . '_active', Profile::class);

                // Créer une nouvelle amitié
                $friendship = new Friendship();
                $friendship->setRequester($requester);
                $friendship->setReceiver($receiver);
                $friendship->setStatus($statuses[array_rand($statuses)]);
                $friendship->setCreatedAt(new DateTimeImmutable());

                if (Friendship::STATUS_ACCEPTED === $friendship->getStatus()) {
                    $friendship->setFriendAt(new DateTimeImmutable());
                }

                $manager->persist($friendship);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProfileFixtures::class,
            ResetAutoIncrementFixtures::class,
        ];
    }
}
