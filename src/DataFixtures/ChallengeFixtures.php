<?php

namespace App\DataFixtures;

use App\Entity\Challenge;
use App\Entity\ChallengeProfile;
use App\Enum\ChallengeTypeEnum;
use App\Enum\ChallengeStatusEnum;
use App\Enum\ChallengeActionTypeEnum;
use App\Enum\ChallengeContentTypeEnum;
use App\Enum\ChallengeFrequencyEnum;
use App\ValueObject\ChallengeConstraint;
use App\Entity\Profile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ChallengeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');

        // Récupère la référence du profil admin actif
        try {
            /** @var Profile $creator */
            $creator = $this->getReference('admin_active_profile', Profile::class);

        } catch (\Exception $e) {
            throw new \Exception('Impossible de récupérer le profil admin : ' . $e->getMessage());
        }

        for ($i = 0; $i < 20; $i++) {
            // Type et statut aléatoires
            $type   = $faker->randomElement(ChallengeTypeEnum::cases());
            $status = $faker->randomElement(ChallengeStatusEnum::cases());

            // Dates de début et fin
            $startAt = \DateTimeImmutable::createFromMutable(
                $faker->dateTimeBetween('-30 days', 'now')
            );
            $endAt = \DateTimeImmutable::createFromMutable(
                $faker->dateTimeBetween('now', '+30 days')
            );

            // Contrainte aléatoire en respectant la logique action/content
            $action = $faker->randomElement(ChallengeActionTypeEnum::cases());

            // Sélectionner le type de contenu en fonction de l'action
            $content = $this->getRandomContentForAction($action, $faker);

            $frequency = $faker->randomElement(ChallengeFrequencyEnum::cases());
            $targetCount = $faker->numberBetween(1, 10);

            $constraint = new ChallengeConstraint(
                $action,
                $content,
                $frequency,
                $targetCount
            );

            // Création du challenge avec le constructeur requis
            $challenge = new Challenge($creator, $constraint);
            $challenge
                ->setName(ucfirst($action->value) . ' ' . $faker->word())
                ->setType($type)
                ->setStatus($status)
                ->setStartAt($startAt)
                ->setEndAt($endAt)
                ->setConstraint($constraint);

            $manager->persist($challenge);

            // Création du lien ChallengeProfile pour le créateur avec le rôle admin
            $challengeProfile = new ChallengeProfile($challenge, $creator, 'admin');
            $challengeProfile->setProgress(0);

            $manager->persist($challengeProfile);
        }

        $manager->flush();
    }

    /**
     * Retourne un type de contenu aléatoire en fonction du type d'action
     */
    private function getRandomContentForAction(ChallengeActionTypeEnum $action, \Faker\Generator $faker): ChallengeContentTypeEnum
    {
        switch ($action) {
            case ChallengeActionTypeEnum::WRITE:
                return $faker->randomElement([
                    ChallengeContentTypeEnum::ARTICLE,
                    ChallengeContentTypeEnum::BOOK_REVIEW,
                    ChallengeContentTypeEnum::BOOK_DESCRIPTION,
                ]);

            case ChallengeActionTypeEnum::READ:
                return $faker->randomElement([
                    ChallengeContentTypeEnum::BOOK,
                    ChallengeContentTypeEnum::PAGE,
                    ChallengeContentTypeEnum::CHAPTER,
                ]);

            case ChallengeActionTypeEnum::HAVE:
                return $faker->randomElement([
                    ChallengeContentTypeEnum::BOOK,
                    ChallengeContentTypeEnum::BOOK_DESCRIPTION,
                ]);

            default:
                throw new \InvalidArgumentException('Type d\'action non pris en charge: ' . $action->name);
        }
    }

    public function getDependencies(): array
    {
        return [ProfileFixtures::class];
    }
}