<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
     */
    public function compareTranslations(Request $request): JsonResponse
    {


        $sourceURL = $request->request->get('source_url');
        $targetURL = $request->request->get('target_url');


        $responseSource = $this->client->request(
            'GET',
            $sourceURL
        );

        $responseTarget = $this->client->request(
            'GET',
            $targetURL
        );

        $source = $responseSource->toArray();
        $target = $responseTarget->toArray();


        $translations = [];
        foreach ($target as $item => $value) {
            if (!array_key_exists($item, $source)):
                $translations[] = [
                    'type' => 'Missing',
                    'key' => $item,
                    'source_value' => '',
                    'target_value' => $value
                ];
            else:
                if ($source[$item] !== $value):
                    $translations[] = [
                        'type' => 'Changed',
                        'key' => $item,
                        'source_value' => $source[$item],
                        'target_value' => $value
                    ];
                endif;
            endif;
        }

        foreach ($source as $item => $value) {

            if (!array_key_exists($item, $target)):
                $translations[] = [
                    'type' => 'Missing',
                    'key' => $item,
                    'source_value' => $value,
                    'target_value' => ''
                ];
            endif;

        }

        return new JsonResponse($translations, $responseSource->getStatusCode());


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
