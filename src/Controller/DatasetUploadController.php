<?php

namespace Drupal\dataset_upload\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for uploading dataset to NIRD.
 */
class DatasetUploadController extends ControllerBase {

  /**
   * Display errors from failed compliance checker.
   */
  public function failedCchecker(Request $request) {
    $session = $request->getSession();
    $outArr = $session->get("nird_failed");
    $fail_message = $session->get('nird_fail_message');

    // dpm(gettype($outArr));
    $out = implode(" ", $outArr);
    $session->remove("nird_failed");
    $session->remove("nird_fail_message");
    $renderArr['message'] = [
      '#type' => 'textfield',
      '#value' => $fail_message,
    ];
    $renderArr['info'] = [
      '#markup' => '<span><strong><a class="w3-btn w3-black" href="/dataset_upload/form">Test another dataset</a></strong></span><br><span>Your dataset failed the compliance checker. Please review: </span><br>' . $out,
      '#allowed_tags' => ['div', 'table', 'tr', 'td', 'style', 'strong',
        'script', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span',
      ],
    ];

    return $renderArr;
  }

  /**
   * Get the failed mmd messages.
   */
  public function failedMmd(Request $request) {
    $session = $request->getSession();
    // $outArr = $session->get("nird_failed");
    $fail_message = $session->get('nird_fail_message');
    $session->remove("nird_failed");
    $session->remove("nird_fail_message");
    $renderArr['message'] = [
      '#type' => 'textfield',
      '#value' => $fail_message,
    ];
    $renderArr['info'] = [
      '#markup' => '<span><strong><a class="w3-btn w3-black" href="/dataset_upload/form">Test another dataset</a></strong></span><br><span>Metadata extraction failed: </span><br>' . $out,
      '#allowed_tags' => ['div', 'table', 'tr', 'td', 'style', 'strong',
        'script', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span',
      ],
    ];

    return $renderArr;
  }

  /**
   * Testing running rclone via php.
   */
  public function rcloneTest() {
    $config_file = '/home/magnarem/rclone/rclone.conf';
    $source = '/var/www/drupal9/files/metsis-dev.local/private/nird/toArchive/5C26E115-C094-49BA-8F40-8CC7FA6E4847';
    $dest = '5C26E115-C094-49BA-8F40-8CC7FA6E4847';
    $dest_base = 'import/test-magnar/';

    $cmd = '/usr/bin/rclone --config=' . $config_file . ' copy ' . $source . ' minio:' . $dest_base . $dest . ' -vv';
    // dpm($cmd);
    $retval = NULL;
    $output = [];
    exec($cmd, $output, $retval);
    // $status = shell_exec($cmd);
    // dpm($retval);
    // dpm($output);
    // dpm($string);
    $this->getLogger('rclone')->debug('<pre><code>' . print_r($output, TRUE) . '</code></pre>');
    // \Drupal::logger('rclone_status')->debug($status);
    return [
      '#type' => 'markup',
      '#markup' => $cmd . '<p>' . $retval . '<p>' . $output,
    ];
  }

}
