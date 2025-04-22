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

        $adminProfile = $manager->getRepository(Profile::class)->findOneBy(['username' => 'admin_username_1']);
        if (!$adminProfile) {
            throw new Exception('Le profil avec le username "admin_username_1" n\'a pas été trouvé.');
        }

        $article = new Post();
        $article->setTitle('Bienvenue sur notre blog !');
        $article->setType('article');
        $article->setContent('Nous sommes ravis de vous accueillir sur notre blog. N’hésitez pas à partager vos lectures et à discuter avec la communauté !');
        $article->setCreatedAt(new DateTimeImmutable());
        $article->setVisibility('public');
        $article->setAuthor($adminProfile);
        $article->setSlug($slugger->slug('bienvenue-sur-notre-blog')->lower());
        $manager->persist($article);

        $article1 = new Post();
        $article1->setTitle('Les bases de Symfony pour débutants');
        $article1->setType('article');
        $article1->setContent('Symfony est un puissant framework PHP permettant de développer des applications web robustes. Découvrez dans cet article les bases essentielles pour bien démarrer !');
        $article1->setCreatedAt(new DateTimeImmutable());
        $article1->setVisibility('public');
        $article1->setStatus('brouillon');
        $article1->setAuthor($adminProfile);
        $article1->setSlug($slugger->slug('les-bases-de-symfony-pour-debutants')->lower());
        $manager->persist($article1);

        $article2 = new Post();
        $article2->setTitle('Pourquoi utiliser Doctrine avec Symfony ?');
        $article2->setType('article');
        $article2->setContent("Doctrine est l'ORM intégré à Symfony qui facilite la gestion des bases de données. Apprenez pourquoi et comment l'utiliser efficacement dans vos projets Symfony.");
        $article2->setCreatedAt(new DateTimeImmutable());
        $article2->setVisibility('public');
        $article2->setStatus('brouillon');
        $article2->setAuthor($adminProfile);
        $article2->setSlug($slugger->slug('pourquoi-utiliser-doctrine-avec-symfony')->lower());
        $manager->persist($article2);

        $article3 = new Post();
        $article3->setTitle('Créer une API REST avec Symfony');
        $article3->setType('article');
        $article3->setContent('Dans cet article, nous vous guidons étape par étape pour créer une API REST performante avec Symfony et API Platform.');
        $article3->setCreatedAt(new DateTimeImmutable());
        $article3->setVisibility('public');
        $article3->setStatus('brouillon');
        $article3->setAuthor($adminProfile);
        $article3->setSlug($slugger->slug('creer-une-api-rest-avec-symfony')->lower());
        $manager->persist($article3);

        $article4 = new Post();
        $article4->setTitle('Optimiser les performances de votre application Symfony');
        $article4->setType('article');
        $article4->setContent('Découvrez les meilleures pratiques pour améliorer les performances de votre application Symfony et réduire les temps de chargement.');
        $article4->setCreatedAt(new DateTimeImmutable());
        $article4->setVisibility('public');
        $article4->setStatus('brouillon');
        $article4->setAuthor($adminProfile);
        $article4->setSlug($slugger->slug('optimiser-les-performances-de-votre-application-symfony')->lower());
        $manager->persist($article4);

        $article5 = new Post();
        $article5->setTitle('Les nouveautés de Symfony 6');
        $article5->setType('article');
        $article5->setContent("Symfony 6 apporte de nombreuses améliorations et nouvelles fonctionnalités. Voici un tour d'horizon des nouveautés à ne pas manquer !");
        $article5->setCreatedAt(new DateTimeImmutable());
        $article5->setVisibility('public');
        $article5->setStatus('brouillon');
        $article5->setAuthor($adminProfile);
        $article5->setSlug($slugger->slug('les-nouveautes-de-symfony-6')->lower());
        $manager->persist($article5);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProfileFixtures::class,
            ResetAutoIncrementFixtures::class,
        ];
    }
}
