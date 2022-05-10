<?php

namespace Drupal\dataset_upload\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Component\Serialization\Json;

/**
 * Plugin implementation of the nird_queue queueworker.
 *
 * @QueueWorker (
 *   id = "nird_upload_queue",
 *   title = @Translation("Upload registered datasets to project area"),
 *   cron = {"time" = 300}
 * )
 */
class DatasetUploadQueue extends QueueWorkerBase
{
    /**
     * {@inheritdoc}
     */
    public function processItem($data)
    {

        /**
         * PSEUDO CODE:
         * Get item.
         * Upload to nird archive
         * if good. change nird_status to uploaded.
         * add data item to nird_email_queue
         * if error. put back to queue and try again.


         */


        $nird = \Drupal::service('dataset_upload.nird_api_client');
        \Drupal::logger('nird')->debug('queue item <pre><code>' . print_r($data, true) . '</code></pre>');
        $status = $nird->getDatasetStatus($data->dataset_id);
        \Drupal::logger('nird')->debug('dataset_status:  <pre><code>' . print_r($status, true) . '</code></pre>');
    }
}
