<?php

namespace App\DataFixtures;

use App\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class TagFixtures extends Fixture
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void
    {
        $tagsData = [
            [
                'name' => 'Fantasy',
                'slug' => 'fantasy',
                'children' => ['Dark Fantasy', 'Heroic Fantasy', 'Urban Fantasy', 'High Fantasy', 'Low Fantasy', 'Medieval Fantasy'],
            ],
            [
                'name' => 'Science-Fiction',
                'slug' => 'science-fiction',
                'children' => ['Cyberpunk', 'Space Opera', 'Dystopie', 'Hard Science', 'Steampunk', 'Biopunk'],
            ],
            [
                'name' => 'Poésie',
                'slug' => 'poesie',
                'children' => ['Haïku', 'Sonnet', 'Épique', 'Slam', 'Lyrisme', 'Calligramme'],
            ],
            [
                'name' => 'Roman Historique',
                'slug' => 'roman-historique',
                'children' => ['Médiéval', 'Guerres Mondiales', 'Renaissance', 'Antiquité', 'Époque Victorienne'],
            ],
            [
                'name' => 'Thriller',
                'slug' => 'thriller',
                'children' => ['Psychologique', 'Espionnage', 'Juridique', 'Médical', 'Politique', 'Technologique'],
            ],
            [
                'name' => 'Horreur',
                'slug' => 'horreur',
                'children' => ['Gothique', 'Lovecraftien', 'Slasher', 'Occulte', 'Zombie'],
            ],
            [
                'name' => 'Romance',
                'slug' => 'romance',
                'children' => ['Contemporaine', 'Historique', 'Paranormale', 'Érotique', 'Young Adult', 'Comédie Romantique'],
            ],
            [
                'name' => 'Littérature Contemporaine',
                'slug' => 'litterature-contemporaine',
                'children' => ['Narrative', 'Minimaliste', 'Drame', 'Réalisme Magique', 'Autofiction'],
            ],
            [
                'name' => 'Bande Dessinée & Manga',
                'slug' => 'bd-manga',
                'children' => ['Shonen', 'Shojo', 'Seinen', 'Comics', 'Webtoon', 'Franco-Belge'],
            ],
            [
                'name' => 'Essai & Philosophie',
                'slug' => 'essai-philosophie',
                'children' => ['Société', 'Économie', 'Spiritualité', 'Sciences Humaines'],
            ],
            [
                'name' => 'Développement Personnel',
                'slug' => 'developpement-personnel',
                'children' => ['Psychologie', 'Bien-être', 'Productivité', 'Coaching', 'Motivation'],
            ],
            [
                'name' => 'Aventure',
                'slug' => 'aventure',
                'children' => ['Exploration', 'Voyage', 'Survie', 'Périple initiatique'],
            ],
            [
                'name' => 'Littérature Jeunesse',
                'slug' => 'litterature-jeunesse',
                'children' => ['Albums', 'Contes', 'Fantasy Jeunesse', 'Éducatif'],
            ],
            [
                'name' => 'Biographie & Mémoires',
                'slug' => 'biographie-memoires',
                'children' => ['Scientifique', 'Autobiographie', 'Témoignage'],
            ],
            [
                'name' => 'Policier & Enquête',
                'slug' => 'policier-enquete',
                'children' => ['Noir', 'Hardboiled', 'Whodunit', 'Cold Case', 'True Crime'],
            ],
            [
                'name' => 'Théâtre',
                'slug' => 'theatre',
                'children' => ['Classique', 'Moderne', 'Tragédie', 'Comédie', 'Absurdiste'],
            ],
            [
                'name' => 'Science & Technologie',
                'slug' => 'science-technologie',
                'children' => ['Astronomie', 'Physique', 'Intelligence Artificielle', 'Biologie', 'Mathématiques'],
            ],
            [
                'name' => 'Spiritualité & Religion',
                'slug' => 'spiritualite-religion',
                'children' => ['Bouddhisme', 'Christianisme', 'Islam', 'Mysticisme', 'Éveil personnel'],
            ],
        ];

        foreach ($tagsData as $tagInfo) {
            $parentTag = $this->createTag($manager, $tagInfo['name'], $tagInfo['slug'], null);

            foreach ($tagInfo['children'] as $childName) {
                $childSlug = $this->slugger->slug($childName)->lower();
                $this->createTag($manager, $childName, $childSlug, $parentTag);
            }
        }

        $manager->flush();
    }

    private function createTag(ObjectManager $manager, string $name, string $slug, ?Tag $parent): Tag
    {
        $existingTag = $manager->getRepository(Tag::class)->findOneBy(['name' => $name]);

        if ($existingTag) {
            return $existingTag;
        }

        $tag = new Tag($name, $parent);

        $manager->persist($tag);

        return $tag;
    }
}
