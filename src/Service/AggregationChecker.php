<?php

namespace Drupal\dataset_upload\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;

/**
 * Class AggregationChecker.
 *
 * @package Drupal\dataset_upload\Service
 */
class AggregationChecker {
  /**
   * Private state variables.
   */

  /**
   * Status message.
   */
  private $message;

  /**
   * Use agg_checker.py to check if datasets can be aggregated.
   *
   * @param string $datasets
   * @param string $variable
   *
   * @return bool $status
   */
  public function check(string $datasets, string $variable) {
    $out_agg = NULL;
    $status_agg = NULL;
    // Check dimensions, variables names and attributes to allow for aggregation.
    exec('/usr/local/bin/agg_checker.py ' . $datasets . ' ' . $variable, $out_agg, $status_agg);
    // dpm('/usr/local/bin/agg_checker.py '.$datasets.' '.$variable);
    // ker.py ran with status: " .$status_agg);
    // \Drupal::logger('dataset_upload')->debug('<pre><code>' . print_r($out_agg, true) . '</code></pre>');.

    $fail_agg = FALSE;
    $msg_agg = [];
    // Build the message with only the Fail prints from the agg_checker.py.
    foreach ($out_agg as $line) {
      if (strpos($line, 'Fail') !== FALSE) {
        $fail_agg = TRUE;
        array_push($msg_agg, $line);
      }
    }
    // \Drupal::messenger()->addMessage("agg_checker.py fail_agg: " .$fail_agg);
    // \Drupal::logger('dataset_upload')->debug('<pre><code>' . print_r($msg_agg, true) . '</code></pre>');

    $messages = [];
    // agg_checker.py exit with status 0, but gives Fail messages, i.e. the datasets are not suitable for aggregation.
    if ($fail_agg == TRUE) {
      $messages[] = [
        '#type' => 'markup',
        '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-red w3-pale-red w3-padding-16">',
        '#suffix' => '</div>',
        '#markup' => '<span><em>Your datasets cannot be aggregated. Check suggestions below:<br></em></span>',
        '#allowed_tags' => ['div', 'br', 'tr', 'td', 'style', 'strong', 'script', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span'],
      ];
      // $output = array_slice($msg_agg, 2);
      $messages[] = [
        '#type' => 'markup',
        '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-yellow w3-pale-yellow w3-padding-16">',
        '#suffix' => '</div>',
        '#markup' => '<span>' . implode("<br>", $msg_agg) . '</span>',
        '#allowed_tags' => ['div', 'br', 'tr', 'td', 'style', 'strong', 'script', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span'],
      ];
      $this->message = $messages;
      return FALSE;
    }
    // agg_checker.py exit with status not 0, i.e. it could not be run.
    if ($status_agg !== 0) {
      $messages[] = [
        '#type' => 'markup',
        '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-red w3-pale-red w3-padding-16">',
        '#suffix' => '</div>',
        '#markup' => "<span><em>The aggregation validation checker could not be run. Please take contact using the contact form</em></span>",
        '#allowed_tags' => ['div', 'br', 'tr', 'td', 'style', 'strong', 'script', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span'],
      ];
      $messages[] = [
        '#type' => 'markup',
        '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-red w3-pale-red w3-padding-16">',
        '#suffix' => '</div>',
        '#markup' => '<span><em>agg_checker.py ran with status: ' . $status_agg . ' and output: ' . implode(" ", $out_agg) . '</em></span>',
        '#allowed_tags' => ['div', 'br', 'tr', 'td', 'style', 'strong', 'script', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span'],
      ];
      $this->message = $messages;
      return FALSE;
    }
    return TRUE;
  }

  /**
   *
   */
  public function getMessage() {
    return $this->message;
  }

}
