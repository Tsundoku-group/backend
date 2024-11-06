<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    private const INTERNAL_SERVER_ERROR = 'Internal Server Error';
    private const USER_NOT_FOUND = 'User not found';

    private $entityManager;
    private $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    #[Route('/all', name: 'user_list', methods: ['GET'])]
    public function getAll(): Response
    {
        try {
            $users = $this->userRepository->findAll();
            $usernames = [];

            foreach ($users as $user) {
                $usernames[] = $user->getUserName();
            }

            return $this->json($usernames);
        } catch (\Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/new', name: 'user_new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json(['error' => 'Invalid JSON'], 400);
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setPassword($data['password']);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json($user, 201);
        } catch (\Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        try {
            $user = $this->userRepository->find($id);

            if (!$user) {
                return $this->json(['error' => self::USER_NOT_FOUND], 404);
            }

            return $this->json([
                'id' => $user->getId(),
                'username' => $user->getUserName(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'birthDay' => $user->getBirthday()->format('Y-m-d'),
                'email' => $user->getEmail(),
                'biographie' => $user->getBiographie(),
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}/edit', name: 'user_edit', methods: ['PUT'])]
    public function update(Request $request, User $user): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json(['error' => 'Invalid JSON'], 400);
            }

            $user->setEmail($data['email'] ?? $user->getEmail());
            $user->setPassword($data['password'] ?? $user->getPassword());

            $this->entityManager->flush();

            return $this->json($user, 200);
        } catch (\Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        try {
            $user = $this->userRepository->findOneBy(['id' => $id]);
            if (!$user) {
                return $this->json(['error' => self::USER_NOT_FOUND], 404);
            }

            $this->entityManager->remove($user);
            $this->entityManager->flush();

            return new Response(null, 204);
        } catch (\Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }
}