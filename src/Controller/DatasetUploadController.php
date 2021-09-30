<?php

namespace Drupal\dataset_upload\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;

class DatasetUploadController extends ControllerBase
{

  /**
    * Display errors from failed compliance checker
    */
    public function failedCChecker()
    {
        $session = \Drupal::request()->getSession();
        $outArr = $session->get("nird_failed");
        $fail_message = $session->get('nird_fail_message');

        //\Drupal::logger('dataset_validation_output')->debug("out array length: " . count($outArr));
        //dpm(gettype($outArr));
        $out = implode(" ", $outArr);
        $session->remove("nird_failed");
        $session->remove("nird_fail_message");
        $renderArr['message'] = [
          '#type' => 'textfield',
          '#value' => $fail_message,
        ];
        $renderArr['info'] = [
    '#markup' => '<span><strong><a class="w3-btn w3-black" href="/dataset_upload/form">Test another dataset</a></strong></span><br><span>Your dataset failed the compliance checker. Please review: </span><br>' . $out,
    '#allowed_tags' => ['div', 'table', 'tr', 'td', 'style','strong', 'script', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span'],
  ];

        return $renderArr;
    }

    public function failedMmd()
    {
        $session = \Drupal::request()->getSession();
        $outArr = $session->get("nird_failed");
        $fail_message = $session->get('nird_fail_message');
        //\Drupal::logger('dataset_validation_output')->debug("out array length: " . count($outArr));
        $session->remove("nird_failed");
        $session->remove("nird_fail_message");
        $renderArr['message'] = [
          '#type' => 'textfield',
          '#value' => $fail_message,
        ];
        $renderArr['info'] = [
    '#markup' => '<span><strong><a class="w3-btn w3-black" href="/dataset_upload/form">Test another dataset</a></strong></span><br><span>Metadata extraction failed: </span><br>' . $out,
    '#allowed_tags' => ['div', 'table', 'tr', 'td', 'style','strong', 'script', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span'],
  ];

        return $renderArr;
    }
}
