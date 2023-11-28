<?php

namespace Drupal\dataset_upload\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MetadataTableForm.
 *
 * Form to provide services selection for datasets.
 *
 * @package Drupal\dataset_upload\Form
 */
class MetadataTableForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Return new static(
    // $container->get('dataset_upload.client'),
    // $container->get('dataset_upload.breed_factory')
    // );.
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dataset_upload.metadata_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $breed_id = NULL, int $limit = 3) {
    $metadata = $form_state->get('metadata');
    $form['validation']['mmd_check'] = [
      '#type' => 'markup',
      '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-green w3-pale-green w3-padding-16" id="mmd-message">',
      '#type' => 'markup',
      '#markup' => '<span>Your uploaded dataset(s) has the metadata as reported in the following table. Please make sure they are correct before
  confirming your submission. If the metadata are not correct, cancel your submission, correct your information and proceed with a new submission.</span>',
      '#suffix' => '</div>',
      '#allowed_tags' => ['div', 'span'],

    ];

    $form['validation']['extracted_metadata'] = [
      '#type' => 'details',
      '#title' => t("Show extracted metadata"),
      '#open' => TRUE,
    ];

    foreach ($metadata as $key => $value) {
      $form['validation']['extracted_metadata'][$key] = [
        '#type' => 'table',
        '#caption' => 'Extracted metadata for ' . $key,
        '#header' => ['Metadata Key', 'Metadata Value'],
        '#rows' => $value,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
