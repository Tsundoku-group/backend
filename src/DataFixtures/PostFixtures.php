<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\Profile;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\String\Slugger\AsciiSlugger;

class PostFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $profiles = $manager->getRepository(Profile::class)->findAll();
        if (empty($profiles)) {
            throw new Exception('Aucun profil trouvé. Assurez-vous d’avoir des utilisateurs dans la base.');
        }

        $titles = [
            'Mes lectures préférées 📖',
            'Quel livre recommandez-vous ?',
            'Discussion autour de la fantasy 🏰',
            'Les classiques de la littérature 📚',
            'Nouveau roman à découvrir !',
            'Petit sondage sur la science-fiction 🤖',
            'Vos avis sur ce best-seller ?',
            'À la recherche d’une nouvelle lecture 🔍',
            'Les meilleurs thrillers à lire !',
            'Comment trouvez-vous cet auteur ?',
        ];

        $contents = [
            'J’ai terminé un super livre et je voulais partager mon avis avec vous !',
            'Quelle est votre saga préférée ?',
            'Je cherche un bon roman de science-fiction, des recommandations ?',
            'Un petit débat : poche ou grand format ?',
            'Les adaptations de livres en films : pour ou contre ?',
            'Si vous ne deviez lire qu’un seul livre cette année, lequel choisiriez-vous ?',
            'Les meilleures citations de romans, partagez les vôtres !',
            'Les librairies indépendantes que vous adorez ❤️',
            'Les livres qui vous ont le plus marqué cette année',
            'Top 5 des livres à lire absolument en 2025 !',
        ];

        $slugger = new AsciiSlugger(); // Utilisé pour générer des slugs uniques

        for ($i = 0; $i < 100; ++$i) {
            $post = new Post();
            $title = $titles[$i % count($titles)];
            $post->setTitle($title);
            $post->setContent($contents[$i % count($contents)]);
            $post->setCreatedAt((new DateTimeImmutable())->modify("-$i days"));
            $post->setVisibility('public');
            $post->setAuthor($profiles[array_rand($profiles)]);

            // ✅ Générer un slug unique en ajoutant un ID au slug
            $slug = $slugger->slug($title . '-' . $i)->lower();
            $post->setSlug($slug);

            $manager->persist($post);
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
