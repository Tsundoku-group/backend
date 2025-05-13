<?php

namespace App\Controller;

use App\Constant\ErrorMessageConstant;
use App\Constant\GenericErrorMessagesConstant;
use App\Constant\UserErrorMessagesConstant;
use App\DTO\BooksList\BooksListDTO;
use App\Entity\User;
use App\Enum\VisibilityEnum;
use App\Repository\BooksListRepository;
use App\Validator\Constraints\ProfileValidator;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/bookslist')]
final class BooksListController extends AbstractController
{
    public function __construct(
        private readonly BooksListRepository $booksListRepository,
        private readonly ProfileValidator    $profileValidator,
    )
    {
    }

    #[Route('/{profileId}', name: 'get_profile_booksList', methods: ['GET'])]
    public function getBooksListByProfileId(int $profileId): JsonResponse
    {
        $profile = $this->profileValidator->validateProfile($profileId);

        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => UserErrorMessagesConstant::USER_NOT_FOUND], Response::HTTP_UNAUTHORIZED);
        }

        try {
            if ($user->getProfiles()->contains($profile)) {
                $booksLists = $this->booksListRepository->findBy(['profile' => $profile->getId()]);
            } else {
                $booksLists = $this->booksListRepository->findBy(['profile' => $profile->getId(), 'visibility' => VisibilityEnum::PUBLIC]);
            }

            return $this->json($booksLists, Response::HTTP_OK, [], ['groups' => ['public']]);

        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route('', name: 'create_booksList', methods: ['POST'])]
    public function createBooksList(Request $request): JsonResponse
    {
        $jsonData = $request->getContent();

        if (!$jsonData) {
            return $this->json(['error' => GenericErrorMessagesConstant::INVALID_DATA], Response::HTTP_BAD_REQUEST);
        }

        try {
            $booksListDTO = new BooksListDTO($jsonData);

            return $this->json($booksListDTO, Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{booksListId}', name: 'get_booksList', methods: ['GET'])]
    public function getBooksList(Request $request): JsonResponse
    {

    }

    #[Route('/{booksListId}', name: 'delete_booksList', methods: ['DELETE'])]
    public function deleteBooksList(Request $request): JsonResponse
    {

    }

    #[Route('/{booksListId}/add', name: 'add_books_to_booksList', methods: ['POST'])]
    public function addBook(Request $request): JsonResponse
    {

    }

    #[Route('/{booksListId}/remove', name: 'remove_books_from_booksList', methods: ['DELETE'])]
    public function removeBook(Request $request): JsonResponse
    {

    }

    #[Route('/search', name: 'search_public_booksList', methods: ['GET'])]
    public function searchBooksList(Request $request): JsonResponse
    {

    }
}
