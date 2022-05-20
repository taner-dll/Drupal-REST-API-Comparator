<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TranslationController extends AbstractController
{
    /**
     * @Route("/translation", name="app_translation")
     */
    public function index(): Response
    {
        return $this->render('translation/index.html.twig', [
            'controller_name' => 'TranslationController',
        ]);
    }
}
