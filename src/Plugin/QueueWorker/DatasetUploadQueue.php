<?php

namespace Drupal\dataset_upload\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\DelayableQueueInterface;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Component\Serialization\Json;

/**
 * Plugin implementation of the nird_queue queueworker.
 *
 * @QueueWorker (
 *   id = "nird_upload_queue",
 *   title = @Translation("Upload registered datasets to project area"),
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

        \Drupal::logger('nird')->info('nird upload: processing  ' . $data->dataset_id);

        //Get the minio upload service
        $minio = \Drupal::service('dataset_upload.minio_service');

        //$nird = \Drupal::service('dataset_upload.nird_api_client');
        //\Drupal::logger('nird')->debug('queue item <pre><code>' . print_r($data, true) . '</code></pre>');
        //$status = $nird->getDatasetStatus($data->dataset_id);
        //\Drupal::logger('nird')->debug('dataset_status:  <pre><code>' . print_r($status, true) . '</code></pre>');

        //Upload the dataset(s)
        $status = $minio->upload($data->path, $data->dataset_id);
        \Drupal::logger('nird')->debug('minio rclone status: ' . $status);


        //If upload is success we send this item to the mailQueue for further processing
        if ($status) {
            \Drupal::logger('nird')->notice('Upload sucess. add item to emailqueue');
            $queue = \Drupal::service('queue')->get('nird_email_queue');
            $data->nird_status = 'uploaded';
            $queue->createItem($data);
        } else {
            \Drupal::logger('nird')->debug('minio rclone failed: ' . $minio->getMessage());
        }
    }
}
