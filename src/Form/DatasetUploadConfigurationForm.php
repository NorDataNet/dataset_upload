<?php
/*
 *
 * @file
 * Contains \Drupal\dataset_upload\DatasetUploadConfigurationForm
 *
 * Form NIRD configuration
 *
 */
namespace Drupal\dataset_upload\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;

/*
 *  * Class ConfigurationForm.
 *
 *  {@inheritdoc}
 *
 *   */
class DatasetUploadConfigurationForm extends ConfigFormBase {

  /*
   * {@inheritdoc}
  */
  protected function getEditableConfigNames() {
    return [
      'dataset_upload.settings',
      ];
  }

  /*
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dataset_upload.admin_config_form';
  }

  /*
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dataset_upload.settings');
    //$form = array();


    $form['account'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Enter NIRD-API account details'),
      '#tree' => TRUE,
      );

    $form['account']['usernaem'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#description' => t("Enter account username"),
      '#default_value' => $config->get('nird_username'),
    );
    $form['account']['password'] = array(
      '#type' => 'password',
      '#title' => t('Password'),
      '#description' => t("Enter account password"),
      '#default_value' => $config->get('nird_password'),
    );


      $form['api'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Enter NIRD-API endpoints'),
        '#tree' => TRUE,
        );

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

    $form['api']['nird_api_subject_endpoint'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('NIRD API Subject endpoint'),
        '#default_value' => $config->get('nird_api_dataset_endpoint'),
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
            '#default_value' => $config->get('nird_api_state_endpoint'),
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
            '#default_value' => $config->get('nird_api_state_endpoint'),
          '#size' => 35,
        ];


    return parent::buildForm($form, $form_state);
 }

  /*
   * {@inheritdoc}
   *
   * NOTE: Implement form validation here
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

return parent::validateForm($form, $form_state);
  }

  /*
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    /**
     * Save the configuration
    */
    $values = $form_state->getValues();

    $this->configFactory->getEditable('dataset_upload.settings')
      ->set('nird_username', $values['nird_username'])
      ->set('nird_password', $values['nird_password'])
      ->set('nird_api_base_uri', $values['nird_api_base_uri'])
      ->set('nird_api_token_endpoint', $values['nird_api_token_endpoint'])
      ->set('nird_api_dataset_endpoint', $values['nird_api_dataset_endpoint'])
      ->set('nird_api_subject_endpoint', $values['nird_api_subject_endpoint'])
      ->set('nird_api_license_endpoint', $values['nird_api_license_endpoint'])
      ->set('nird_api_state_endpoint', $values['nird_api_state_endpoint'])
      ->set('nird_api_category_endpoint', $values['nird_api_category_endpoint'])
      ->set('nird_api_person_endpoint', $values['nird_api_person_endpoint'])
      ->set('nird_api_organization_endpoint', $values['nird_api_organization_endpoint'])

      ->save();
    parent::submitForm($form, $form_state);
  }
}
