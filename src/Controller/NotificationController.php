<?php

namespace App\Controller;

use App\Message\FlushNotificationsMessage;
use App\Repository\NotificationRepository;
use App\Service\Redis\RedisNotificationService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/notification')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly RedisNotificationService $redisNotificationService,
        private readonly NotificationRepository $notificationRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/{receiverId}', name: 'get_notifications', methods: ['GET'])]
    public function getNotifications(string $receiverId, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $notifications = $this->redisNotificationService->getNotificationsFromCache($receiverId);
        if (empty($notifications)) {
            $notificationsFromDB = $this->notificationRepository->findBy(
                ['recipient' => $receiverId],
                ['createdAt' => 'DESC'],
                $limit,
                $offset
            );

            $notifications = array_map(fn($notification) => [
                'actorId' => $notification->getProfile()->getId(),
                'type' => $notification->getType(),
                'resourceId' => $notification->getResourceId(),
                'isRead' => $notification->isRead(),
                'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
            ], $notificationsFromDB);
        }

        return $this->json([
            'notifications' => $notifications,
            'currentPage' => $page,
            'perPage' => $limit,
        ]);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/{receiverId}/read', name: 'get_notification', methods: ['POST'])]
    public function markAsReadNotifications(string $receiverId ,MessageBusInterface $bus): JsonResponse
    {
        $notifications = $this->redisNotificationService->getNotificationsFromCache($receiverId);

        if (empty($notifications)) {
            $notificationsFromDB = $this->notificationRepository->findBy([
                'recipient' => $receiverId,
                'isRead' => false
            ]);

            if (empty($notificationsFromDB)) {
                return $this->json(['message' => 'Aucune notification à marquer comme lue'], 404);
            }

            foreach ($notificationsFromDB as $notification) {
                $notification->setIsRead(true);
                $notification->setIsReadAt(new DateTimeImmutable());
            }

            $this->entityManager->flush();

            return $this->json(['message' => 'Toutes les notifications en BDD ont été marquées comme lues']);
        }

        foreach ($notifications as &$notification) {
            $notification['isRead'] = true;
        }

        $bus->dispatch(new FlushNotificationsMessage($receiverId));

        return $this->json(['message' => 'Toutes les notifications ont été marquées comme lues et enregistrées en BDD']);
    }
}
