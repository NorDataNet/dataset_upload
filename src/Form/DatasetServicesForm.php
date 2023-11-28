<?php

namespace Drupal\dataset_upload\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DatasetServicesForm.
 *
 * Form to provide services selection for datasets.
 *
 * @package Drupal\dataset_upload\Form
 */
class DatasetServicesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Return new static(
    // $container->get('dataset_upload.client'),
    // container->get('dataset_upload.breed_factory')
    // );.
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dataset_upload.services_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $breed_id = NULL, int $limit = 3) {

    if ($form_state->has('archived_files')) {
      $form['aggregation']['message'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('You have selected a multiple file upload. Your files will be aggregated on the server. <br>
         Declare the variable on which you wish to aggregate your netcdf files. <br>
         This field MUST match the exact name of the variables in your netCDF files'),
      ];
    }

    $form['services'] = [
      '#type' => 'container',
    ];
    $form['services']['select_conf']['dataset_type'] = [
      '#title' => $this->t('Select the type of dataset you are uploading and the services you would like to activate for your dataset'),
      '#type' => 'radios',
      '#required' => TRUE,
      '#options' => [
        'gridded_data' => $this->t('Gridded data'),
        'time_seriesg' => $this->t('Time series gridded data'),
        'time_series' => $this->t('Time series not gridded data'),
      ],
      '#default_value' => 'gridded_data',
    ];

    /* Here we just upload a tgz that will have to be uncompressed and validated. */
    $form['services']['select_conf']['gridded_data'] = [
      '#title' => $this->t('Services'),
      '#type' => 'checkboxes',
      '#options' => [
        'https' => $this->t('Download of dataset (http(s))'),
        'opendap' => $this->t('OPeNDAP (Remote access)'),
        'wms' => $this->t('WMS client (Web Map Server)'),
      ],
      '#default_value' => ['https', 'opendap', 'wms'],
      '#states' => [
        'visible' => [
          ':input[name="dataset_type"]' => ['value' => 'gridded_data'],
        ],
      ],
    ];

    $form['services']['select_conf']['time_seriesg'] = [
      '#title' => $this->t('Services'),
      '#type' => 'checkboxes',
      '#options' => [
        'https' => $this->t('Download of dataset (http(s))'),
        'opendap' => $this->t('OPeNDAP (Remote access)'),
        'wms' => $this->t('WMS client (Web Map Server)'),
      ],
      '#default_value' => ['https', 'opendap', 'wms'],
      '#states' => [
        'visible' => [
          ':input[name="dataset_type"]' => ['value' => 'time_seriesg'],
        ],
      ],
    ];

    $form['services']['select_conf']['time_series'] = [
      '#title' => $this->t('Services'),
      '#type' => 'checkboxes',
      '#options' => [
        'https' => $this->t('Download of dataset (http(s))'),
        'opendap' => $this->t('OPeNDAP (Remote access)'),
      ],
      // '#attributes' => array('checked' => 'unchecked'),
      '#default_value' => ['https', 'opendap'],
      '#states' => [
        'visible' => [
          ':input[name="dataset_type"]' => ['value' => 'time_series'],
        ],
      ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Confirm'),
      '#submit' => ['::confirmServices'],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel submission'),
      '#submit' => ['::cancelSubmission'],
    ];

    // $form_state->setValue('metadata', $metadata);
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
