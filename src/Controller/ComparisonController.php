<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ComparisonController extends AbstractController
{

    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

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
     * @Route("/comparison/compare-translations",
     *     name="app_comparison_compare_translations",
     *     options = { "expose" = true })
     * @throws TransportExceptionInterface
     */
    public function compareTranslations(Request $request): JsonResponse
    {


        $sourceURL = $request->request->get('source_url');
        $targetURL = $request->request->get('target_url');

        $responseSource = $this->client->request(
            'GET',
            $sourceURL
        );

        $source = $responseSource->toArray();

        dump($source);exit;

        return new JsonResponse($responseSource->getStatusCode(), $responseSource->getStatusCode());

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
