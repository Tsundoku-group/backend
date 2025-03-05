<?php

namespace App\Command;

use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-tags',
    description: 'Importe des tags depuis un fichier JSON.',
)]
class ImportTagsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = __DIR__ . '/../Data/tags.json';

        if (!file_exists($filePath)) {
            $io->error('Fichier tags.json introuvable.');
            return Command::FAILURE;
        }

        $jsonContent = file_get_contents($filePath);
        $tagsData = json_decode($jsonContent, true);

        if (null === $tagsData) {
            $io->error('Le fichier JSON est mal formaté.');
            return Command::FAILURE;
        }

        $tagRepository = $this->entityManager->getRepository(Tag::class);

        $existingTags = $tagRepository->findBy([]);
        $tagMap = [];
        foreach ($existingTags as $tag) {
            $tagMap[$tag->getName()] = $tag;
        }

        foreach ($tagsData as $tagData) {
            $parentTag = $tagMap[$tagData['name']] ?? null;

            if (!$parentTag) {
                $parentTag = new Tag($tagData['name']);
                $this->entityManager->persist($parentTag);
                $tagMap[$tagData['name']] = $parentTag;
            }

            foreach ($tagData['children'] as $childTagName) {
                if (!isset($tagMap[$childTagName])) {
                    $childTag = new Tag($childTagName, $parentTag);
                    $this->entityManager->persist($childTag);
                    $tagMap[$childTagName] = $childTag;
                }
            }
        }

        $this->entityManager->flush();

        $io->success('Les tags ont été importés avec succès !');
        return Command::SUCCESS;
    }
}