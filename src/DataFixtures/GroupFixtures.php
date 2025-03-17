<?php

namespace App\DataFixtures;

use App\Entity\Group;
use App\Entity\Profile;
use App\Entity\Post;
use App\Entity\GroupProfile;
use App\Entity\Tag;
use App\Entity\Taggable;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;
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
        $profiles = $manager->getRepository(Profile::class)->findAll();
        $tags = $manager->getRepository(Tag::class)->findAll();

        if (empty($profiles)) {
            throw new RuntimeException('Aucun profil trouvé. Ajoutez des profils avant de charger les groupes.');
        }

        $existingPublicGroup = $manager->getRepository(Group::class)->findOneBy(['visibility' => 'public']);

        if (!$existingPublicGroup) {
            $publicGroup = new Group($profiles[0]);
            $publicGroup->setName('Fil d’actualité');
            $publicGroup->setSlug($this->slugger->slug('Fil d’actualité')->lower());
            $publicGroup->setVisibility('public');
            $manager->persist($publicGroup);
        }

        $privateGroups = [
            'Club des lecteurs',
            'Amis du fantasy',
            'Groupe de discussion',
            'Passion science-fiction',
            'Auteurs indépendants',
            'Écrivains en herbe',
            'Thrillers et polars',
            'Romans historiques',
            'Club manga & anime',
            'Philosophie et essais',
        ];

        $groupEntities = [];

        foreach ($privateGroups as $groupName) {
            $creator = $profiles[array_rand($profiles)];

            $group = new Group($creator);
            $group->setName($groupName);
            $group->setSlug($this->slugger->slug($groupName)->lower());
            $group->setVisibility('private');

            $manager->persist($group);
            $groupEntities[] = $group;

            $this->addMembersToGroup($manager, $group, $profiles);
            $this->addPostsToGroup($manager, $group, $profiles);
        }

        $manager->flush(); 
        foreach ($groupEntities as $group) {
            $this->addTagsToGroup($manager, $group, $tags);
        }

        $manager->flush();
    }


    private function addMembersToGroup(ObjectManager $manager, Group $group, array $profiles): void
    {
        $members = array_slice($profiles, 0, rand(3, 6));

        foreach ($members as $member) {
            $groupProfile = new GroupProfile($group, $member, 'member');
            $manager->persist($groupProfile);
        }
    }

    private function addPostsToGroup(ObjectManager $manager, Group $group, array $profiles): void
    {
        $titles = [
            "Bienvenue dans le groupe !",
            "Nos recommandations de lecture",
            "Derniers avis sur les livres",
            "Nouveau challenge littéraire",
            "Discussion autour d'un auteur"
        ];

        foreach ($profiles as $member) {
            $randomPostCount = rand(1, 3);

            for ($i = 0; $i < $randomPostCount; $i++) {
                $title = $titles[array_rand($titles)];
                $post = new Post();
                $post->setAuthor($member);
                $post->setGroup($group);
                $post->setTitle($title);
                $post->setContent("Ceci est un message dans le groupe **" . $group->getName() . "**.");
                $post->setSlug($this->slugger->slug($title)->lower());
                $post->setVisibility($group->getVisibility());
                $post->setCreatedAt(new DateTimeImmutable('-' . rand(0, 30) . ' days'));

                $manager->persist($post);
            }
        }
    }

    private function addTagsToGroup(ObjectManager $manager, Group $group, array $tags): void
    {
        if (empty($tags)) {
            return;
        }

        $randomTags = array_slice($tags, 0, rand(1, 3));

        foreach ($randomTags as $tag) {
            $taggable = new Taggable($tag, 'group', $group->getId(), $group);
            $manager->persist($taggable);
        }
    }

    public function getDependencies(): array
    {
        return [
            ProfileFixtures::class,
        ];
    }
}