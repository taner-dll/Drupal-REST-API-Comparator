<?php

namespace App\Controller;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ComparisonController extends AbstractController
{

  private HttpClientInterface $client;


  public function __construct(HttpClientInterface $client)
  {
    $this->client = $client;
  }

  /**
   * @Route("/comparison", name="app_comparison")
   */
  public function index(): Response
  {
    return $this->redirectToRoute('app_comparison_translations');
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
   * @Route("/comparison/compare-contents",
   *     name="app_comparison_compare_contents",
   *     options = { "expose" = true })
   */
  public function compareContents(Request $request)
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

    $type = explode("?type=", $sourceURL);
    $type = explode("&", $type[1]);
    $type = $type[0];


    $contents = [];
    if ($type === 'bundle' || $type === 'branded_fare') {

      $sourceTitle = [];
      $sourceSearchTerm = [];
      foreach ($source as $value) {
        $sourceTitle[] = $value['title'][0]['value'];
        $sourceSearchTerm[] = $value['uuid'][0]['value'];
      }

      foreach ($target as $value) {

        if (!in_array($value['uuid'][0]['value'], $sourceSearchTerm)):

          $target_data = 'Title: ' . $value['title'][0]['value'] . ' - ' .
            'Code: ' . $value['field_bundle_code'][0]['value'];

          $contents[] = [
            'type' => 'Missing',
            'key' => $value['type'][0]['target_id'] . ' (' . $value['uuid'][0]['value'] . ')',
            'content_type' => $value['type'][0]['target_id'],
            'source_value' => '',
            'target_value' => $target_data
          ];
        else:

          // Title changed?
          if (!in_array($value['title'][0]['value'], $sourceTitle)):

            $target_data = 'Title: ' . $value['title'][0]['value'] . ' - ' .
              'Code: ' . $value['field_bundle_code'][0]['value'];

            //Aynı UUID'ye sahip source içeriğine ait title'ı getir.
            $bundleSourceTitle = '';
            foreach ($source as $val) {
              if ($val['uuid'][0]['value'] === $value['uuid'][0]['value']) {
                $bundleSourceTitle = 'Title: ' . $val['title'][0]['value'] . ' - ' .
                  'Code: ' . $val['field_bundle_code'][0]['value'];
              }
            }

            $contents[] = [
              'type' => 'Changed',
              'key' => $value['type'][0]['target_id'] . ' (' . $value['uuid'][0]['value'] . ')',
              'content_type' => $value['type'][0]['target_id'],
              'source_value' => $bundleSourceTitle,
              'target_value' => $target_data
            ];
          endif;


          // Check Modified
          foreach ($source as $val) {
            if ($val['uuid'][0]['value'] === $value['uuid'][0]['value']) {

              if ($val['changed'][0]['value'] !== $value['changed'][0]['value']){
                $contents[] = [
                  'type' => 'Modified',
                  'key' => $value['type'][0]['target_id'] . ' (' . $value['uuid'][0]['value'] . ')',
                  'content_type' => $value['type'][0]['target_id'],
                  'source_value' => 'Title: ' . $val['title'][0]['value'] . ' - '
                    .'Code: ' . $val['field_bundle_code'][0]['value'] . "\n" . $val['changed'][0]['value'],
                  'target_value' => 'Title: ' . $value['title'][0]['value'] . ' - '
                    .'Code: ' . $value['field_bundle_code'][0]['value']."\n" . $value['changed'][0]['value'],
                ];
              }

            }
          }

        endif;
      }


      $targetSearchTerm = [];
      foreach ($target as $value) {
        $targetSearchTerm[] = $value['uuid'][0]['value'];
      }
      foreach ($source as $value) {

        if (!in_array($value['uuid'][0]['value'], $targetSearchTerm)):

          $source_data = 'Title: ' . $value['title'][0]['value'] . ' - ' .
            'Code: ' . $value['field_bundle_code'][0]['value'];

          $contents[] = [
            'type' => 'Missing',
            'key' => $value['type'][0]['target_id'] . ' (' . $value['uuid'][0]['value'] . ')',
            'content_type' => $value['type'][0]['target_id'],
            'source_value' => $source_data,
            'target_value' => ''
          ];
        endif;
      }


    }
    else { // not bundle || branded_fare

      $sourceTitle = [];
      $sourceSearchTerm = [];
      foreach ($source as $value) {
        $sourceTitle[] = $value['title'][0]['value'];
        $sourceSearchTerm[] = $value['uuid'][0]['value'];
      }

      foreach ($target as $value) {

        if (!in_array($value['uuid'][0]['value'], $sourceSearchTerm)):

          $target_data = 'Title: ' . $value['title'][0]['value'];

          $contents[] = [
            'type' => 'Missing',
            'key' => $value['type'][0]['target_id'] . ' (' . $value['uuid'][0]['value'] . ')',
            'content_type' => $value['type'][0]['target_id'],
            'source_value' => '',
            'target_value' => $target_data
          ];
        else:

          // Title changed?
          if (!in_array($value['title'][0]['value'], $sourceTitle)):

            $target_data = 'Title: ' . $value['title'][0]['value'];

            //Aynı UUID'ye sahip source içeriğine ait title'ı getir.
            $sourceTitle = '';
            foreach ($source as $val) {
              if ($val['uuid'][0]['value'] === $value['uuid'][0]['value']) {
                $sourceTitle = $val['title'][0]['value'];
              }
            }

            $contents[] = [
              'type' => 'Changed',
              'key' => $value['type'][0]['target_id'] . ' (' . $value['uuid'][0]['value'] . ')',
              'content_type' => $value['type'][0]['target_id'],
              'source_value' => $sourceTitle,
              'target_value' => $target_data
            ];
          endif;

          // Check Modified
          foreach ($source as $val) {
            if ($val['uuid'][0]['value'] === $value['uuid'][0]['value']) {

              if ($val['changed'][0]['value'] !== $value['changed'][0]['value']){
                $contents[] = [
                  'type' => 'Modified',
                  'key' => $value['type'][0]['target_id'] . ' (' . $value['uuid'][0]['value'] . ')',
                  'content_type' => $value['type'][0]['target_id'],
                  'source_value' => 'Title: ' . $val['title'][0]['value'] . ' - '
                    .'Code: ' . $val['field_bundle_code'][0]['value'] . "\n" . $val['changed'][0]['value'],
                  'target_value' => 'Title: ' . $value['title'][0]['value'] . ' - '
                    .'Code: ' . $value['field_bundle_code'][0]['value']."\n" . $value['changed'][0]['value'],
                ];
              }

            }
          }
        endif;
      }


      $targetSearchTerm = [];
      foreach ($target as $value) {
        $targetSearchTerm[] = $value['uuid'][0]['value'];
      }
      foreach ($source as $value) {

        if (!in_array($value['uuid'][0]['value'], $targetSearchTerm)):

          $source_data = 'Title: ' . $value['title'][0]['value'] . '-' . $value['langcode'][0]['value'];

          $contents[] = [
            'type' => 'Missing',
            'key' => $value['type'][0]['target_id'] . ' (' . $value['uuid'][0]['value'] . ')',
            'content_type' => $value['type'][0]['target_id'],
            'source_value' => $source_data,
            'target_value' => ''
          ];
        endif;
      }

    }

    if ($request->request->get('export_xlsx') === 'true') {
      return $this->exportXLSX($sourceURL,$targetURL,$contents,'Content');
    }

    return new JsonResponse($contents, $responseSource->getStatusCode());
  }

