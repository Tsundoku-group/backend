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

readonly class RedisReactService
{
    public function __construct(
        private RedisClientConfig      $redis,
        private EntityManagerInterface $entityManager,
        private ReactRepository        $reactRepository,
        private ProfileRepository      $profileRepository,
        private LoggerInterface        $logger
    )
    {
    }

    public function addReactionToCache(string $recipientId, string $profileId, string $resourceType, string $reactType, ?string $resourceId): void
    {
        $reactionKey = "reactions:{$resourceType}:{$resourceId}";

        $reactionData = json_encode([
            'recipientId' => $recipientId,
            'profileId' => $profileId,
            'reactType' => $reactType,
            'resourceId' => $resourceId,
            'createdAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        $this->redis->getClient()->rpush($reactionKey, (array)$reactionData);

        if ($this->redis->getClient()->llen($reactionKey) >= 50) {
            $this->flushAllReactionsToDatabase();
        }
    }

    public function getReactionsFromCache(string $resourceId, string $resourceType): array
    {
        $reactionKey = "reactions:{$resourceType}:{$resourceId}";
        $reactionsJson = $this->redis->getClient()->lrange($reactionKey, 0, -1);

        return array_map(fn($json) => json_decode($json, true), $reactionsJson);
    }

    public function flushAllReactionsToDatabase(): void
    {
        $keys = $this->redis->getClient()->keys("reactions:*");

        if (empty($keys)) {
            $this->logger->warning("❌ Aucune clé de réaction trouvée dans Redis !");
            return;
        }

        $this->logger->info("🔍 Clés trouvées dans Redis :", ['keys' => $keys]);

        $batchPersist = false;

        foreach ($keys as $key) {
            $parts = explode(":", $key);
            if (count($parts) < 3) continue;

            [$prefix, $resourceType, $resourceId] = $parts;

            $this->logger->info("⚡ Flush en cours pour {$resourceId} ({$resourceType})");

            try {
                $reactions = $this->getReactionsFromCache($resourceId, $resourceType);
                if (empty($reactions)) {
                    $this->logger->warning("⚠️ Aucune réaction à traiter pour {$resourceId} ({$resourceType})");
                    continue;
                }

                foreach ($reactions as $reactionData) {
                    $profile = $this->profileRepository->find($reactionData['profileId']);
                    if (!$profile) {
                        $this->logger->error("❌ Profil introuvable : {$reactionData['profileId']}");
                        continue;
                    }

                    $existingReaction = $this->reactRepository->findOneBy([
                        'profile' => $profile,
                        'resourceId' => $resourceId,
                        'resourceType' => ResourceTypeEnum::from($resourceType),
                        'reactType' => ReactTypeEnum::from($reactionData['reactType']),
                    ]);

                    if (!$existingReaction) {
                        $reaction = new React(
                            $profile,
                            $resourceId,
                            ResourceTypeEnum::from($resourceType),
                            ReactTypeEnum::from($reactionData['reactType'])
                        );

                        $this->entityManager->persist($reaction);
                        $batchPersist = true;

                        $this->logger->info("📝 Nouvelle réaction ajoutée", [
                            'profileId' => $reactionData['profileId'],
                            'resourceId' => $reactionData['resourceId'],
                            'resourceType' => $resourceType,
                            'reactType' => $reactionData['reactType']
                        ]);
                    }
                }

                $this->redis->getClient()->del($key);
                $this->logger->info("✅ Flush réussi pour {$resourceId} ({$resourceType})", [
                    'timestamp' => date('Y-m-d H:i:s')
                ]);

            } catch (Exception $e) {
                $this->logger->error("❌ Échec du flush des réactions pour {$resourceId} ({$resourceType})", [
                    'error' => $e->getMessage(),
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
        }

        if ($batchPersist) {
            $this->entityManager->flush();
            $this->logger->info("✅ Toutes les nouvelles réactions ont été enregistrées en BDD.");
        }
    }
}