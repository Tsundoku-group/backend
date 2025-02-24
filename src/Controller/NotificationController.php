<?php

namespace App\Controller;

use App\Service\Redis\RedisNotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/notification')]
class NotificationController extends AbstractController
{
    private RedisNotificationService $notificationService;

    public function __construct(RedisNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    #[Route('', name: 'get_notifications', methods: ['GET'])]
    public function getNotifications(): JsonResponse
    {
        $recipientId = $this->getUser()->getId();

        $this->notificationService->flushNotifications($recipientId);

        $notifications = $this->notificationService->getNotifications($recipientId);

        return $this->json(['notifications' => $notifications]);
    }
}