  /**
   * @Route("/comparison/compare-translations",
   *     name="app_comparison_compare_translations",
   *     options = { "expose" = true })
   */
  public function compareTranslations(Request $request)
  {

    $translationEndpointPathSource = $request->request->get('translation_endpoint_path_source');
    $localeSource = $request->request->get('locale_source');

    $translationEndpointPathTarget = $request->request->get('translation_endpoint_path_target');
    $localeTarget = $request->request->get('locale_target');

    if ($translationEndpointPathTarget!==$translationEndpointPathSource){
      return new JsonResponse("Endpoint paths do not match:<br>".
        $translationEndpointPathSource.'<br>'.$translationEndpointPathTarget, Response::HTTP_BAD_REQUEST);
    }

    if ($localeSource!==$localeTarget){
      return new JsonResponse("Locales do not match:<br>".
        $localeSource.'<br>'.$localeTarget, Response::HTTP_BAD_REQUEST);
    }


    $sourceURL = $request->request->get('source_url');
    $sourceURL = $sourceURL.$translationEndpointPathSource.$localeSource;

    $targetURL = $request->request->get('target_url');
    $targetURL = $targetURL.$translationEndpointPathTarget.$localeTarget;

    try {
      $responseSource = $this->client->request(
        'GET',
        $sourceURL
      );
    }catch (TransportException $e){
      return new JsonResponse($e);
    }

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

    if ($request->request->get('export_xlsx') === 'true') {
      return $this->exportXLSX($sourceURL,$targetURL,$translations,'Translation');
    }

    return new JsonResponse($translations, $responseSource->getStatusCode());
  }




