<?php

namespace Drupal\dataset_upload\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\DelayedRequeueException;

/**
 * Plugin implementation of the nird_queue queueworker.
 *
 * @QueueWorker (
 *   id = "nird_upload_queue",
 *   title = @Translation("Upload registered datasets to project area"),
 *   delay = { "time" = 3600 }
 * )
 */
class DatasetUploadQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    /*
     * PSEUDO CODE:
     * Get item.
     * Upload to nird archive
     * if good. change nird_status to uploaded.
     * add data item to nird_email_queue
     * if error. put back to queue and try again.
     */

    \Drupal::logger('nird')->info('nird upload: processing  ' . $data->dataset_id);

    // Get the minio upload service.
    $minio = \Drupal::service('dataset_upload.minio_service');

    // Get the module config.
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->get('dataset_upload.settings');
    $base_dest = $config->get('manifest_config_path');
    // $nird = \Drupal::service('dataset_upload.nird_api_client');
    // \Drupal::logger('nird')->debug('queue item <pre><code>' . print_r($data, true) . '</code></pre>');
    // $status = $nird->getDatasetStatus($data->dataset_id);
    // \Drupal::logger('nird')->debug('dataset_status:  <pre><code>' . print_r($status, true) . '</code></pre>');

    // Upload the dataset(s)
    $status = $minio->upload($data->path, $data->dataset_id, $data->root_path);
    \Drupal::logger('nird')->debug('minio rclone status: ' . $status);

    // If upload is success we send this item to the mailQueue for further processing.
    if ($status) {
      \Drupal::logger('nird')->info('Upload success!. add item ' . $data->dataset_id . ' to emailqueue.');
      $queue = \Drupal::service('queue')->get('nird_email_queue');
      $data->nird_process['uploaded'] = 'SUCCESS';

      $data->nird_status = 'uploaded';
      // Call the ingest.
      $nirdApiClient = \Drupal::service('dataset_upload.nird_api_client');
      $ingestStatus = $nirdApiClient->ingestDataset([
        'dataset_id' => $data->dataset_id,
        'paths' => [[
         'file_path' =>  $data->root_path . '/' . $base_dest . $data->dataset_id,
	]],
      ]);
      \Drupal::logger('nird')->info('paths: ' . $data->root_path . '/' . $base_dest . $data->dataset_id);
      \Drupal::logger('nird')->info('<pre><code>' . print_r($ingestStatus, TRUE) . '</code></pre>');
      $queue->createItem($data);
    }
    else {
      $data->nird_process['uploaded'] = 'FAILED';
      \Drupal::logger('nird')->error('minio rclone failed: <pre><code>' . print_r($minio->getMessage(), TRUE) . '</code></pre>');
      throw new DelayedRequeueException(240);
      
    }
  }
 /*
  * {@inheritdoc}
  */
  public function delayItem($item, int $delay) {
    // if(isset())
    return TRUE;
  }


}
