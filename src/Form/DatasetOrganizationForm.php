<?php

namespace Drupal\dataset_upload\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DatasetOrganizationForm.
 *
 * Form to provide services selection for datasets.
 *
 * @package Drupal\dataset_upload\Form
 */
class DatasetOrganizationForm extends FormBase {

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
    return 'dataset_upload.services_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $breed_id = NULL, int $limit = 3) {

    $form['longname'] = [
      '#type' => 'textfield',
      '#title' => $this
        ->t('Long name'),
    // '#default_value' => $form_state->getValue(['dataset','data_manager','manager',$i,'longname']),
    ];

    $form['shortname'] = [
      '#type' => 'textfield',
      '#title' => $this
        ->t('Short name'),
      '#default_value' => $form_state->getValue(['dataset', 'data_manager', 'manager', $i, 'shortname']),
    ];
    $form['contactemail'] = [
      '#type' => 'email',
      '#title' => $this
        ->t('Contact email'),
      '#default_value' => $form_state->getValue(['dataset', 'data_manager', 'manager', $i, 'contactemail']),
    ];
    $form['homepage'] = [
      '#type' => 'url',
      '#title' => $this
        ->t('Homepage'),
      '#default_value' => $form_state->getValue(['dataset', 'data_manager', 'manager', $i, 'homepage']),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