  private function exportXLSX($sourceURL, $targetURL, $contents, $exportType): \Symfony\Component\HttpFoundation\BinaryFileResponse
  {

    $spreadsheet = new Spreadsheet();
    $fontBold = ['font' => ['bold' => true]];
    $sheet = $spreadsheet->getActiveSheet();

    // Sheet Title (Sub Tab)
    $sheet->setTitle($exportType.' Comparison Results');

    // Header
    $sheet->mergeCells('A1:D1');
    $sheet->setCellValue('A1', 'HititCS CMS - '.$exportType.' Comparison Results');
    $spreadsheet->getActiveSheet()->getStyle('A1')->applyFromArray($fontBold);
    $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal('center');

    // Column width
    $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(20);
    $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
    $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(50);
    $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(50);

    // Source & Target URLs
    $sheet->setCellValue('A2', 'SOURCE:');
    $spreadsheet->getActiveSheet()->getStyle('A2')->applyFromArray($fontBold);
    $spreadsheet->getActiveSheet()->getStyle('A2')->getAlignment()->setHorizontal('left');

    $sheet->setCellValue('B2', $sourceURL);
    $spreadsheet->getActiveSheet()->getStyle('B2')->getAlignment()->setHorizontal('left');
    $sheet->mergeCells('B2:D2');

    $sheet->setCellValue('A3', 'TARGET:');
    $spreadsheet->getActiveSheet()->getStyle('A3')->applyFromArray($fontBold);
    $spreadsheet->getActiveSheet()->getStyle('A3')->getAlignment()->setHorizontal('left');

    $sheet->setCellValue('B3', $targetURL);
    $spreadsheet->getActiveSheet()->getStyle('B3')->getAlignment()->setHorizontal('left');
    $sheet->mergeCells('B3:D3');

    $spreadsheet->getActiveSheet()
      ->getStyle('A2:D2')
      ->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN)
      ->setColor(new Color('000000'));

    $spreadsheet->getActiveSheet()
      ->getStyle('A3:D3')
      ->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN)
      ->setColor(new Color('000000'));

    // Column headers: TYPE, KEY, SOURCE VALUE, TARGET VALUE
    $sheet->setCellValue('A5', 'TYPE');
    $spreadsheet->getActiveSheet()->getStyle('A5')->applyFromArray($fontBold);
    $spreadsheet->getActiveSheet()->getStyle('A5')->getAlignment()->setHorizontal('center');

    $sheet->setCellValue('B5', 'KEY');
    $spreadsheet->getActiveSheet()->getStyle('B5')->applyFromArray($fontBold);
    $spreadsheet->getActiveSheet()->getStyle('B5')->getAlignment()->setHorizontal('center');

    $sheet->setCellValue('C5', 'SOURCE');
    $spreadsheet->getActiveSheet()->getStyle('C5')->applyFromArray($fontBold);
    $spreadsheet->getActiveSheet()->getStyle('C5')->getAlignment()->setHorizontal('center');

    $sheet->setCellValue('D5', 'TARGET');
    $spreadsheet->getActiveSheet()->getStyle('D5')->applyFromArray($fontBold);
    $spreadsheet->getActiveSheet()->getStyle('D5')->getAlignment()->setHorizontal('center');

    $spreadsheet->getActiveSheet()
      ->getStyle('A5:D5')
      ->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN)
      ->setColor(new Color('000000'));

    $line = 6;
    foreach ($contents as $val){

      $sheet->setCellValue('A'.$line, $val['type']);
      $spreadsheet->getActiveSheet()->getStyle('A'.$line)->getAlignment()->setHorizontal('center');

      if ($val['type']==='Missing')
        $spreadsheet->getActiveSheet()->getStyle('A'.$line.':D'.$line)
          ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('e0ffe3');

      if ($val['type']==='Changed')
        $spreadsheet->getActiveSheet()->getStyle('A'.$line.':D'.$line)
          ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('fbfcc2');

      if ($val['type']==='Modified')
        $spreadsheet->getActiveSheet()->getStyle('A'.$line.':D'.$line)
          ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('adeaff');


      if (isset($val['content_type'])){
        $sheet->setCellValue('B'.$line, $val['content_type']);
      }
      else{
        $sheet->setCellValue('B'.$line, $val['key']);
      }

      $spreadsheet->getActiveSheet()->getStyle('B'.$line)->getAlignment()->setHorizontal('center');
      $sheet->setCellValue('C'.$line, $val['source_value']);
      $spreadsheet->getActiveSheet()->getStyle('C'.$line)->getAlignment()->setHorizontal('center');
      $sheet->setCellValue('D'.$line, $val['target_value']);
      $spreadsheet->getActiveSheet()->getStyle('D'.$line)->getAlignment()->setHorizontal('center');

      $spreadsheet->getActiveSheet()
        ->getStyle('A'.$line.':D'.$line)
        ->getBorders()->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN)
        ->setColor(new Color('000000'));

      $line++;
    }

    $spreadsheet->getActiveSheet()->getStyle('D6:D3000') ->getAlignment()->setWrapText(true);
    $spreadsheet->getActiveSheet()->getStyle('E6:E3000') ->getAlignment()->setWrapText(true);

    // Create your Office 2007 Excel (XLSX Format)
    $writer = new Xlsx($spreadsheet);

    // Create a Temporary file in the system
    date_default_timezone_set("Europe/Istanbul");
    $dateTime = new \DateTime('now');

    $fileName = 'translation_comparison_'.$dateTime->format('d_m_Y_h_i_s').'.xlsx';

    if ($exportType==='Content'){
      $fileName = 'content_comparison_'.$dateTime->format('d_m_Y_h_i_s').'.xlsx';
    }

    $temp_file = tempnam(sys_get_temp_dir(), $fileName);

    // Create the excel file in the tmp directory of the system
    $writer->save($temp_file);

    // Return the excel file as an attachment
    return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
  }

}
