<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use App\Service\Redis\RedisNotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/notification')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly RedisNotificationService $redisNotificationService,
        private readonly NotificationRepository   $notificationRepository
    )
    {
    }

    #[Route('/{receiverId}', name: 'get_notifications', methods: ['GET'])]
    public function getNotifications(string $receiverId): JsonResponse
    {
        $notifications = $this->redisNotificationService->getNotificationsFromCache($receiverId);

        if (empty($notifications)) {
            $notificationsFromDB = $this->notificationRepository->findBy([
                'receiver' => $receiverId,
            ]);

            $notifications = array_map(fn($notification) => [
                'profileId' => $notification->getProfile()->getId(),
                'type' => $notification->getType(),
                'resourceId' => $notification->getResourceId(),
                'isRead' => $notification->isRead(),
                'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
            ], $notificationsFromDB);
        }

        return $this->json(['notifications' => $notifications]);
    }
}