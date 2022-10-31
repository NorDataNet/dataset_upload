<?php

namespace Drupal\dataset_upload\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Queue\DelayableQueueInterface;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\file\Entity\File;

/**
 * Plugin implementation of the nird_queue queueworker.
 *
 * @QueueWorker (
 *   id = "nird_email_queue",
 *   title = @Translation("Check dataset status, and send email when dataset is available"),
 * )
 */
 //implements DelayableQueueInterface
class DatasetEmailQueue extends QueueWorkerBase
{
    /**
     * {@inheritdoc}
     */
    public function processItem($data)
    {
        \Drupal::logger('nird')->info('nird dataset status check: processing  ' . $data->dataset_id);


        /**
         * PSEUDO CODE:
         * Get item.
         * Get dataset statu from nird api
         * if dataset have status ok and doi
         * send email to user that the dataset are available
         * if dataset do not have status.
         * put back into queue, and process next time.
         */
        $nird = \Drupal::service('dataset_upload.nird_api_client');
        //\Drupal::logger('nird')->debug('queue item <pre><code>' . print_r($data, true) . '</code></pre>');
        $status = $nird->getDatasetStatus($data->dataset_id);
        \Drupal::logger('nird')->debug('dataset_status:  <pre><code>' . print_r($status, true) . '</code></pre>');

        //TEST FOR ALWAYS HAVE A DOI
        $r = rand(0, 10);
        if ($r <= 5) {
            $status['doi'] = 'https://doi.org/10.21203/rs.3.rs-361384/v1';
        }
        //Requeue for 1 hour and check again if we have no DOI
        if (!$status['doi']) {
            \Drupal::logger('nird')->notice('Dataset ' . $data->dataset_id. ' not published yet. delaying processing...');
            throw new DelayedRequeueException(3600);

        //If dataset is published, we send an email.
        } else {
            $user = \Drupal\user\Entity\User::load($data->uid);
            \Drupal::logger('nird')->notice('Got DOI; ' . $status['doi'] . '. Sending email to contributor ' . $user->getEmail());

            $mailManager = \Drupal::service('plugin.manager.mail');
            $module = 'dataset_upload';
            $key = 'dataset_published';
            $to = $user->getEmail();
            $params['message'] = t('Your dataset(s) are now published with DOI: @doi', ['@doi' => $status['doi']]);
            $params['title'] = $data->title;
            $params['id'] = $data->dataset_id;
            //$params['doi'] = 'https://doi.org/10.21203/rs.3.rs-361384/v1';
            //$params['doi'] = $data->doi;

            $langcode = $user->getPreferredLangcode();
            $send = true;

            $result = $mailManager->mail($module, $key, $to, $langcode, $params, null, $send);
            if ($result['result'] !== true) {
                \Drupal::logger('nird')->error(t('There was a problem sending your message and it was not sent.'));
                //If email sending fails..requeue
                throw new DelayedRequeueException(3600);
            } else {
                \Drupal::logger('nird')->notice(t('The email was sent to @user', ['@user' => $to]));

                //Clean up the files.
                if (property_exists($data, 'fid')) {
                    $fid = $data->fid;
                    $file = File::load($fid);
                    if (isset($file)) {
                        $file->delete();
                    }
                }
                $filesystem = \Drupal::service('file_system');
                $filesystem->deleteRecursive($data->path);
                return true;
            }
        }
    }
    /*
        public function delayItem($item, int $delay)
        {
            return true;
        }
    */
}
