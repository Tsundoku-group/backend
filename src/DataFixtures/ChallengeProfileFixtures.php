<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\ChallengeProfile;
use App\Entity\Profile;
use App\Entity\Challenge;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ChallengeProfileFixtures extends Fixture implements DependentFixtureInterface
{
    public const REFERENCE_CHALLENGE_PROFILE_1 = 'challenge-profile-1';

    /**
     * Charge les fixtures dans la base de données.
     */
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');

        // Récupérer un profile cible différent du créateur
        $profiles = $manager->getRepository(Profile::class)->findAll();
        if (count($profiles) < 2) {
            throw new \RuntimeException('Au moins 2 profils sont nécessaires pour créer des ChallengeProfile supplémentaires.');
        }

        $challenges = $manager->getRepository(Challenge::class)->findAll();
        if (empty($challenges)) {
            throw new \RuntimeException('Aucun Challenge trouvé. Assurez-vous que ChallengeFixtures s\'exécute avant.');
        }

        $participantsCreated = 0;

        foreach ($challenges as $challenge) {
            // Récupérer le créateur du challenge pour l'exclure des participants
            $creator = $challenge->getCreator();

            // Filtrer les profils pour exclure le créateur
            $availableProfiles = array_filter($profiles, function (Profile $profile) use ($creator) {
                return $profile->getId() !== $creator->getId();
            });

            if (empty($availableProfiles)) {
                continue;
            }

            // Ajouter 1 à 3 participants aléatoires à chaque challenge
            $numParticipants = $faker->numberBetween(1, min(3, count($availableProfiles)));
            $selectedProfiles = $faker->randomElements($availableProfiles, $numParticipants);

            foreach ($selectedProfiles as $profile) {
                $challengeProfile = new ChallengeProfile($challenge, $profile, 'participant');
                $challengeProfile->setProgress($faker->numberBetween(0, 5));

                $manager->persist($challengeProfile);

                // Créer une référence pour le premier ChallengeProfile participant
                if ($participantsCreated === 0) {
                    $this->addReference(self::REFERENCE_CHALLENGE_PROFILE_1, $challengeProfile);
                }

                $participantsCreated++;
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