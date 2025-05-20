<?php

namespace App\DataFixtures;

use App\Entity\Badge;
use App\Entity\Challenge;
use App\Entity\ChallengeProfile;
use App\Entity\JoinableChallenge;
use App\Entity\Profile;
use App\Enum\ChallengeActionTypeEnum;
use App\Enum\ChallengeContentTypeEnum;
use App\Enum\ChallengeFrequencyEnum;
use App\Enum\ChallengeProfileRoleEnum;
use App\Enum\ChallengeProfileStatusEnum;
use App\Enum\ChallengeStatusEnum;
use App\Enum\ChallengeTypeEnum;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ChallengeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $profiles = $manager->getRepository(Profile::class)->findAll();

        if (empty($profiles)) {
            throw new \RuntimeException('Profiles must be loaded before challenges');
        }

        // Create badges
        $badges = $this->createBadges($manager);

        // Create predefined challenges
        $this->createPredefinedChallenges($manager, $profiles, $badges, $faker);

        // Create community challenges
        $this->createCommunityChallenges($manager, $profiles, $badges, $faker);

        // Create customised challenges
        $this->createCustomisedChallenges($manager, $profiles, $badges, $faker);

        // Create special challenges
        $this->createSpecialChallenges($manager, $profiles, $badges, $faker);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProfileFixtures::class,
        ];
    }

    private function createBadges(ObjectManager $manager): array
    {
        $badges = [];

        $badgeData = [
            [ChallengeTypeEnum::PREDEFINED, 'Novice Reader', 'badges/novice_reader.png', ['level' => 1]],
            [ChallengeTypeEnum::PREDEFINED, 'Expert Reader', 'badges/expert_reader.png', ['level' => 5]],
            [ChallengeTypeEnum::COMMUNITY, 'Book Club Leader', 'badges/book_club.png', ['type' => 'social']],
            [ChallengeTypeEnum::COMMUNITY, 'Community Star', 'badges/community_star.png', ['type' => 'engagement']],
            [ChallengeTypeEnum::CUSTOMISED, 'Goal Setter', 'badges/goal_setter.png', ['category' => 'personal']],
            [ChallengeTypeEnum::SPECIAL, 'Special Achievement', 'badges/special.png', ['type' => 'exclusive', 'limited' => true]],
        ];

        foreach ($badgeData as $index => $data) {
            $badge = new Badge($data[0], $data[1], $data[2], $data[3]);
            if ($index % 2 === 0) {
                $badge->setShowcase(true);
            }
            $manager->persist($badge);
            $badges[] = $badge;
        }

        return $badges;
    }

    private function createPredefinedChallenges(ObjectManager $manager, array $profiles, array $badges, $faker): void
    {
        $challengeData = [
            [
                'name' => '30-Day Reading Challenge',
                'status' => ChallengeStatusEnum::IN_PROGRESS,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::PAGE,
                'quantity' => 50,
            ],
            [
                'name' => 'Summer Reading List',
                'status' => ChallengeStatusEnum::PENDING,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 10,
            ],
            [
                'name' => 'Classic Literature Marathon',
                'status' => ChallengeStatusEnum::COMPLETED,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 5,
            ],
            [
                'name' => 'Daily Chapter Challenge',
                'status' => ChallengeStatusEnum::IN_PROGRESS,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::CHAPTER,
                'quantity' => 30,
            ],
            [
                'name' => 'Science Fiction Explorer',
                'status' => ChallengeStatusEnum::PENDING,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 8,
            ],
            [
                'name' => 'Monthly Book Review',
                'status' => ChallengeStatusEnum::IN_PROGRESS,
                'actionType' => ChallengeActionTypeEnum::WRITE,
                'contentType' => ChallengeContentTypeEnum::BOOK_REVIEW,
                'quantity' => 3,
            ],
            [
                'name' => 'Build Your Library',
                'status' => ChallengeStatusEnum::PENDING,
                'actionType' => ChallengeActionTypeEnum::HAVE,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 20,
            ],
        ];

        foreach ($challengeData as $index => $data) {
            $challenge = new JoinableChallenge();
            $challenge->setName($data['name'])
                ->setType(ChallengeTypeEnum::PREDEFINED)
                ->setStatus($data['status'])
                ->setActionType($data['actionType'])
                ->setContentType($data['contentType'])
                ->setQuantity($data['quantity'])
                ->setFrequency(ChallengeFrequencyEnum::ONCE);

            // Assign a badge to some challenges
            if ($index % 2 === 0 && !empty($badges)) {
                $badge = $badges[$index % count($badges)];
                $challenge->setBadge($badge);
            }

            $manager->persist($challenge);

            // Add profiles to challenge
            $this->addProfilesToChallenge($manager, $challenge, $profiles, $faker, 3, 8);
        }
    }

    private function createCommunityChallenges(ObjectManager $manager, array $profiles, array $badges, $faker): void
    {
        $challengeData = [
            [
                'name' => 'Book Club Challenge',
                'status' => ChallengeStatusEnum::IN_PROGRESS,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 4,
            ],
            [
                'name' => 'Author Spotlight: Jane Austen',
                'status' => ChallengeStatusEnum::PENDING,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 6,
            ],
            [
                'name' => 'Fantasy Book Marathon',
                'status' => ChallengeStatusEnum::IN_PROGRESS,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 12,
            ],
            [
                'name' => 'Reading Group Discussion',
                'status' => ChallengeStatusEnum::IN_PROGRESS,
                'actionType' => ChallengeActionTypeEnum::WRITE,
                'contentType' => ChallengeContentTypeEnum::BOOK_REVIEW,
                'quantity' => 5,
            ],
            [
                'name' => 'Mystery Novel Club',
                'status' => ChallengeStatusEnum::COMPLETED,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 8,
            ],
            [
                'name' => 'Literary Critics Circle',
                'status' => ChallengeStatusEnum::IN_PROGRESS,
                'actionType' => ChallengeActionTypeEnum::WRITE,
                'contentType' => ChallengeContentTypeEnum::BOOK_REVIEW,
                'quantity' => 10,
            ],
            [
                'name' => 'Audiobook Listeners Group',
                'status' => ChallengeStatusEnum::PENDING,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 6,
            ],
            [
                'name' => 'Contemporary Fiction Club',
                'status' => ChallengeStatusEnum::IN_PROGRESS,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 10,
            ],
        ];

        foreach ($challengeData as $index => $data) {
            $challenge = new JoinableChallenge();
            $challenge->setName($data['name'])
                ->setType(ChallengeTypeEnum::COMMUNITY)
                ->setStatus($data['status'])
                ->setActionType($data['actionType'])
                ->setContentType($data['contentType'])
                ->setQuantity($data['quantity'])
                ->setFrequency(ChallengeFrequencyEnum::MONTHLY);

            // Assign a badge to some challenges
            if ($index % 3 === 0 && !empty($badges)) {
                $badge = $badges[$index % count($badges)];
                $challenge->setBadge($badge);
            }

            $manager->persist($challenge);

            // Add profiles to challenge
            $this->addProfilesToChallenge($manager, $challenge, $profiles, $faker, 5, 15);
        }
    }

    private function createCustomisedChallenges(ObjectManager $manager, array $profiles, array $badges, $faker): void
    {
        $challengeData = [
            [
                'name' => 'My Personal Reading Goal',
                'status' => ChallengeStatusEnum::IN_PROGRESS,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::PAGE,
                'quantity' => 100,
            ],
            [
                'name' => 'Write a Novel in 30 Days',
                'status' => ChallengeStatusEnum::PENDING,
                'actionType' => ChallengeActionTypeEnum::WRITE,
                'contentType' => ChallengeContentTypeEnum::CHAPTER,
                'quantity' => 30,
            ],
            [
                'name' => 'Historical Fiction Reading Plan',
                'status' => ChallengeStatusEnum::IN_PROGRESS,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 6,
            ],
            [
                'name' => 'Self-Help Book Journey',
                'status' => ChallengeStatusEnum::COMPLETED,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 4,
            ],
            [
                'name' => 'Book Collecting Challenge',
                'status' => ChallengeStatusEnum::IN_PROGRESS,
                'actionType' => ChallengeActionTypeEnum::HAVE,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 30,
            ],
            [
                'name' => 'Poetry Reading Month',
                'status' => ChallengeStatusEnum::PENDING,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 5,
            ],
            [
                'name' => 'Weekly Article Review',
                'status' => ChallengeStatusEnum::IN_PROGRESS,
                'actionType' => ChallengeActionTypeEnum::WRITE,
                'contentType' => ChallengeContentTypeEnum::ARTICLE,
                'quantity' => 12,
            ],
        ];

        foreach ($challengeData as $index => $data) {
            $challenge = new JoinableChallenge();
            $challenge->setName($data['name'])
                ->setType(ChallengeTypeEnum::CUSTOMISED)
                ->setStatus($data['status'])
                ->setActionType($data['actionType'])
                ->setContentType($data['contentType'])
                ->setQuantity($data['quantity'])
                ->setFrequency(
                    $faker->randomElement([
                        ChallengeFrequencyEnum::DAILY,
                        ChallengeFrequencyEnum::WEEKLY,
                        ChallengeFrequencyEnum::MONTHLY,
                        ChallengeFrequencyEnum::YEARLY,
                        ChallengeFrequencyEnum::ONCE,
                    ])
                );

            // Assign a badge to some challenges
            if ($index % 2 === 1 && !empty($badges)) {
                $badge = $badges[$index % count($badges)];
                $challenge->setBadge($badge);
            }

            $manager->persist($challenge);

            // Add profiles to challenge
            $this->addProfilesToChallenge($manager, $challenge, $profiles, $faker, 1, 3);
        }
    }

    private function createSpecialChallenges(ObjectManager $manager, array $profiles, array $badges, $faker): void
    {
        $challengeData = [
            [
                'name' => 'Reading Olympics 2025',
                'status' => ChallengeStatusEnum::PENDING,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 25,
            ],
            [
                'name' => 'World Book Day Challenge',
                'status' => ChallengeStatusEnum::COMPLETED,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::PAGE,
                'quantity' => 250,
            ],
            [
                'name' => 'Banned Books Awareness',
                'status' => ChallengeStatusEnum::IN_PROGRESS,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 10,
            ],
            [
                'name' => 'Holiday Reading Spree',
                'status' => ChallengeStatusEnum::PENDING,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 3,
            ],
            [
                'name' => 'Diverse Authors Challenge',
                'status' => ChallengeStatusEnum::IN_PROGRESS,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 12,
            ],
            [
                'name' => 'Literary Award Winners',
                'status' => ChallengeStatusEnum::IN_PROGRESS,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 8,
            ],
            [
                'name' => 'Blind Date with a Book',
                'status' => ChallengeStatusEnum::PENDING,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 5,
            ],
            [
                'name' => 'Read Around the World',
                'status' => ChallengeStatusEnum::IN_PROGRESS,
                'actionType' => ChallengeActionTypeEnum::READ,
                'contentType' => ChallengeContentTypeEnum::BOOK,
                'quantity' => 20,
            ],
        ];

        foreach ($challengeData as $index => $data) {
            $challenge = new JoinableChallenge();
            $challenge->setName($data['name'])
                ->setType(ChallengeTypeEnum::SPECIAL)
                ->setStatus($data['status'])
                ->setActionType($data['actionType'])
                ->setContentType($data['contentType'])
                ->setQuantity($data['quantity'])
                ->setFrequency(
                    $faker->randomElement([
                        ChallengeFrequencyEnum::YEARLY,
                        ChallengeFrequencyEnum::ONCE,
                    ])
                );

            // Assign a badge to special challenges
            if (!empty($badges)) {
                $badge = $badges[$index % count($badges)];
                $challenge->setBadge($badge);
            }

            $manager->persist($challenge);

            // Add profiles to challenge
            $this->addProfilesToChallenge($manager, $challenge, $profiles, $faker, 10, 30);
        }
    }

    private function addProfilesToChallenge(
        ObjectManager $manager,
        Challenge $challenge,
        array $profiles,
        $faker,
        int $minProfiles,
        int $maxProfiles
    ): void {
        $numProfiles = $faker->numberBetween($minProfiles, min($maxProfiles, count($profiles)));
        $selectedProfiles = $faker->randomElements($profiles, $numProfiles);

        // First profile is always the creator
        $creatorProfile = array_shift($selectedProfiles);
        $creatorChallengeProfile = new ChallengeProfile();
        $creatorChallengeProfile->setChallenge($challenge)
            ->setProfile($creatorProfile)
            ->setRole(ChallengeProfileRoleEnum::CREATOR)
            ->setStatus(ChallengeProfileStatusEnum::ACCEPTED)
            ->setJoinedAt(
                (new DateTimeImmutable())->modify('-' . $faker->numberBetween(30, 90) . ' days')
            );

        $manager->persist($creatorChallengeProfile);

        // The rest of the profiles are members
        foreach ($selectedProfiles as $profile) {
            $status = $faker->randomElement([
                ChallengeProfileStatusEnum::INVITED,
                ChallengeProfileStatusEnum::PENDING,
                ChallengeProfileStatusEnum::ACCEPTED,
                ChallengeProfileStatusEnum::REJECTED,
                ChallengeProfileStatusEnum::SUCCEEDED,
                ChallengeProfileStatusEnum::FAILED,
            ]);

            $challengeProfile = new ChallengeProfile();
            $challengeProfile->setChallenge($challenge)
                ->setProfile($profile)
                ->setRole(ChallengeProfileRoleEnum::MEMBER)
                ->setStatus($status)
                ->setJoinedAt(
                    (new DateTimeImmutable())->modify('-' . $faker->numberBetween(1, 30) . ' days')
                );

            // Set completedAt for challenges that are completed
            if ($status === ChallengeProfileStatusEnum::SUCCEEDED || $status === ChallengeProfileStatusEnum::FAILED) {
                $challengeProfile->setCompletedAt(
                    (new DateTimeImmutable())->modify('-' . $faker->numberBetween(1, 7) . ' days')
                );
            }

            $manager->persist($challengeProfile);
        }
    }
}
