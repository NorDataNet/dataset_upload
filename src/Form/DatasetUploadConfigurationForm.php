<?php

namespace Drupal\dataset_upload\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigurationForm.
 *
 * {@inheritdoc}
 */
class DatasetUploadConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dataset_upload.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dataset_upload.admin_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dataset_upload.settings');
    // $form = array();
    $form['account'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Enter NIRD-API account details'),
    // '#tree' => TRUE,
    ];

    $form['account']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t("Enter account username"),
      '#default_value' => $config->get('nird_username'),
    ];
    $form['account']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t("Enter account password"),
      '#default_value' => $config->get('nird_password'),
    ];

    $form['api'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Enter NIRD-API endpoints'),
      // '#tree' => TRUE,
    ];

    $form['api']['nird_api_base_uri'] = [
      '#type'          => 'url',
      '#title'         => $this->t('NIRD API base URI'),
      '#default_value' => $config->get('nird_api_base_uri'),
      '#size' => 60,
    ];

    $form['api']['nird_api_token_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API Token endpoint'),
      '#default_value' => $config->get('nird_api_token_endpoint'),
      '#size' => 35,
    ];

    $form['api']['nird_api_dataset_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API Dataset endpoint'),
      '#default_value' => $config->get('nird_api_dataset_endpoint'),
      '#size' => 35,
    ];

    $form['api']['nird_api_ingest_dataset_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API Ingest dataset endpoint'),
      '#default_value' => $config->get('nird_api_ingest_dataset_endpoint'),
      '#size' => 35,
    ];

    $form['api']['nird_api_ingest_root_path_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API Ingest get root path endpoint'),
      '#default_value' => $config->get('nird_api_ingest_root_path_endpoint'),
      '#size' => 35,
    ];

    $form['api']['nird_api_subject_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API Subject endpoint'),
      '#default_value' => $config->get('nird_api_subject_endpoint'),
      '#size' => 35,
    ];

    $form['api']['nird_api_subject_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API Subject endpoint'),
      '#default_value' => $config->get('nird_api_subject_endpoint'),
      '#size' => 35,
    ];

    $form['api']['nird_api_domain_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API Domain endpoint'),
      '#default_value' => $config->get('nird_api_domain_endpoint'),
      '#size' => 35,
    ];

    $form['api']['nird_api_field_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API Field endpoint'),
      '#default_value' => $config->get('nird_api_field_endpoint'),
      '#size' => 35,
    ];

    $form['api']['nird_api_subfield_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API Subfield endpoint'),
      '#default_value' => $config->get('nird_api_subfield_endpoint'),
      '#size' => 35,
    ];

    $form['api']['nird_api_license_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API License endpoint'),
      '#default_value' => $config->get('nird_api_license_endpoint'),
      '#size' => 35,
    ];
    $form['api']['nird_api_state_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API State endpoint'),
      '#default_value' => $config->get('nird_api_state_endpoint'),
      '#size' => 35,
    ];

    $form['api']['nird_api_category_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API Category endpoint'),
      '#default_value' => $config->get('nird_api_category_endpoint'),
      '#size' => 35,
    ];

    $form['api']['nird_api_person_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API Person endpoint'),
      '#default_value' => $config->get('nird_api_person_endpoint'),
      '#size' => 35,
    ];

    $form['api']['nird_api_organization_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API Organization endpoint'),
      '#default_value' => $config->get('nird_api_organization_endpoint'),
      '#size' => 35,
    ];

    $form['api']['nird_api_dataset_status_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API Dataset status endpoint'),
      '#default_value' => $config->get('nird_api_dataset_status_endpoint'),
      '#size' => 35,
    ];

    $form['api']['nird_api_landing_page_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API Dataset landing page endpoint'),
      '#default_value' => $config->get('nird_api_landing_page_endpoint'),
      '#size' => 35,
    ];

    $form['api']['nird_api_search_org_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API search Organization endpoint'),
      '#default_value' => $config->get('nird_api_search_org_endpoint'),
      '#size' => 35,
    ];

    $form['manifest-wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Ingest dataset manifest config'),
        // '#tree' => TRUE,
    ];
    $form['manifest-wrapper']['manifest_path_config'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Manifest file path'),
      '#description' => $this->t('Provide relative path for where to ingest manifest files. DO NOT add leading slash, but DO ADD trailing slash.'),
      '#default_value' => $config->get('manifest_config_path'),
      '#size' => 35,
    ];

    $form['minio-wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('minIO configuration'),
        // '#tree' => TRUE,
    ];
    $form['minio-wrapper']['minio_rclone_config'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('minIO rclone.conf location'),
      '#description' => $this->t('Provide full path including filename for the rclone.conf file.'),
      '#default_value' => $config->get('minio_rclone_config_path'),
      '#size' => 35,
    ];
    $form['minio-wrapper']['minio_remote_base_path'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('minIO remote base path'),
      '#description' => $this->t('Enter the minIO relative basepath for upload. DO NOT add leading slash, but DO ADD trailing slash'),
      '#default_value' => $config->get('minio_remote_base_path'),
      '#size' => 35,
    ];

    $form['helptext-wrapper']['helptext-upload'] =
        $form['helptext-wrapper'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Helptext and instruction'),
        // '#tree' => TRUE,
        ];
    $form['helptext-wrapper']['helptext-upload'] = [
      '#type'          => 'text_format',
      '#title'         => $this->t('Dataset upload form instructions'),
      '#description' => $this->t('Enter instructions to be shown before uploading dataset.'),
      '#format'        => $config->get('helptext_upload')['format'],
      '#default_value' => $config->get('helptext_upload')['value'],
    ];
    $form['helptext-wrapper']['helptext-dataset'] = [
      '#type'          => 'text_format',
      '#title'         => $this->t('Dataset creation form instructions'),
      '#description' => $this->t('Enter instructions to be shown when filling out the dataset information form.'),
      '#format'        => $config->get('helptext_dataset')['format'],
      '#default_value' => $config->get('helptext_dataset')['value'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    /*
     * Save the configuration
     */
    $config = $this->config('dataset_upload.settings');
    $values = $form_state->getValues();
    // dpm($values);
    $current_pass = $config->get('nird_password');
    $form_pass = $values['password'];

    if ($form_pass === '' && $current_pass !== '') {
      $newPass = $current_pass;
    }
    else {
      $newPass = $form_pass;
    }

    $this->configFactory->getEditable('dataset_upload.settings')
      ->set('nird_username', $values['username'])
      ->set('nird_password', $newPass)
      // ->set('nird_password', $values['password'])
      ->set('nird_api_base_uri', $values['nird_api_base_uri'])
      ->set('nird_api_token_endpoint', $values['nird_api_token_endpoint'])
      ->set('nird_api_dataset_endpoint', $values['nird_api_dataset_endpoint'])
      ->set('nird_api_ingest_dataset_endpoint', $values['nird_api_ingest_dataset_endpoint'])
      ->set('nird_api_subject_endpoint', $values['nird_api_subject_endpoint'])
      ->set('nird_api_domain_endpoint', $values['nird_api_domain_endpoint'])
      ->set('nird_api_field_endpoint', $values['nird_api_field_endpoint'])
      ->set('nird_api_subfield_endpoint', $values['nird_api_subfield_endpoint'])
      ->set('nird_api_license_endpoint', $values['nird_api_license_endpoint'])
      ->set('nird_api_state_endpoint', $values['nird_api_state_endpoint'])
      ->set('nird_api_category_endpoint', $values['nird_api_category_endpoint'])
      ->set('nird_api_person_endpoint', $values['nird_api_person_endpoint'])
      ->set('nird_api_organization_endpoint', $values['nird_api_organization_endpoint'])
      ->set('nird_api_dataset_status_endpoint', $values['nird_api_dataset_status_endpoint'])
      ->set('nird_api_landing_page_endpoint', $values['nird_api_landing_page_endpoint'])
      ->set('nird_api_search_org_endpoint', $values['nird_api_search_org_endpoint'])
      ->set('nird_api_ingest_root_path_endpoint', $values['nird_api_ingest_root_path_endpoint'])

      ->set('manifest_config_path', $values['manifest_path_config'])

      ->set('minio_rclone_config_path', $values['minio_rclone_config'])
      ->set('minio_remote_base_path', $values['minio_remote_base_path'])

      ->set('helptext_upload', $values['helptext-upload'])
      ->set('helptext_dataset', $values['helptext-dataset'])
      ->set('debug_json', $values['debug_json'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
