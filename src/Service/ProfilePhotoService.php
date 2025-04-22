<?php

namespace App\Service;

use App\Constant\GenericErrorMessagesConstant;
use App\Entity\Profile;
use App\Entity\ProfilePhoto;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class ProfilePhotoService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function addPhotoToProfile(Profile $profile, string $url, string $type): bool
    {
        try {
            $profilePhotos = $profile->getProfilePhotos();

            if ($profilePhotos->isEmpty()) {
                $profilePhoto = new ProfilePhoto();
                $profilePhoto->setUrl($url)
                    ->setType($type)
                    ->activate()
                    ->setProfile($profile);

                $this->entityManager->persist($profilePhoto);
                $this->entityManager->flush();

                return true;
            }

            foreach ($profilePhotos as $profilePhoto) {
                if ($type === $profilePhoto->getType()) {
                    $profilePhoto->deactivate();
                    $this->entityManager->persist($profilePhoto);
                }
            }

            $newProfilePhoto = new ProfilePhoto();
            $newProfilePhoto->setUrl($url)
                ->setType($type)
                ->activate()
                ->setProfile($profile);

            $this->entityManager->persist($newProfilePhoto);
            $this->entityManager->flush();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function deletePhotoFromProfile(Profile $profile, string $url, string $type): bool
    {
        try {
            foreach ($profile->getProfilePhotos() as $profilePhoto) {
                if ($type === $profilePhoto->getType() && $profilePhoto->getUrl() === $url) {
                    $this->entityManager->remove($profilePhoto);
                }
            }

            $this->entityManager->flush();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getActiveProfilePhoto(Profile $profile): array
    {
        try {
            $photos = $profile->getProfilePhotos();

            $activePhotos = [];
            foreach ($photos as $photo) {
                if ($photo->isActive()) {
                    $activePhotos[] = [
                        'url' => $photo->getUrl(),
                        'type' => $photo->getType(),
                        'isActive' => $photo->isActive(),
                    ];
                }
            }

            return [
                'status' => 'success',
                'photos' => $activePhotos,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function setActivateProfilePhoto(Profile $profile, string $url, string $type): array
    {
        try {
            $found = false;

            foreach ($profile->getProfilePhotos() as $profilePhoto) {
                if ($profilePhoto->getUrl() === $url && $profilePhoto->getType() === $type && $profilePhoto->isActive()) {
                    return [
                        'status' => 'error',
                        'message' => 'Cette photo de profil est déjà activée',
                    ];
                }

                if ($profilePhoto->getType() === $type && $profilePhoto->isActive()) {
                    $profilePhoto->deactivate();
                    $this->entityManager->persist($profilePhoto);
                }

                if ($profilePhoto->getUrl() === $url && $profilePhoto->getType() === $type) {
                    $profilePhoto->activate();
                    $this->entityManager->persist($profilePhoto);
                    $found = true;
                }
            }

            if (!$found) {
                return [
                    'status' => 'error',
                    'message' => "Aucune photo de profil n'a été trouvée",
                ];
            }

            $this->entityManager->flush();

            return [
                'status' => 'success',
                'message' => 'Photo activée avec succès',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR . $e->getMessage(),
            ];
        }
    }
}
