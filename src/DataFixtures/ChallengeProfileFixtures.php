<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\ChallengeProfile;
use App\Entity\Profile;
use App\Entity\Challenge;
use App\Enum\ChallengeStatusEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ChallengeProfileFixtures extends Fixture implements DependentFixtureInterface
{
    public const REFERENCE_CHALLENGE_PROFILE_1 = 'challenge-profile-1';

    /**
     * Charge les fixtures dans la base de données.
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        // Récupérer le profile cible
        $profile = $manager->getRepository(Profile::class)->find(1);
        if (!$profile instanceof Profile) {
            throw new \RuntimeException('Profile with id 1 not found.');
        }

        $challenges = $manager->getRepository(Challenge::class)->findAll();
        if (empty($challenges)) {
            throw new \RuntimeException('No Challenge entities found.');
        }

        foreach ($challenges as $index => $challenge) {
            $challengeProfile = new ChallengeProfile();
            $challengeProfile
                ->setProfile($profile)
                ->setChallenge($challenge)
                ->setProgress([])
                ->setStatus(ChallengeStatusEnum::PENDING);

            $manager->persist($challengeProfile);

            if ($index === 0) {
                $this->addReference(self::REFERENCE_CHALLENGE_PROFILE_1, $challengeProfile);
            }
        }

        $manager->flush();
    }

    /**
     * Dépend des fixtures ProfileFixtures et ChallengeFixtures
     *
     * @return array<int, class-string>
     */
    public function getDependencies(): array
    {
        return [
            ProfileFixtures::class,
            ChallengeFixtures::class,
        ];
    }
}
