<?php

namespace App\Service\Redis;

use App\Config\RedisClientConfig;
use App\Entity\React;
use App\Enum\ReactTypeEnum;
use App\Enum\ResourceTypeEnum;
use App\Repository\ProfileRepository;
use App\Repository\ReactRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class RedisReactService
{
    public function __construct(
        private RedisClientConfig $redis,
        private EntityManagerInterface $entityManager,
        private ReactRepository $reactRepository,
        private ProfileRepository $profileRepository,
        #[Autowire(service: 'monolog.logger.reactions')]
        private LoggerInterface $logger,
    ) {
    }

    public function addReactionToCache(string $profileId, string $receiverId, string $resourceType, string $reactType, ?string $resourceId): void
    {
        $reactionKey = "reactions:{$resourceType}:{$resourceId}";

        $reactionData = json_encode([
            'actorId' => $profileId,
            'receiverId' => $receiverId,
            'reactType' => $reactType,
            'resourceId' => $resourceId,
            'createdAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        $this->redis->getClient()->rpush($reactionKey, (array) $reactionData);

        if ($this->redis->getClient()->llen($reactionKey) >= 50) {
            $this->flushAllReactionsToDatabase();
        }
    }

    public function getReactionsFromCache(string $resourceId, string $resourceType): array
    {
        $reactionKey = "reactions:{$resourceType}:{$resourceId}";
        $reactionsJson = $this->redis->getClient()->lrange($reactionKey, 0, -1);

        return array_map(fn ($json) => json_decode($json, true), $reactionsJson);
    }

    public function flushAllReactionsToDatabase(): void
    {
        $keys = $this->redis->getClient()->keys('reactions:*');

        if (empty($keys)) {
            $this->logger->warning('❌ Aucune clé de réaction trouvée dans Redis !');

            return;
        }

        $this->logger->info('🔍 Clés trouvées dans Redis :', ['keys' => $keys]);

        $batchPersist = false;

        foreach ($keys as $key) {
            $parts = explode(':', $key);
            if (count($parts) < 3) {
                continue;
            }

            [$prefix, $resourceType, $resourceId] = $parts;
            $this->logger->info("⚡ Flush en cours pour {$resourceId} ({$resourceType})");

            try {
                $reactions = $this->getReactionsFromCache($resourceId, $resourceType);
                if (empty($reactions)) {
                    $this->logger->warning("⚠️ Aucune réaction à traiter pour {$resourceId} ({$resourceType})");
                    continue;
                }

                $actorIds = array_column($reactions, 'actorId');
                $receiverIds = array_column($reactions, 'receiverId');
                $allProfiles = $this->profileRepository->findBy(['id' => array_unique(array_merge($actorIds, $receiverIds))]);

                $profileMap = [];
                foreach ($allProfiles as $profile) {
                    $profileMap[$profile->getId()] = $profile;
                }

                $existingReactions = $this->reactRepository->findBy([
                    'resourceId' => $resourceId,
                    'resourceType' => ResourceTypeEnum::from($resourceType),
                ]);

                $existingReactionsMap = [];
                foreach ($existingReactions as $reaction) {
                    $existingReactionsMap[$reaction->getActor()->getId()][$reaction->getReactType()->value] = $reaction;
                }

                foreach ($reactions as $reactionData) {
                    $actor = $profileMap[$reactionData['actorId']] ?? null;
                    $receiver = $profileMap[$reactionData['receiverId']] ?? null;

                    if (!$actor || !$receiver) {
                        $this->logger->error("❌ Profils introuvables : {$reactionData['actorId']} ou {$reactionData['receiverId']}");
                        continue;
                    }

                    $reactType = ReactTypeEnum::from($reactionData['reactType']);

                    if (isset($existingReactionsMap[$actor->getId()][$reactType->value])) {
                        continue;
                    }

                    $reaction = new React($actor, $receiver, $resourceId, ResourceTypeEnum::from($resourceType), $reactType);
                    $this->entityManager->persist($reaction);
                    $batchPersist = true;

                    $this->logger->info('📝 Nouvelle réaction ajoutée', [
                        'actorId' => $reactionData['actorId'],
                        'receiverId' => $reactionData['receiverId'],
                        'resourceId' => $reactionData['resourceId'],
                        'resourceType' => $resourceType,
                        'reactType' => $reactionData['reactType'],
                    ]);
                }

                $this->redis->getClient()->del($key);
                $this->logger->info("✅ Flush réussi pour {$resourceId} ({$resourceType})", [
                    'timestamp' => date('Y-m-d H:i:s'),
                ]);
            } catch (Exception $e) {
                $this->logger->error("❌ Échec du flush des réactions pour {$resourceId} ({$resourceType})", [
                    'error' => $e->getMessage(),
                    'timestamp' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        if ($batchPersist) {
            $this->entityManager->flush();
            $this->logger->info('✅ Toutes les nouvelles réactions ont été enregistrées en BDD.');
        }
    }
}
