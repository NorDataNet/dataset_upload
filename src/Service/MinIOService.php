<?php

namespace Drupal\dataset_upload\Service;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class MinIOService
 *
 * @package Drupal\dataset_upload\Service
 */
class MinIOService
{
    /**
     * Constants for config and base destination
     */
    public const CONFIG_FILE = '/home/ubuntu/rclone/rclone.conf';
    public const BASE_DEST = 'import/test-magnar/';

    /**
     * Private state variables
     */

    //Status message
    private $message;

    /**
     * Use rclone and minio to upload registered datasets to NIRD Archive.
     *
     * @param string $source_path
     * @param string $id
     *
     * @return bool $status
     */

    public function upload(string $source_path, string $id)
    {
        $out = null;
        $status = null;

        $cmd = '/usr/bin/rclone --config='.MinIOService::CONFIG_FILE . ' copy '.$source_path . ' minio:'.MinIOService::BASE_DEST.$id;
        //Upload the file(s)
        exec($cmd, $out, $status);
        \Drupal::logger('dataset_upload')->debug('Rclone: <pre><code>' . print_r($out, true) . '</code></pre>');
        \Drupal::logger('dataset_upload')->debug('Rclone CMD status: '. $status);


        if ($status !== 0) {
            $this->message = $out;
            return false;
        }
        return true;
    }

    public function getMessage()
    {
        return $this->message;
    }
}
