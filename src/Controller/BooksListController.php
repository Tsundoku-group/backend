<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/bookslist')]
final class BooksListController extends AbstractController
{
    #[Route('/{profileId}', name: 'get_profile_booksList',methods: ['GET'])]
    public function getBooksListByProfileId(int $profileId, Request $request): JsonResponse
    {

    }


    #[Route('', name: 'create_booksList', methods: ['POST'])]
    public function createBooksList(int $profileId, Request $request): JsonResponse
    {

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
