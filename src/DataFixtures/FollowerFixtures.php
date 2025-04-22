<?php

namespace App\DataFixtures;

use App\Entity\Follower;
use App\Entity\Profile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;

class FollowerFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $connection = $manager->getConnection();
        $connection->executeStatement('ALTER SEQUENCE follower_id_seq RESTART WITH 1');

        $profiles = $manager->getRepository(Profile::class)->findAll();

        if (count($profiles) < 2) {
            throw new Exception('Il faut au moins deux profils pour créer des followers.');
        }

        $alreadyFollowed = [];

        foreach ($profiles as $follower) {
            $followingCount = 0;

            while ($followingCount < 5) {
                $randomProfile = $profiles[array_rand($profiles)];

                if ($follower === $randomProfile || isset($alreadyFollowed[$follower->getId()][$randomProfile->getId()])) {
                    continue;
                }

                $follow = new Follower();
                $follow->setFollower($follower);
                $follow->setFollowing($randomProfile);

                $manager->persist($follow);

                $alreadyFollowed[$follower->getId()][$randomProfile->getId()] = true;

                ++$followingCount;
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
