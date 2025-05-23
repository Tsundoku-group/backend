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
    private const BADGE_COUNT = 20;

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
                ->setForChallengeType($this->getRandomChallengeType())
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

    /**
     * Crée un tableau d'entités Badge.
     *
     * @param ChallengeProfile $challengeProfile
     * @return Badge[]
     */
    private function createBadges(ChallengeProfile $challengeProfile): array
    {
        $faker = Factory::create();
        $badges = [];

        for ($i = 0; $i < self::BADGE_COUNT; $i++) {
            $badges[] = $this->buildBadge($challengeProfile, $faker);
        }

        return $badges;
    }

    /**
     * Construit une entité Badge.
     *
     * @param ChallengeProfile $challengeProfile
     * @param \Faker\Generator $faker
     * @return Badge
     */
    private function buildBadge(ChallengeProfile $challengeProfile, \Faker\Generator $faker): Badge
    {
        $badge = new Badge();
        $badge->setChallengeProfile($challengeProfile)
            ->setAwardedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year', 'now')))
            ->setForChallengeType($this->getRandomChallengeType())
            ->setStyle(null);

        return $badge;
    }

    /**
     * Retourne un type de challenge aléatoire.
     *
     * @return ChallengeTypeEnum
     */
    private function getRandomChallengeType(): ChallengeTypeEnum
    {
        $types = ChallengeTypeEnum::cases();

        return $types[array_rand($types)];
    }
}