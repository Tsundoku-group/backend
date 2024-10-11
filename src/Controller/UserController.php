<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    #[Route('/all', name: 'user_list', methods: 'GET')]
    public function getAll(UserRepository $userRepository, SerializerInterface $serializer): Response
    {
        try {
            $users = $userRepository->findAll();
            foreach ($users as $user) {
                $usersId[] = $user->getUserName();
            }
            dd($usersId);
            return $this->json($users);
        } catch (\Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/new', name: 'user_new', methods: 'POST')]
    public function new(Request $request): Response
    {
        try {
            $data = $request->getContent();
            $user = $data;

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json($user, 201, [], ['groups' => 'user']);
        } catch (\Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(int $id, UserRepository $userRepository): Response
    {
        try {
            $user = $userRepository->find($id);

            if (!$user) {
                return $this->json(['error' => self::USER_NOT_FOUND], 404);
            }

            return $this->json([
                'id' => $user->getId(),
                'username' => $user->getUserName(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'birthDay' => $user->getbirthday(),
                'email' => $user->getEmail(),
                'biographie' => $user->getBiographie()
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }


    #[Route('/{id}/edit', name: 'user_edit', methods: 'PUT')]
    public function update(Request $request, User $user, SerializerInterface $serializer, ValidatorInterface $validator): Response
    {
        try {
            $data = $request->getContent();
            $serializer->deserialize($data, User::class, 'json', ['object_to_populate' => $user]);
            $errors = $validator->validate($user);

            if (count($errors) > 0) {
                return $this->json($errors, 400);
            }

            $this->entityManager->flush();

            return $this->json($user, 200, [], ['groups' => 'user']);
        } catch (\Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}', name: 'user_delete', methods: 'DELETE')]
    public function delete(int $id): Response
    {
        try {
            $user = $this->userRepository->findOneBy(['id' => $id]);
            $this->entityManager->remove($user);
            $this->entityManager->flush();

            return new Response(null, 204);
        } catch (\Exception $e) {
            return $this->json(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }
}
