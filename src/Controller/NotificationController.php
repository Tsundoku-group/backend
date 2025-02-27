<?php

namespace App\Controller;

use App\Service\Redis\RedisNotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/notification')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly RedisNotificationService $redisNotificationService,
    )
    {
    }

    #[Route('/{receiverId}', name: 'get_notifications', methods: ['GET'])]
    public function getNotifications(string $receiverId, Request $request): JsonResponse
    {
        $weeksAgo = max(0, (int)$request->query->get('weeksAgo', 0));

        $notifications = $this->redisNotificationService->getNotificationsByWeek($receiverId, $weeksAgo);

        return $this->json([
            'notifications' => $notifications,
        ]);
    }

    #[Route('/{receiverId}/read', name: 'mark_notifications_as_read', methods: ['POST'])]
    public function markAsReadNotifications(string $receiverId): JsonResponse
    {
        $this->redisNotificationService->markNotificationsAsReadInCache($receiverId);

        return $this->json(['message' => 'Toutes les notifications ont été marquées comme lues en cache.']);
    }
}
