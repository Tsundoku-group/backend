<?php

namespace App\Service;

use App\Entity\Group;
use App\Entity\GroupRequest;
use App\Entity\Profile;
use App\Enum\RequestStatusEnum;
use App\Repository\GroupRequestRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use LogicException;
use RuntimeException;

readonly class GroupRequestService
{
    public function __construct(private EntityManagerInterface $entityManager, private GroupRequestRepository $groupRequestRepository)
    {
    }

    public function requestToJoinGroup(Group $group, Profile $profile): GroupRequest
    {
        $existingRequest = $this->groupRequestRepository->findOneBy([
            'group' => $group,
            'profile' => $profile,
        ]);

        if ($existingRequest) {
            throw new Exception('Une demande est déjà en attente pour ce groupe.');
        }

        $request = new GroupRequest($group, $profile);

        $this->entityManager->persist($request);
        $this->entityManager->flush();

        return $request;
    }

    public function updateRequestStatus(GroupRequest $request, RequestStatusEnum $status): void
    {
        if (in_array($request->getStatus(), [RequestStatusEnum::ACCEPTED, RequestStatusEnum::DENIED])) {
            throw new LogicException('Cette demande a déjà été traitée.');
        }

        $request->setStatus($status);

        $request->setUpdatedAt(new DateTimeImmutable());

        if (RequestStatusEnum::ACCEPTED === $status) {
            $request->setJoinAt(new DateTimeImmutable());
        }

        try {
            $this->entityManager->flush();
        } catch (Exception $e) {
            throw new RuntimeException('Erreur lors de la mise à jour de la demande : ' . $e->getMessage());
        }
    }

    public function getPendingRequestsForGroup(int $groupId): array
    {
        return $this->groupRequestRepository->findBy([
            'group' => $groupId,
            'status' => RequestStatusEnum::PENDING,
        ]);
    }
}
