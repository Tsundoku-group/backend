<?php

namespace App\DataFixtures;

use App\Entity\Group;
use App\Entity\Profile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class GroupFixtures extends Fixture implements DependentFixtureInterface
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void
    {
        $profile = $manager->getRepository(Profile::class)->findOneBy([]);

        if (!$profile) {
            throw new \RuntimeException('Aucun profil trouvé. Ajoutez des profils avant de charger les groupes.');
        }

        $existingPublicGroup = $manager->getRepository(Group::class)->findOneBy(['visibility' => 'public']);

        if (!$existingPublicGroup) {
            $publicGroup = new Group($profile);
            $publicGroup->setName('Fil d’actualité');
            $publicGroup->setSlug($this->slugger->slug('Fil d’actualité')->lower());
            $publicGroup->setVisibility('public');

            $manager->persist($publicGroup);
        }

        $privateGroups = [
            'Club des lecteurs',
            'Amis du fantasy',
            'Groupe de discussion'
        ];

        foreach ($privateGroups as $groupName) {
            $group = new Group($profile);
            $group->setName($groupName);
            $group->setSlug($this->slugger->slug($groupName)->lower());
            $group->setVisibility('private');

            $manager->persist($group);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProfileFixtures::class,
        ];
    }
}