<?php

namespace Drupal\dataset_upload\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DatasetForm.
 *
 * Form to provide services selection for datasets.
 *
 * @package Drupal\dataset_upload\Form
 */
class DatasetForm extends FormBase {

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->currentUser = $container->get('current_user');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dataset_upload.dataset_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $breed_id = NULL, int $limit = 3) {
    // Get the extracted metadata to prefill the form.
    $metadata = $form_state->get('metadata')[$form_state->get('filename')];
    if ($form_state->has('archived_files')) {
      $metadata = $form_state->get('metadata')[$form_state->get('archived_files')[0]];
    }
    // $metadata = [];
    $prefill = [];
    for ($i = 0; $i < count($metadata); $i++) {
      $key = $metadata[$i][0];
      $prefill += [$key => $metadata[$i][1]];
    }

    $form['#tree'] = TRUE;
    $form['dataset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Dataset information"),
      '#open' => TRUE,
      '#attributes' => [
    // 'class' => ['w3-border-green', 'w3-border'],
  ],

    ];

    /*
     * contributor
     */

    $form['dataset']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Title"),
      '#default_value' => $prefill[' title'],
      '#size' => 120,
      '#required' => TRUE,
      '#disabled' => TRUE,
    ];

    /*
     * description
     */

    $form['dataset']['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Description"),
      '#default_value' => $prefill[' abstract'],
      '#size' => 120,
      '#required' => TRUE,
      '#attributes' => [
        'disabled' => TRUE,
      ],
    ];

    /*
     * state
     */

    $state = $form_state->get('api_state');
    $form['dataset']['state'] = [
      '#type' => 'select',
      '#title' => $this->t("State"),
      '#empty_option' => $this->t('- Select state -'),
      '#options' => array_combine($state, $state),
      '#required' => TRUE,
    ];

    /*
     * category
     */

    $category = $form_state->get('api_category');
    $form['dataset']['category'] = [
      '#type' => 'select',
      '#title' => $this->t("Category"),
      '#empty_option' => $this->t('- Select category -'),
      '#options' => array_combine($category, $category),
      '#required' => TRUE,
    ];

    /* language */
    $form['dataset']['language'] = [
      '#type' => 'select',
      '#title' => $this->t("Language"),
      '#empty_option' => $this->t('- Select language -'),
      '#options' => ['Norwegian' => $this->t('Norwegian'), 'English' => $this->t('English')],
      '#required' => TRUE,
    ];

    /*
     * Licence
     */

    $licences = $form_state->get('api_licences');
    $licence = array_column($licences, 'licence');
    $licence_name = array_column($licences, 'name');
    /*  foreach($lics as $lic ) {
    $licence[] = $licences['licence'];
    }*/
    $form['dataset']['licence'] = [
      '#type' => 'select',
      '#title' => $this->t("Licence"),
      '#empty_option' => $this->t('- Select licence -'),
      '#options' => array_combine($licence, $licence_name),
      '#required' => TRUE,
    ];

    $form['dataset']['licence-info'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="licence-info">',
      '#markup' => '',
      '#suffix' => '</div>',
      '#allowed_tags' => ['div', 'tr' , 'li'],
    ];

    /*
     * created date
     */

    $date_format = 'Y-m-d';
    $time_format = 'H:i:s';
    // dpm($created_date);
    // dpm($created_time);
    // $date_time_format = trim($date_format . ' ' . $time_format);
    // $date_time_input = trim($created_date . ' ' . $created_time);
    // $timezone = $this->currentUser->getTimeZone();
    $form['dataset']['created'] = [
      '#type' => 'date',
      '#title' => $this->t("Created"),
      '#date_date_format' => $date_format,
      '#date_time_format' => $time_format,
    // '#description' => date($date_format, time()),
      '#required' => TRUE,
    ];

    /*
     * publication articles
     */

    $num_articles = $form_state->get('num_articles');
    // dpm('before: ' .$num_articles);.
    if ($num_articles === NULL) {
      $form_state->set('num_articles', 1);
      $num_articles = 1;
    }

    // dpm('after: ' .$num_articles);.
    $form['dataset']['article'] = [
      '#type' => 'container',
    // '#title' => $this->t(),
    // '#prefix' => '<div id="article-wrapper">',
    // '#suffix' => '</div>',
    // '#open' => TRUE,
      '#tree' => TRUE,
      '#attributes' => [
    // 'class' => ['w3-border-green', 'w3-border'],
  ],

    ];
    /*
    $form['num_articles'] = [
    '#type' => 'store',
    //'#value' => 1,
    //'#state'
    ];
     */

    $form['dataset']['article']['publication'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('The publication(s) that describes the dataset.'),
      '#discription' => $this->t('Add publications related to this dataset. The first publication added will be consiedered the primary publication.'),
      '#prefix' => '<div id="publication-wrapper">',
      '#suffix' => '</div>',
          // '#tree' => TRUE,
    ];

    // $form['#tree'] = TRUE;
    // for ($i = 0; $i < $num_articles; $i++) {
    // for ($i = 0; $i < $num_articles; $i++) {
    $form['dataset']['article']['publication']['published'] = [
      '#type' => 'select',
      '#title' => $this->t('Published'),
      '#empty_option' => $this->t('- Select published status -'),
      '#options' => [TRUE => 'Yes', FALSE => 'No'],
    ];
    $form['dataset']['article']['publication']['reference']['doi'] = [
      '#type' => 'url',
      '#title' => $this->t('DOI reference'),
      '#required' => TRUE,
    ];

    if ($num_articles > 1) {
      $form['dataset']['article']['publication']['primary'] = [
        '#type' => 'hidden',
        '#value' => FALSE,
      ];
    }
    else {
      $form['dataset']['article']['primary'] = [
        '#type' => 'hidden',
        '#value' => FALSE,
      ];
    }
    // }
    /*
    $form['dataset']['article']['publication']['actions'] = [
    '#type' => 'actions',
    ];
    if ($num_articles > 1) {
    $form['dataset']['article']['publication']['actions']['remove_article'] = [
    '#type' => 'submit',
    '#value' => $this->t('Remove one'),
    '#submit' => ['::removeCallback'],
    '#ajax' => [
    'callback' => '::addArticleCallback',
    'wrapper' => 'publication-wrapper',
    ],
    ];
    }
     */
    /*
     * contributor
     */

    $form['dataset']['contributor'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Contributor'),
      '#description' => $this->t('The person or group of people that contributed to the archiving of the dataset.'),
      '#tree' => TRUE,
    ];
    $form['dataset']['contributor']['member'] = [
      '#type' => 'container',
    ];

    $form['dataset']['contributor']['uploader'] = [
      '#type' => 'hidden',
      '#value' => TRUE,
    ];

    // dpm('manager persons: ' .$num_manager_person);
    // dpm('manager orgs: ' .$num_manager_org);.
    $form['dataset']['data_manager'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    $form['dataset']['data_manager']['manager'] = [

      '#type' => 'fieldset',
      '#title' => $this->t('Data manager'),
      '#description' => $this->t('The person or organization that are responsible for fielding questions on the maintenance and use of the data. There can be more than one data manager'),
      '#tree' => TRUE,
      '#prefix' => '<div id="manager-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['dataset']['data_manager']['manager']['actions'] = [
      '#type' => 'actions',
    ];
    $form['dataset']['data_manager']['manager']['actions']['add_person'] = [
      '#type' => 'submit',
      '#submit' => ['::addManagerPerson'],
      '#value' => $this->t('Add person'),
      '#ajax' => [
        'callback' => '::addManagerCallback',
        'wrapper' => 'manager-wrapper',
      ],
    ];
    $form['dataset']['data_manager']['manager']['actions']['add_org'] = [
      '#type' => 'submit',
      '#submit' => ['::addManagerOrg'],
      '#value' => $this->t('Add organization'),
      '#ajax' => [
        'callback' => '::addManagerCallback',
        'wrapper' => 'manager-wrapper',
      ],
    ];

    /*
     * rights holder
     */

    $form['dataset']['rights_holder'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Rights holder'),
      '#description' => $this->t('The person or organization that hold the rights to the data (or can act as the contact person).'),
      '#tree' => TRUE,
    ];
    $form['dataset']['rights_holder']['holder'] = [
      '#type' => 'container',
    ];

    /*
     * creator
     */

    $form['dataset']['creator'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Creator'),
      '#description' => $this->t('The person or organization that created the dataset'),
      '#tree' => TRUE,
    ];
    $form['dataset']['creator']['creator'] = [
      '#type' => 'container',
    ];

    /*
     * subject
     */
    $subjects = $form_state->get('api_subjects');
    // dpm($subjects);
    $domains = array_unique(array_column($subjects, 'domain'));
    // $fields = array_search('Humanities' )
    $form['dataset']['subject'] = [
      '#type' => 'select',
      '#title' => $this->t("Subject"),
      '#empty_option' => $this->t('- Select subject -'),
      '#options' => array_combine($domains, $domains),
    ];

    /*
     * submit actions
     */
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Confirm and upload dataset.'),
    // '#submit' => ['::confirmNIRD'],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel submission'),
      '#submit' => ['::cancelSubmission'],
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
