<?php

namespace Drupal\dataset_upload\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DatasetPersonForm.
 *
 * Form to provide services selection for datasets.
 *
 * @package Drupal\dataset_upload\Form
 */
class DatasetPersonForm extends FormBase {

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
    return 'dataset_upload.person_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $breed_id = NULL, int $limit = 3) {
    $form['firstname'] = [
      '#type' => 'textfield',
      '#title' => $this
        ->t('First name'),
      // '#default_value' => $this->currentUser->getAccountName(),
    ];

    $form['lastname'] = [
      '#type' => 'textfield',
      '#title' => $this
        ->t('Last name'),
        // '#default_value' => $this->currentUser->getDisplayName(),
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this
        ->t('Email'),
        // '#default_value' => $this->currentUser->getEmail(),
    ];
    $form['federatedid'] = [
      '#type' => 'number',
      '#title' => $this
        ->t('Federated ID'),
        // '#default_value' => $this->currentUser->id(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
