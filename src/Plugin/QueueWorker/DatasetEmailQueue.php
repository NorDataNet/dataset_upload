<?php

namespace Drupal\dataset_upload\Plugin\QueueWorker;

use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Plugin implementation of the nird_queue queueworker.
 *
 * @QueueWorker (
 *   id = "nird_email_queue",
 *   title = @Translation("Check dataset status, and send email when dataset is available"),
 *   delay = { "time" = 3600},
 *   cron = {"time" = 60}
 * )
 */
class DatasetEmailQueue extends QueueWorkerBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    \Drupal::logger('nird')->info('nird dataset status check: processing  ' . $data->dataset_id);

    /*
     * PSEUDO CODE:
     * Get item.
     * Get dataset statu from nird api
     * if dataset have status ok and doi
     * send email to user that the dataset are available
     * if dataset do not have status.
     * put back into queue, and process next time.
     */
    $nird = \Drupal::service('dataset_upload.nird_api_client');
    $status = $nird->getDatasetStatus($data->dataset_id);
    \Drupal::logger('nird')->debug('dataset_status:  <pre><code>' . print_r($status, TRUE) . '</code></pre>');

    // TEST FOR ALWAYS HAVE A DOI
    // $r = rand(0, 10);
    // if ($r <= 5) {
    // $status['doi'] = 'https://doi.org/10.21203/rs.3.rs-361384/v1';
    // }
    // Requeue for 1 hour and check again if we have no DOI.
    if (!isset($status['doi'])) {
      \Drupal::logger('nird')->notice('Dataset ' . $data->dataset_id . ' not published yet. delaying processing...');
      $data->nird_process['published'] = 'NO';
      throw new DelayedRequeueException();
      // Throw new RequeueException();
      // If dataset is published, we send an email.
    }
    else {
      $user = User::load($data->uid);
      \Drupal::logger('nird')->info('Got DOI; ' . $status['doi'] . '. Sending email to contributor ' . $user->getEmail());

      $data->nird_process['published'] = 'YES';

      $mailManager = \Drupal::service('plugin.manager.mail');
      $module = 'dataset_upload';
      $key = 'dataset_published';
      $to = $user->getEmail();
      $params['message'] = $this->t('Your dataset(s) are now published with DOI: @doi', ['@doi' => $status['doi']]);
      $params['title'] = $data->title;
      $params['id'] = $data->dataset_id;
      // $params['doi'] = 'https://doi.org/10.21203/rs.3.rs-361384/v1';
      $params['doi'] = $status['doi'];

      $langcode = $user->getPreferredLangcode();
      $send = TRUE;

      $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
      if ($result['result'] !== TRUE) {
        \Drupal::logger('nird')->error($this->t('There was a problem sending your message and it was not sent.'));
        $data->nird_process['email sent'] = 'FAIL';

        // If email sending fails..requeue.
        throw new DelayedRequeueException();
        // Throw new RequeueException();
      }
      else {
        \Drupal::logger('nird')->info($this->t('The email was sent to @user', ['@user' => $to]));
        $data->nird_process['email sent'] = 'SUCCESS';

        // Clean up the files.
        if (property_exists($data, 'fid')) {
          $fid = $data->fid;
          $file = File::load($fid);
          if (isset($file)) {
            $file->delete();
          }
        }
        $filesystem = \Drupal::service('file_system');
        $filesystem->deleteRecursive($data->path);
        return TRUE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delayItem($item, int $delay) {
    // if(isset())
    return TRUE;
  }

}
