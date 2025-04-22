<?php

namespace App\DataFixtures;

use App\Entity\Group;
use App\Entity\GroupProfile;
use App\Entity\Post;
use App\Entity\Profile;
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

        $publicGroup = new Group($profiles[0]);
        $publicGroup->setName('Fil d’actualité');
        $publicGroup->setSlug($this->slugger->slug('Fil d’actualité')->lower());
        $publicGroup->setVisibility('public');
        $publicGroup->setDescription("Ce groupe est le fil d'actualité principal. Tous les membres peuvent y publier leurs réflexions, annonces ou critiques littéraires. Il représente la place publique de la plateforme.");

        $manager->persist($publicGroup);
        $manager->flush();
        $this->addMembersToGroup($manager, $publicGroup, $profiles);
        $this->addPostsToGroup($manager, $publicGroup, $profiles, 3, 6);

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

        foreach ($privateGroups as $groupName) {
            $creator = $profiles[array_rand($profiles)];

            $group = new Group($creator);
            $group->setName($groupName);
            $group->setSlug($this->slugger->slug($groupName)->lower());
            $group->setVisibility('private');
            $group->setDescription("Bienvenue dans le groupe \"$groupName\". Ici, les membres échangent autour de leurs passions communes avec bienveillance et respect. Rejoignez les discussions et découvrez des contenus exclusifs !");

            $group->setRules([
                'Respect mutuel entre membres',
                'Pas de spoilers sans avertissement',
                'Interdiction de contenus offensants ou discriminatoires',
                'Pas de promotion personnelle ou publicitaire',
                'Participation régulière aux discussions',
            ]);

            $group->setActivities([
                'Échanges autour des lectures récentes',
                'Organisation de lectures communes',
                'Défis littéraires mensuels',
                'Partage de critiques et recommandations',
                'Rencontres virtuelles entre membres',
            ]);

            $group->setWhoCanJoin('Uniquement sur invitation d’un membre ou après approbation d’un modérateur');
            $group->setExternalLinks([
                'https://example.com/regles-groupe-' . strtolower(str_replace(' ', '-', $groupName)),
                'https://example.com/evenements-groupe-' . strtolower(str_replace(' ', '-', $groupName)),
            ]);

            $manager->persist($group);
            $manager->flush();

            $this->addMembersToGroup($manager, $group, $profiles);
            $this->addPostsToGroup($manager, $group, $profiles);
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

    private function addPostsToGroup(ObjectManager $manager, Group $group, array $profiles, int $min = 1, int $max = 3): void
    {
        $titles = [
            'Bienvenue dans le groupe !',
            'Nos recommandations de lecture',
            'Derniers avis sur les livres',
            'Nouveau challenge littéraire',
            "Discussion autour d'un auteur",
        ];

        foreach ($profiles as $member) {
            $randomPostCount = rand($min, $max);

            for ($i = 0; $i < $randomPostCount; ++$i) {
                $title = $titles[array_rand($titles)];
                $post = new Post();
                $post->setAuthor($member);
                $post->setGroup($group);
                $post->setTitle($title);
                $post->setContent('Ceci est un message dans le groupe **' . $group->getName() . '**.');
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
            ResetAutoIncrementFixtures::class,
        ];
    }
}