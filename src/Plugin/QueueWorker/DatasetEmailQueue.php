<?php

namespace Drupal\dataset_upload\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Component\Serialization\Json;

/**
 * Plugin implementation of the nird_queue queueworker.
 *
 * @QueueWorker (
 *   id = "nird_email_queue",
 *   title = @Translation("Check dataset status, and send email when dataset is available"),
 *   cron = {"time" = 300}
 * )
 */
class DatasetEmailQueue extends QueueWorkerBase
{
    /**
     * {@inheritdoc}
     */
    public function processItem($data)
    {

      /**
       * PSEUDO CODE:
       * Get item.
       * Get dataset statu from nird api
       * if dataset have status ok and doi
       * send email to user that the dataset are available
       * if dataset do not have status.
       * put back into queue, and process next time.
       */
        $mailFactory = \Drupal::service('plugin.manager.mail');
        $nird = \Drupal::service('dataset_upload.nird_api_client');
        \Drupal::logger('nird')->debug('queue item <pre><code>' . print_r($data, true) . '</code></pre>');
        $status = $nird->getDatasetStatus($data->dataset_id);
        \Drupal::logger('nird')->debug('dataset_status:  <pre><code>' . print_r($status, true) . '</code></pre>');
    }
}
