<?php

namespace App\Controller;

use App\Service\Redis\RedisNotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/notification')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly RedisNotificationService $redisNotificationService,
    ) {
    }

    #[Route('/{receiverId}', name: 'get_notifications', methods: ['GET'])]
    public function getNotifications(string $receiverId): JsonResponse
    {
        $notifications = $this->redisNotificationService->getNotifications($receiverId);

        return $this->json([
            'notifications' => $notifications
        ]);
    }

    #[Route('/{receiverId}/read', name: 'mark_notifications_as_read', methods: ['POST'])]
    public function markAsReadNotifications(string $receiverId): JsonResponse
    {
        $this->redisNotificationService->markNotificationsAsReadInCache($receiverId);

        return $this->json(['message' => 'Toutes les notifications ont été marquées comme lues en cache.']);
    }
}