<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/profile')]
class ProfileController extends AbstractController
{
    private const INTERNAL_SERVER_ERROR = 'Internal Server Error';
    private const PROFILE_NOT_FOUND = 'Profile not found';

    private $entityManager;
    private $profileRepository;

    public function __construct(EntityManagerInterface $entityManager, ProfileRepository $profileRepository)
    {
        $this->entityManager = $entityManager;
        $this->profileRepository = $profileRepository;
    }

    #[Route('/{profileId}', name: 'profile_show', methods: ['GET'])]
    public function show(int $profileId): JsonResponse
    {
        try {
            $profile = $this->profileRepository->findOneBy(['user' => $profileId]);

            if (!$profile) {
                return new JsonResponse(['error' => self::PROFILE_NOT_FOUND], 404);
            }

            $profileData = [
                'id' => $profile->getId(),
                'firstName' => $profile->getFirstName(),
                'lastName' => $profile->getLastName(),
                'username' => $profile->getUsername(),
                'birthday' => $profile->getBirthday() ? $profile->getBirthday()->format('Y-m-d') : null,
                'gender' => $profile->getGender(),
                'phoneNumber' => $profile->getPhoneNumber(),
                'bio' => $profile->getBio(),
                'facebook' => $profile->getFacebook(),
                'instagram' => $profile->getInstagram(),
                'x' => $profile->getX(),
                'createdAt' => $profile->getCreatedAt() ? $profile->getCreatedAt()->format(\DateTime::ATOM) : null,
            ];

            return new JsonResponse($profileData);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}/edit', name: 'profile_edit', methods: ['PUT'])]
    public function update(Request $request, Profile $profile): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return new JsonResponse(['error' => self::PROFILE_NOT_FOUND], 404);
            }

            $profile->setFirstName($data['firstName'] ?? $profile->getFirstName());
            $profile->setLastName($data['lastName'] ?? $profile->getLastName());
            $profile->setUsername($data['username'] ?? $profile->getUsername());
            $profile->setBirthday(new \DateTime($data['birthday'] ?? $profile->getBirthday()));
            $profile->setGender($data['gender'] ?? $profile->getGender());
            $profile->setPhoneNumber($data['phoneNumber'] ?? $profile->getPhoneNumber());
            $profile->setBio($data['bio'] ?? $profile->getBio());
            $profile->setFacebook($data['facebook'] ?? $profile->getFacebook());
            $profile->setInstagram($data['instagram'] ?? $profile->getInstagram());
            $profile->setX($data['x'] ?? $profile->getX());

            $this->entityManager->flush();

            return $this->json($profile, 200);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{profileId}', name: 'delete_profile', methods: ['DELETE'])]
    public function delete(int $profileId): JsonResponse
    {
        try {
            $profile = $this->profileRepository->findOneBy(['user' => $profileId]);

            if (!$profile) {
                return new JsonResponse(['error' => self::PROFILE_NOT_FOUND], 404);
            }
            $this->entityManager->remove($profile);
            $this->entityManager->flush();

            return new JsonResponse(null, 204);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => self::INTERNAL_SERVER_ERROR], 500);
        } catch (ORMException $e) {
        }

        return new JsonResponse(null, 500);
    }
}