<?php

namespace App\DataFixtures;

use App\Entity\Badge;
use App\Entity\ChallengeProfile;
use App\Enum\ChallengeTypeEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker\Factory;


class BadgeFixtures extends Fixture implements DependentFixtureInterface
{

    public function load(ObjectManager $manager): void
    {
        // on récupère tous les challengeProfiles liés au Profile 1
        $challengeProfiles = $manager
            ->getRepository(ChallengeProfile::class)
            ->findBy(['profile' => 1]);

        foreach ($challengeProfiles as $challengeProfile) {
            $badge = new Badge();
            $badge->setChallengeProfile($challengeProfile)
                ->setAwardedAt(new \DateTimeImmutable())
                ->setStyle(null);

            $manager->persist($badge);
        }

        $manager->flush();
    }

    /**
     * Cette fixture dépend de ChallengeProfileFixtures
     *
     * @return array<int, class-string>
     */
    public function getDependencies(): array
    {
        return [
            ChallengeProfileFixtures::class,
        ];
    }
}