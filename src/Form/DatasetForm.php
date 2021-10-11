<?php

namespace Drupal\dataset_upload\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\dataset_validation\Form\DatasetPersonForm;
use Drupal\dataset_validation\Form\DatasetOrganizationForm;
use Drupal\dataset_validation\Form\DatasetCreatorForm;

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
 * @var AccountProxyInterface $currentUser
 */
  protected $currentUser;


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
//    $instance->archiverManager = $container->get('plugin.manager.archiver');
    $instance->currentUser = $container->get('current_user');
//    $instance->nirdApiClient = $container->get('dataset_upload.nird_api_client');
//    $instance->ncToMmd = $container->get('metsis_lib.nc_to_mmd');
//    $instance->aggChecker = $container->get('dataset_upload.aggregation_checker');

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
    //Get the extracted metadata to prefill the form.
    $metadata = $form_state->get('metadata')[$form_state->get('filename')];
    if($form_state->has('archived_files')) {
      $metadata = $form_state->get('metadata')[$form_state->get('archived_files')[0]];
    }
    //$metadata = [];
    $prefill = [];
    for ($i = 0; $i < sizeof($metadata); $i++) {
        $key =$metadata[$i][0];
        $prefill+= [$key=>$metadata[$i][1]];
    }

    $form['#tree'] = true;
    $form['dataset'] = [
  '#type' => 'fieldset',
  '#title' => t("Dataset information"),
  '#open' => true,
  '#attributes' => [
    //'class' => ['w3-border-green', 'w3-border'],
  ],

];


    /**
     * contributor
     */


    $form['dataset']['title'] = [
  '#type' => 'textfield',
  '#title' => t("Title"),
  '#default_value' => $prefill[' title'],
  '#size' => 120,
  '#required' => true,
  '#disabled' => true,
];

    /**
     * description
     */


    $form['dataset']['description'] = [
  '#type' => 'textfield',
  '#title' => t("Description"),
  '#default_value' => $prefill[' abstract'],
  '#size' => 120,
  '#required' => true,
  '#attributes' => [
    'disabled' => true,
  ],
];

    /**
     * state
     */


    $state = $form_state->get('api_state');
    $form['dataset']['state'] = [
      '#type' => 'select',
      '#title' => t("State"),
      '#empty_option' => t('- Select state -'),
      '#options' => array_combine($state, $state),
      '#required' => true,
    ];


    /**
     * category
     */

    $category = $form_state->get('api_category');
    $form['dataset']['category'] = [
  '#type' => 'select',
  '#title' => t("Category"),
  '#empty_option' => t('- Select category -'),
  '#options' => array_combine($category, $category),
  '#required' => true,
];



    /**
     * language
     */

    $form['dataset']['language'] = [
  '#type' => 'select',
  '#title' => t("Language"),
  '#empty_option' => t('- Select language -'),
  '#options' => ['Norwegian'=>'Norwegian', 'English'=>'English'],
  '#required' => true,
];

