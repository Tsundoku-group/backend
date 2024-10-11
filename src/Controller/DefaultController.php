<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class DefaultController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        return new JsonResponse(['success' => true]);
    }
}