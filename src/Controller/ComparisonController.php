<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ComparisonController extends AbstractController
{
    /**
     * @Route("/comparison/translations", name="app_comparison_translations")
     */
    public function translations(): Response
    {
        return $this->render('comparison/translations.html.twig', [
            'controller_name' => 'ComparisonController',
        ]);
    }

    /**
     * @Route("/comparison/contents", name="app_comparison_contents")
     */
    public function contents(): Response
    {
        return $this->render('comparison/contents.html.twig', [
            'controller_name' => 'ComparisonController',
        ]);
    }
}