/**
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
'#title' => t("Licence"),
'#empty_option' => t('- Select licence -'),
'#options' => array_combine($licence, $licence_name),
'#required' => true,
//    '#ajax' => [
//      'callback' => '::licenceSelectCallback',
//      'wrapper' => 'licence-info',
//  ],
];

$form['dataset']['licence-info'] = [
'#type' => 'markup',
'#prefix' => '<div id="licence-info">',
'#markup' => '',
'#suffix' => '</div>',
'#allowed_tags' => ['div', 'tr' ,'li'],
];


    /**
     * created date
     */


    $date_format = 'Y-m-d';
    $time_format = 'H:i:s';
    //$created_date = explode('T', $prefill[' last_metadata_update update datetime'])[0];
    //$created_time = substr(explode('T', $prefill[' last_metadata_update update datetime'])[1], 0, -1);
    //dpm($created_date);
    //dpm($created_time);
    //$date_time_format = trim($date_format . ' ' . $time_format);
    //$date_time_input = trim($created_date . ' ' . $created_time);
    //$timezone = $this->currentUser->getTimeZone();
    $form['dataset']['created'] = [
  '#type' => 'date',
  '#title' => t("Created"),
  //'#default_value' => DrupalDateTime::createFromFormat($date_time_format, $date_time_input, $timezone),
  '#date_date_format' => $date_format,
 '#date_time_format' => $time_format,
 //'#description' => date($date_format, time()),
  '#required' => true,
];





    /**
     * publication articles
     */


    $num_articles = $form_state->get('num_articles');
    //dpm('before: ' .$num_articles);

    if ($num_articles === null) {
        $form_state->set('num_articles', 1);
        $num_articles = 1;
    }

    //dpm('after: ' .$num_articles);

    $form['dataset']['article'] = [
  '#type' => 'container',
  //'#title' => t(),
  //'#prefix' => '<div id="article-wrapper">',
  //'#suffix' => '</div>',
  //'#open' => TRUE,
  '#tree' => true,
  '#attributes' => [
  //'class' => ['w3-border-green', 'w3-border'],
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
          '#title' => t('The publication(s) that describes the dataset.'),
          '#discription' => t('Add publications related to this dataset. The first publication added will be consiedered the primary publication.'),
          '#prefix' => '<div id="publication-wrapper">',
          '#suffix' => '</div>',
          //'#tree' => TRUE,
        ];

    //$form['#tree'] = TRUE;
    //for ($i = 0; $i < $num_articles; $i++) {
    //for ($i = 0; $i < $num_articles; $i++) {
        $form['dataset']['article']['publication']['published'] = [
  '#type' => 'select',
  '#title' => t('Published'),
  '#empty_option' => t('- Select published status -'),
  '#options' => [true =>'Yes', false => 'No'],
];
        $form['dataset']['article']['publication']['reference']['doi'] = [
  '#type' => 'url',
  '#title' => t('DOI reference'),
  '#required' => true,
];

        if ($num_articles > 1) {
            $form['dataset']['article']['publication']['primary'] = [
  '#type' => 'hidden',
  '#value' => false,
];
        } else {
            $form['dataset']['article']['primary'] = [
  '#type' => 'hidden',
  '#value' => false,
];
        }
    //}

    /*
    $form['dataset']['article']['publication']['actions'] = [
  '#type' => 'actions',
];
    $form['dataset']['article']['publication']['actions']['add_article'] = array(
              '#type' => 'submit',
              '#value' => t('Add another'),
              '#submit' => ['::addArticle'],
              '#ajax' => [
                   'callback' => '::addArticleCallback',
                   'wrapper' => 'publication-wrapper',


              ],
          );

    if ($num_articles > 1) {
        $form['dataset']['article']['publication']['actions']['remove_article'] = [
    '#type' => 'submit',
    '#value' => t('Remove one'),
    '#submit' => ['::removeCallback'],
    '#ajax' => [
      'callback' => '::addArticleCallback',
      'wrapper' => 'publication-wrapper',
    ],
  ];
    }
*/
    /**
     * contributor
     */

    $form['dataset']['contributor'] = [
  '#type' => 'fieldset',
  '#title' => t('Contributor'),
  '#description' => t('The person or group of people that contributed to the archiving of the dataset.'),
  '#tree' => true,
];
  $form['dataset']['contributor']['member'] = [
    '#type' => 'container',
  ];

$form['dataset']['contributor']['uploader'] = [
'#type' => 'hidden',
'#value' => true,
];


//           dpm('manager persons: ' .$num_manager_person);
  //         dpm('manager orgs: ' .$num_manager_org);


          $form['dataset']['data_manager'] = [
              '#type' => 'container',
              '#tree' =>   true,
      ];

      $form['dataset']['data_manager']['manager'] = [

      '#type' => 'fieldset',
      '#title' => t('Data manager'),
      '#description' => t('The person or organization that are responsible for fielding questions on the maintenance and use of the data. There can be more than one data manager'),
      '#tree' => true,
      '#prefix' => '<div id="manager-wrapper">',
      '#suffix' => '</div>',
    ];






              $form['dataset']['data_manager']['manager']['actions'] = [
                  '#type' => 'actions'
              ];
              $form['dataset']['data_manager']['manager']['actions']['add_person'] = [
                  '#type' => 'submit',
                  '#submit' => ['::addManagerPerson'],
                  '#value' => t('Add person'),
                  '#ajax' => [
                       'callback' => '::addManagerCallback',
                       'wrapper' => 'manager-wrapper',
                     ],
              ];
              $form['dataset']['data_manager']['manager']['actions']['add_org'] = [
                  '#type' => 'submit',
                  '#submit' => ['::addManagerOrg'],
                  '#value' => t('Add organization'),
                  '#ajax' => [
                       'callback' => '::addManagerCallback',
                       'wrapper' => 'manager-wrapper',
                     ],
              ];

                /**
                 * rights holder
                 */

                $form['dataset']['rights_holder'] = [
              '#type' => 'fieldset',
              '#title' => t('Rights holder'),
              '#description' => t('The person or organization that hold the rights to the data (or can act as the contact person).'),
              '#tree' => true,
            ];
            $form['dataset']['rights_holder']['holder'] = [
              '#type' => 'container'
            ];


      /**
       * creator
       */

      $form['dataset']['creator'] = [
    '#type' => 'fieldset',
    '#title' => t('Creator'),
    '#description' => t('The person or organization that created the dataset'),
    '#tree' => true,
  ];
  $form['dataset']['creator']['creator'] = [
    '#type' => 'container'
  ];
  


/**
* subject
*/
$subjects = $form_state->get('api_subjects');
//dpm($subjects);
$domains = array_unique(array_column($subjects, 'domain'));
//$fields = array_search('Humanities' )
    $form['dataset']['subject'] = [
  '#type' => 'select',
  '#title' => t("Subject"),
  '#empty_option' => t('- Select subject -'),
  '#options' => array_combine($domains,$domains),
];


/**
 * submit actions
 */
 $form['actions'] = [
   '#type' => 'actions',
 ];

    $form['actions']['submit'] = array(
'#type' => 'submit',
'#button_type' => 'primary',
'#value' => t('Confirm and upload dataset.'),
//'#submit' => ['::confirmNIRD'],
);

    $form['actions']['cancel'] = array(
'#type' => 'submit',
'#value' => t('Cancel submission'),
'#submit' => ['::cancelSubmission'],
);

    return $form;

  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {


  }

}
