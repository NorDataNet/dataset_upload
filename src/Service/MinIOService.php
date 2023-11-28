<?php

namespace Drupal\dataset_upload\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;

/**
 * Class MinIOService.
 *
 * @package Drupal\dataset_upload\Service
 */
class MinIOService {
  /**
   * Constants for config and base destination.
   */
  public const CONFIG_FILE = '/home/ubuntu/rclone/rclone.conf';
  public const BASE_DEST = 'import/test-magnar/';

  /**
   * Private state variables.
   */

  /**
   * Status message.
   */
  private $message;

  /**
   * Use rclone and minio to upload registered datasets to NIRD Archive.
   *
   * @param string $source_path
   * @param string $id
   * @param string $root_path
   *
   * @return bool $status
   */
  public function upload(string $source_path, string $id, string $root_path) {

    /**
      * Get the minIO base destination from the config
      */

    $config_factory = \Drupal::configFactory();
    $config = $config_factory->get('dataset_upload.settings');
    $base_dest = $config->get('minio_remote_base_path');
    $config_path = $config->get('minio_rclone_config_path');
    $out = NULL;
    $status = NULL;

    $cmd = '/usr/bin/rclone --config=' . $config_path . ' copy ' . $source_path . ' minio:' . $base_dest . $id;
    \Drupal::logger('dataset_upload')->info('rclone cmd: ' . $cmd);
    // Upload the file(s)
    exec($cmd, $out, $status);
    // \Drupal::logger('dataset_upload')->debug('Rclone: <pre><code>' . print_r($out, true) . '</code></pre>');
    \Drupal::logger('dataset_upload')->info('Rclone CMD status: ' . $status);

    if ($status !== 0) {
      $this->message = $out;
      return FALSE;
    }
    /*$statusIngest = $this->nirdApiClient->ingestDataset(['dataset_id' => $id,
    [
    'paths' => $root_path .'/'.$base_dest.$id,
    ],
    ]
    ]);
    \Drupal::logger('dataset_upload')->debug('Ingest status: '. $statusIngest);*/
    else {
      return TRUE;
    }
  }

  /**
   *
   */
  public function getMessage() {
    return $this->message;
  }

}
