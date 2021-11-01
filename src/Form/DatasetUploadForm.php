<?php
/*
 * @file
 * Contains \Drupal\dataset_upload\DatasetUploadForm
 *
 * This form will upload a MMD file and create landig page with doi
 *
 */

namespace Drupal\dataset_upload\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Component\Uuid\UuidInterface;
//use Drupal\Core\Archiver\ArchiverManager;
use Drupal\Core\Archiver\ArchiverInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Component\Serialization\Json;

use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AppendCommand;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\Exception;


use Drupal\dataset_validation\Form\DatasetValidationForm;

use Drupal\dataset_upload\Form\DatasetForm;
use Drupal\dataset_upload\Form\MetadataTableForm;

/*
 * {@inheritdoc}
 * Form class for the dataset upload. Extending dataset validation form.
 */
class DatasetUploadForm extends DatasetValidationForm
{
    /**
    * The archiver plugin manager service.
    *
    * @var \Drupal\Core\Archiver\ArchiverManager
    */
    protected $archiverManager;

    /**

   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var AccountProxyInterface $currentUser
   */
    protected $currentUser;


    /**

   * Drupal\dataset_upload\NirdApiClientInterface definition.
   *
   * @var NirdApiClient $nirdApiClient
   */
    protected $nirdApiClient;

    /**
   * Drupal\metsis_lib\NcToMmdInterface definition.
   *
   * @var NcToMmd $ncToMmd
   */
    protected $ncToMmd;


    /**
   * Drupal\dataset_upload\AggregationChecker definition.
   *
   * @var AggregationChecker $ncToMmd
   */
    protected $aggChecker;

    /**
     * {@inheritdoc}
     */

    /* Custom class attributes */





    /**
      * {@inheritdoc}
      */
    public static function create(ContainerInterface $container)
    {
        // Instantiates this form class.
        $instance = parent::create($container);
        $instance->archiverManager = $container->get('plugin.manager.archiver');
        $instance->currentUser = $container->get('current_user');
        $instance->nirdApiClient = $container->get('dataset_upload.nird_api_client');
        $instance->ncToMmd = $container->get('metsis_lib.nc_to_mmd');
        $instance->aggChecker = $container->get('dataset_upload.aggregation_checker');
        //return new static(
        //   $container->get('plugin.manager.archiver')
        // );
        return $instance;
    }


    /*
     * Returns a unique string identifying the form.
     *
     * The returned ID should be a unique string that can be a valid PHP function
     * name, since it's used in hook implementation names such as
     * hook_form_FORM_ID_alter().
     *
     * @return string
     *   The unique string identifying the form.
     *
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'dataset_upload.form';
    }

    /**
     * Build form step 1: Upload file
     *
     * @param $o|rm
     * @param $form_state
     *
     * @return mixed
     *
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        //Get the current session
        $session = \Drupal::request()->getSession();
        //dpm('buildForm');
        /**
         * Test witch step/form page we are on, and call the corresponding buildForm
         * function for that step/page
         */
        if ($form_state->has('page') && $form_state->get('page') == 2) {
            dpm('building dataset form');
            return self::buildDatasetForm($form, $form_state);
        }

        if ($form_state->has('page') && $form_state->get('page') == 3) {
            return self::formPageThree($form, $form_state);
        }

        if ($form_state->has('page') && $form_state->get('page') == 4) {
            return self::formPageFour($form, $form_state);
        }
        if ($form_state->has('page') && $form_state->get('page') == 5) {
            return self::formPageFive($form, $form_state);
        }
        if ($session->has('dataset_upload_status')) {
          //  \Drupal::logger('dataset_upload')->debug("Unsubmitted form found...cleaning up");
          //  $this->cleanUp($this->currentUser->id());
        }

        //Set form page/step
        $form_state->set('page', 1);
      //  dpm('building form page 1...');
      //dpm('buildeing parent form');
      //Get the upload valitation form
      //$form_state->set('upload_location', 'public://dataset_upload_folder/');
      $form = parent::buildForm($form, $form_state);
      $form['container']['creation']['test'] = []; //[

        return $form;
    }

    /**
     * Build form step 1: Upload file
     *
     * @param $o|rm
     * @param $form_state
     *
     * @return mixed
     *
     * {@inheritdoc}
     */
    public function buildDatasetForm(array $form, FormStateInterface $form_state)
    {

      /**
       * Load the current user object to extract more information about the user submittingthe form.
       */
       $user = \Drupal\user\Entity\User::load($this->currentUser->id());
       //dpm($user);
       \Drupal::logger('dataset_upload')->debug('<pre><code>' . print_r($user, TRUE) . '</code></pre>');

      $form['#tree'] = true;
    $form['validation-message'] = [
      '#type' => 'markup',
      '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-green w3-pale-green w3-padding-16" id="nird-message">',
      '#markup' => '<span>Your dataset(s) is compliant with CF and ACDD standards. The submission can now proceed.</span>',
      '#suffix' => '</div>',
      '#allowed_tags' => ['div', 'span'],
    ];
    //Get the extracted metadata to prefill the form.
    $metadata = $form_state->get('metadata'); //[$form_state->get('filename')];
    //$metadata = $metadata[$form_state->get('filename')];
  //  $metadata = $form_state->get('metadata');
            $form['validation']['mmd_check'] = array(
    '#type' => 'markup',
    '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-green w3-pale-green w3-padding-16" id="mmd-message">',
'#type' => 'markup',
'#markup' => '<span>Your uploaded dataset(s) has the metadata as reported in the following table. Please make sure they are correct before
confirming your submission. If the metadata are not correct, cancel your submission, correct your information and proceed with a new submission.</span>',
'#suffix' => '</div>',
'#allowed_tags' => ['div', 'span'],

);

$form['validation']['extracted_metadata'] = [
  '#type' => 'details',
  '#title' => t("Show extracted metadata"),
  '#open' => true,
];

//foreach($metadata as $key => $value) {
/*
  $form['validation']['extracted_metadata'] = [
    '#type' => 'table',
    '#caption' => 'Extracted metadata for ' .$form_state->get('filename'),
    '#header' => ['Metadata Key', 'Metadata Value'],
    '#rows' => $metadata,
    ];
    */
  //}
    if($form_state->has('archived_files')) {
      //$metadata = $form_state->get('metadata')[$form_state->get('archived_files')[0]];
    }
    //$metadata = [];
    $prefill = [];
  /*  for ($i = 0; $i < sizeof($metadata); $i++) {
        $key =$metadata[$i][0];
        $prefill+= [$key=>$metadata[$i][1]];
    }
*/

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
  '#default_value' => $metadata['title'], //$prefill[' title'],
  '#size' => 120,
  '#required' => true,
  '#disabled' => true,
  ];

    /**
     * description
     */


    $form['dataset']['description'] = [
  '#type' => 'textarea',
  '#title' => t("Description"),
  '#value' => $metadata['summary'], //$prefill[' abstract'],
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
  dpm($licences);
  $form['dataset']['licence'] = [
  '#type' => 'select',
  '#title' => t("Licence"),
  '#empty_option' => t('- Select licence -'),
  //'#options' => array_combine($licence, $licence_name),
  //'#default_option' => $metadata['license'],
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
    $timezone = $this->currentUser->getTimeZone();
    $form['dataset']['created'] = [
  '#type' => 'date',
  '#title' => t("Created"),
  //'#default_value' => DrupalDateTime::createFromFormat($date_format, $metadata['created'], $timezone),
  '#date_date_format' => $date_format,
  '#date_time_format' => $time_format,
  //'#description' => date($date_format, time()),
  '#required' => true,
  ];





    /**
     * publication articles
     */

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
  '#required' => true,

  ];
        $form['dataset']['article']['publication']['reference']['doi'] = [
  '#type' => 'url',
  '#title' => t('DOI reference'),
  '#required' => true,
  ];
/*
        if ($num_articles > 1) {
            $form['dataset']['article']['publication']['primary'] = [
  '#type' => 'hidden',
  '#value' => false,
  ];
} else { */
            $form['dataset']['article']['primary'] = [
  '#type' => 'hidden',
  '#value' => true,
  ];
        //}
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


  $form['dataset']['contributor']['member']['firstname'] = [
'#type' => 'textfield',
  '#title' => $this
    ->t('First name'),
    '#default_value' => $user->field_first_name->value,
    '#disabled' => true,
];

  $form['dataset']['contributor']['member']['lastname'] = [
  '#type' => 'textfield',
    '#title' => $this
      ->t('Last name'),
      '#default_value' => $user->field_last_name->value,
      '#disabled' => true,
  ];
  $form['dataset']['contributor']['member']['email'] = [
    '#type' => 'email',
      '#title' => $this
        ->t('Email'),
      '#default_value' => $this->currentUser->getEmail(),
      '#disabled' => true,
    ];
  $form['dataset']['contributor']['member']['federatedid'] = [
        '#type' => 'number',
          '#title' => $this
            ->t('Federated ID'),
            '#default_value' => $this->currentUser->id(),
            '#disabled' => true,
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


    $form['dataset']['data_manager']['manager']['longname'] = [
  '#type' => 'textfield',
    '#title' => $this
      ->t('Long name'),
      '#default_value' => $metadata['institution'],
      '#disabled' => true,
  ];

    $form['dataset']['data_manager']['manager']['shortname'] = [
    '#type' => 'textfield',
      '#title' => $this
        ->t('Short name'),
        '#default_value' => $metadata['institution_short_name'],
        '#disabled' => true,
    ];
    $form['dataset']['data_manager']['manager']['contactemail'] = [
      '#type' => 'email',
        '#title' => $this
          ->t('Contact email'),
          '#default_value' => $metadata['publisher_email'],
          '#disabled' => true,
      ];
    $form['dataset']['data_manager']['manager']['homepage'] = [
          '#type' => 'url',
            '#title' => $this
              ->t('Homepage'),
              '#default_value' => $metadata['publisher_url'],
              '#disabled' => true,
          ];



              $form['dataset']['data_manager']['actions'] = [
                  '#type' => 'actions'
              ];
              $form['dataset']['data_manager']['actions']['add_person'] = [
                  '#type' => 'submit',
                  '#submit' => ['::addManagerPerson'],
                  '#value' => t('Add person'),
                  '#ajax' => [
                       'callback' => '::addManagerCallback',
                       'wrapper' => 'manager-wrapper',
                     ],
              ];
              $form['dataset']['data_manager']['actions']['add_org'] = [
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


            $form['dataset']['rights_holder']['holder']['firstname'] = [
          '#type' => 'textfield',
            '#title' => $this
              ->t('First name'),
              '#default_value' => $user->field_first_name->value,
              '#disabled' => true,

          ];

            $form['dataset']['rights_holder']['holder']['lastname'] = [
            '#type' => 'textfield',
              '#title' => $this
                ->t('Last name'),
                '#default_value' => $user->field_last_name->value,
                  '#disabled' => true,
                ];
            $form['dataset']['rights_holder']['holder']['email'] = [
              '#type' => 'email',
                '#title' => $this
                  ->t('Email'),
                '#default_value' => $this->currentUser->getEmail(),
                '#disabled' => true,
              ];
            $form['dataset']['rights_holder']['holder']['federatedid'] = [
                  '#type' => 'number',
                    '#title' => $this
                      ->t('Federated ID'),
                      '#default_value' => $this->currentUser->id(),
                      '#disabled' => true,
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
  $form['dataset']['creator']['creator']['firstname'] = [
'#type' => 'textfield',
  '#title' => $this
    ->t('First name'),
    '#default_value' => explode(' ',$metadata['creator_name'])[0],
    '#disabled' => true,
];

  $form['dataset']['creator']['creator']['lastname'] = [
  '#type' => 'textfield',
    '#title' => $this
      ->t('Last name'),
      '#default_value' => explode(' ',$metadata['creator_name'])[1],
      '#disabled' => true,
      //'#default_value' => $this->currentUser->getDisplayName(),
  ];
  $form['dataset']['creator']['creator']['email'] = [
    '#type' => 'email',
      '#title' => $this
        ->t('Email'),
        '#default_value' => $metadata['creator_email'],
        '#disabled' => true,
      //'#default_value' => $this->currentUser->getEmail(),
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
    * Override the validate function from parent
    *
    * {@inheritdoc}
    */
    public function validate(array &$form, FormStateInterface $form_state) {

    //Call the validation function from the parent DatasetValidationForm
    $form_state->set('keep_file', 1);
    $form_state->set('tests', ['cf:1.6' => 1, 'acdd' => 1]);
      \Drupal::logger('dataset_upload')->debug('calling parent validate');
     parent::validate($form, $form_state);
     \Drupal::logger('dataset_upload')->debug('finished parent validate');
     \Drupal::logger('dataset_upload')->debug($form_state->get('int_status'));
     //If dataset validation fails, redirect to form page 1.
     if($form_state->get('int_status') > 0) {
       $form_state->set('page', 1);
     }
     //If success, redirect to form page 2
     else {
       $form_state->set('page', 2);
       \Drupal::logger('dataset_upload')->debug('call NIRD API prefill controlled vocabulary');
       //Call NIRD API to prefetch controlled vocabularies
       $form_state->set('api_state', $this->nirdApiClient->getState());
      //dpm('get category');
       $form_state->set('api_category', $this->nirdApiClient->getCategory());
       //dpm('get licences');
       $form_state->set('api_licences', $this->nirdApiClient->getLicence());
       //dpm('get subject');

       $form_state->set('api_subjects', $this->nirdApiClient->getSubject());

       //Extract Metadata from datasets
        \Drupal::logger('dataset_upload')->debug('extracting metadata');
       $metadata = self::extractMetadata($form, $form_state);
       dpm($metadata);
       $form_state->set('metadata', $metadata);

     }
     $form_state->setRebuild();
   }

   /**
   * Override the validateCallback function from parent
   *
   * {@inheritdoc}
   */
   public function validateCallback(array &$form, FormStateInterface $form_state) {


    $message = $form_state->get('validation_message');
    //dpm($message);
    //$form['container']['creation']['file']['#file'] = FALSE;
    //$form['container']['creation']['file']['filename'] = [];
    //$form['container']['creation']['file']['#value']['fid'] = 0;
    //$form['message']['result'] = [];
    if($form_state->get('int_status') > 0) {
      $form['container']['message'] = $message;
    }

    return $form;

  }


/**
 * Function for extracting metadata using the metsis_lib.nc_to_mmd service
 */
 private function extractMetadata(array &$form, FormStateInterface $form_state) {
    $metadata = array();

    $output_path = \Drupal::service('file_system')->realpath($form_state->get('upload_location')) . '/';
    $file_path = $form_state->get('file_path');
    $filename = $form_state->get('filename');

    //Process single file:
    if(!$form_state->has('archived_files')) {
      $md = $this->ncToMmd->getMetadata($file_path, $filename, $output_path);

      //Give back the metadata in a better structure for filling out the form.
      //foreach($md as $key => $value) {
      //  $metadata[$key] = $value;
      //}
        \Drupal::logger('dataset_upload')->debug('<pre><code>' . print_r($md, TRUE) . '</code></pre>');
      //\Drupal::logger('dataset_upload')->debug(implode(' ', $metadata[$filename]));
    }

    //Process archived files
    if($form_state->has('archived_files')) {
      $archived_files = $form_state->get('archived_files');
      //Loop over the files
      foreach($archived_files as $f) {
        $uri = $output_path .'/' .$f;
        $filepath = \Drupal::service('file_system')->realpath($uri);
        $metadata[$filename] = $this->ncToMmd->getMetadata($filepath, $f, $output_path);

      }
    }

    /**
     * Return mockup metadata for now. until better metadata extraction service are developed.
     * This structure should be returned..all key values here should be mandatory
     *
     * keys should be acdd attributes
     */
     $metadata= [
       'title' => 'Observations from station KVIToYA SN99938',
       'summary' => 'Surface meteorological observations from the observation network operated by the Norwegian Meteorological Institute. Data are received and quality controlled using the local KVALOBS system. Observation stations are normally operated according to WMO requirements, although specifications are not followed on some remote stations for practical matters. Stations may have more parameters than reported in this dataset.',
       'institution' => 'MET Norway',
       'institution_short_name' => 'METNO',
       'publisher_url' => 'https://met.no',
       'publisher_name' => 'METNO',
       'publisher_email' => 'post@met.no',
       'creator_type' => 'person',
       'creator_url' => 'https:///met.no',
       'creator_name' => 'Nina Larsgard',
       'creator_email' => 'observations_data_archive@met.no',
       'creator_institution' => 'METNO',
       'contributor_name' => 'Louise Oram, Vegar Kristiansen',
       'contributor_role' => 'Technical contact, Technical contact',
       'license' => 'CCBY40',

     ];

    return $metadata;
 }



    /**
     * BUILD FORM PAGE 3
     */

    public function confirmServicesForm(array &$form, FormStateInterface $form_state)
    {
      //  dpm('building form page 3...');

        //$metadata = $form_state->getValue('metadata');

        $form['services'] = [
  '#type' => 'container',
];
        $form['services']['select_conf']['dataset_type'] = array(
    '#title' => t('Select the type of dataset you are uploading and the services you would like to activate for your dataset'),
    '#type' => 'radios',
    '#required' => true,
    '#options' => array('gridded_data' => t('Gridded data'),
                        'time_seriesg' => t('Time series gridded data'),
                        'time_series' => t('Time series not gridded data')),
    '#default_value' => 'gridded_data',
  );


        // here we just upload a tgz that will have to be uncompressed and validated.
        $form['services']['select_conf']['gridded_data'] = array(
    '#title' => t('Services'),
    '#type' => 'checkboxes',
    '#options' => array('https' => t('Download of dataset (http(s))'),
                        'opendap' => t('OPeNDAP (Remote access)'),
                        'wms' => t('WMS client (Web Map Server)')
                  ),
    '#default_value' => array('https', 'opendap', 'wms'),
    '#states'=> array(
    'visible' => array(
        ':input[name="dataset_type"]' =>array('value' => 'gridded_data'),
                 ),
                 ),
  );

        $form['services']['select_conf']['time_seriesg'] = array(
    '#title' => t('Services'),
    '#type' => 'checkboxes',
    '#options' => array('https' => t('Download of dataset (http(s))'),
                        'opendap' => t('OPeNDAP (Remote access)'),
                        'wms' => t('WMS client (Web Map Server)')
                  ),
    '#default_value' => array('https', 'opendap', 'wms'),
    '#states'=> array(
    'visible' => array(
        ':input[name="dataset_type"]' =>array('value' => 'time_seriesg'),
                 ),
                 ),
  );

        $form['services']['select_conf']['time_series'] = array(
    '#title' => t('Services'),
    '#type' => 'checkboxes',
    '#options' => array('https' => t('Download of dataset (http(s))'),
                        'opendap' => t('OPeNDAP (Remote access)')
                  ),
    //'#attributes' => array('checked' => 'unchecked'),
    '#default_value' => array('https', 'opendap'),
    '#states'=> array(
    'visible' => array(
        ':input[name="dataset_type"]' =>array('value' => 'time_series'),
                 ),
                 ),
  );
        $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Confirm'),
      '#submit' => ['::confirmServices'],
      );

        $form['actions']['cancel'] = array(
      '#type' => 'submit',
      '#value' => t('Cancel submission'),
      '#submit' => ['::cancelSubmission'],
      );


        //$form_state->setValue('metadata', $metadata);
        return $form;
    }





/**
 * FORM PAGE 5
 */


    public function formPageFive(array &$form, FormStateInterface $form_state)
    {

      //$form = array();
      $form['json'] = [
        '#type' => 'textarea',
        '#title' => 'JSON request object',
        '#default_value' => $form_state->get('json'),
      ];

      $form['nird_response'] = [
        '#type' => 'textarea',
        '#title' => 'NIRD API Response',
        '#default_value' => $form_state->get('dataset_response'),
      ];

      $form = self::confirmServicesForm($form, $form_state);
      return $form;
    }

    /*
     * {@inheritdoc}
     * TODO: Impletment form validation here
     *
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        /**
         * Call parent Validation
         */

        return parent::validateForm($form, $form_state);
    }


    /*
   * {@inheritdoc}
   * Main submit form. (Last step).
   */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        \Drupal::messenger()->addMessage(t("Confirm final. Contact NIRD API and upload."));
        $form_state->set('page', 5);

        //Check services selected and create services config file.
        $session = \Drupal::request()->getSession();
        $upload_path = $session->get('upload_path');
        $user_id = $this->currentUser->id();

        $dataset = $form_state->getValues()['dataset'];
          //\Drupal::logger('dataset_upload')->debug('<pre><code>' . print_r($dataset, TRUE) . '</code></pre>');

        /**
         * Modify array of form values and encode to json for
         * the create dataset api call
         */

        $category = $dataset['category'];
        $lang = $dataset['language'];
        $licence = $dataset['licence'];
        $article = $dataset['article'];
        $contributor = $dataset['contributor'];
        $published = (int) $article['publication']['published'];
        if($published === 1) {
          $article['publication']['published'] = true;
        }
        else {
          $article['publication']['published'] = false;
        }
        //Datamanger
        $manager = $dataset['data_manager'];
        unset($manager['actions']);

        $manager_new = [];
        if(isset($manager['manager']['person'])) {
          $dm_person = $manager['manager']['person'];

        foreach ($dm_person as $p) {
          $obj = (object) [
            'manager' => $p
          ];
          array_push($manager_new,$obj);
        }
      }

      if(isset($manager['manager']['organization'])) {
        $dm_org = $manager['manager']['organization'];

        foreach ($dm_org as $o) {
          $obj = (object) [
            'manager' => $o
          ];
          array_push($manager_new,$obj);
        }

}
        //Rights holder
        $holder = $dataset['rights_holder'];

        //Creator
        $creator = $dataset['creator'];

        //Subject
        $subject = $dataset['subject'];

        //FOR testing
         $subject = (object) [
           'domain' => 'Natural sciences',
           'field' => 'Space science',
           'subfield' => 'Astrophysics'
         ];

        $dataset['created'] = $form_state->getValue(['dataset','created']); //->format('Y-m-d');
        $dataset['category'] = [[
          'name' => $category,
        ]];
        $dataset['language'] = [[
          'name' => $lang,
        ]];
        $dataset['licence'] = [
          'id' => $licence,
        ];
        $dataset['article'] = [
          $article
        ];
        $dataset['contributor'] = [
          $contributor,
        ];


        $dataset['data_manager'] = [$manager]; //_new;

        //$dataset['rights_holder'] = $holder

        $dataset['creator'] = [
          $creator
        ];
        $dataset ['subject'] = [
          $subject
        ];

        $json = Json::encode($dataset);
        //\Drupal::logger('dataset_upload')->debug($json);
        \Drupal::logger('dataset_upload')->debug('<pre><code>' . print_r($dataset, TRUE) . '</code></pre>');

  /*
   * Call the NIRD API create dataset endpoint
  */
      $result = $this->nirdApiClient->createDataset($dataset);
          \Drupal::logger('dataset_upload')->debug('<pre><code>' . print_r($result, TRUE) . '</code></pre>');

    }


    /**
      * Cancel submission form function
      */
    public function cancelSubmission(array &$form, FormStateInterface $form_state)
    {
        \Drupal::messenger()->addMessage(t("Cancel submission (clear all data)"));
        //TODO: Call cleaning function with parameters

        //Clean all session data
        $user_id = $this->currentUser->id();
        $this->cleanUp($user_id);

        $form_state->setRedirect('dataset_upload.form');
    }

    private static function cleanUp($user_id)
    {/*
        \Drupal::logger('dataset_upload')->debug("Clean up session variables and files.");
        $session = \Drupal::request()->getSession();
        if ($session->has('current_upload_fid')) {
            $fid = $session->get('current_upload_fid');
            $file = File::load($fid);
            if (isset($file)) {
                $file->delete();
            }
        }
        $upload_path = 'public://dataset_upload_folder/';
        $session_id = $session->getId();

        $filesystem = \Drupal::service('file_system');


        //Remove aggregation config file entity
        if ($session->has('aggregation_config_fid')) {
            $fid = $session->get('aggregation_config_fid');
            $file = File::load($fid);
            if (isset($file)) {
                $file->delete();
            }
        }

        //Remove services config file entity
        if ($session->has('services_config_fid')) {
            $fid = $session->get('services_config_fid');
            $file = File::load($fid);
            if (isset($file)) {
                $file->delete();
            }
        }
        $filesystem->deleteRecursive($upload_path . $user_id . '/' . $session_id);

        $is_empty = function (string $folder): bool {
            if (!file_exists($folder)) {
                return true;
            }

            if (!is_dir($folder)) {
                throw new \Exception("{$folder} is not a folder.");
            }

            return is_null(shell_exec("ls {$folder}"));
        };

        if ($is_empty($upload_path . $user_id)) {
            $filesystem->deleteRecursive($upload_path . $user_id);
        }
        $session->remove('nird_upload_path');
        $session->remove('current_upload_uuid');
        //$session->remove('nird_fail_message');
        //$session->remove('nird_failed');
        $session->remove('upload_archive');
        $session->remove('num_files');
        $session->remove('files_in_archive');
        $session->remove('current_upload_fid');
        $session->remove('dataset_upload_basepath');
        $session->remove('aggregation_config_fid');
        $session->remove('services_config_fid');
        $session->remove('dataset_upload_status');

        //Clean access token
        if ($session->has('access_token')) {
            $session->remove('access_token');
        }
        if ($session->has('token_type')) {
            $session->remove('token_type');
        }*/
    }

    /**
     * Form action step 3
     * Confirm metadata and display selection of services form
     */
    public function confirmMetadata(array &$form, FormStateInterface $form_state)
    {
        \Drupal::messenger()->addMessage(t("Metadata confirmation form"));
        //$form_state->set('page', 3);
        $session = \Drupal::request()->getSession();
        $upload_path = $session->get('nird_upload_path');
        $user_id = $this->currentUser->id();
        $base_path = $session->get('dataset_upload_basepath');

        $fid = $session->get('current_upload_fid');
        $fid = (int)$form_state->get('upload_fid');
        $file = File::load($fid); //Load the file object
      $filename = $file->getFilename(); //Get

      \Drupal::messenger()->addMessage('Chosen variable: ' .$form_state->getValue('aggregation'));
        if (!empty($form_state->getValue('aggregation'))) {
            \Drupal::messenger()->addMessage(t('creating aggregation config .ncml'));
            $ncml_content = '<netcdf xmlns="http://www.unidata.ucar.edu/namespaces/netcdf/ncml-2.2">';
            $ncml_content .= "\n";
            $ncml_content .= '<aggregation dimName="'.$form_state->getValue('aggregation').'" type="joinExisting">';
            $ncml_content .= "\n";
            $ncml_content .= '<scan location="." suffix=".nc" />';
            $ncml_content .= "\n";
            $ncml_content .= '</aggregation>';
            $ncml_content .= "\n";
            $ncml_content .= '</netcdf>';
            $ncml_content .= "\n";
            $ncml_file = $upload_path. substr($filename, 0, -4). '.ncml' ;
            $ncml_config = file_save_data($ncml_content, $ncml_file, FileSystemInterface::EXISTS_REPLACE);
            $ncml_config->save();
            \Drupal::messenger()->addMessage(t("Created aggregation config file with fid: " .$ncml_config->id()));
            $session->set('aggregation_config_fid', $ncml_config->id());

            $archived_files = $session->get('files_in_archive');
            \Drupal::messenger()->addMessage($archived_files);
            //create string with list of files which are input to the agg_checker.py
            $files_to_agg = '';
            foreach ($archived_files as $file) {
                $files_to_agg .= $base_path.'/extract/'.$file.' ';
            }

            //check dimensions, variables names and attributes to allow for aggregation
            exec('/usr/local/bin/agg_checker.py '.$files_to_agg.' '.$form_state->getValue('aggregation'), $out_agg, $status_agg);
            //dpm('/usr/local/bin/agg_checker.py '.$files_to_agg.' '.$form_state->getValue('aggregation'));
            \Drupal::messenger()->addMessage("agg_checker.py ran with status: " .$status_agg);
            $fail_agg = false;
            $msg_agg = array();
            //build the message with only the Fail prints from the agg_checker.py
            foreach ($out_agg as $line) {
                if (strpos($line, 'Fail') !== false) {
                    $fail_agg = true;
                    array_push($msg_agg, $line);
                }
            }

            // agg_checker.py exit with status 0, but gives Fail messages, i.e. the datasets are not suitable for aggregation
            if ($fail_agg == true) {
                \Drupal::messenger()->addMessage(t('Your datasets cannot be aggregated. Check suggestions below:<br>'.print_r(implode('<br>', $msg_agg), true)));
                \Drupal::messenger()->addMessage(t("agg_checker.py ran with status: " .$status_agg . " and output: " . implode(" ", $out_agg)));
            }
            // agg_checker.py exit with status not 0, i.e. it could not be run.
            if ($status_agg !== 0) {
                \Drupal::messenger()->addMessage(t('The aggregation validation checker could not be run. Please take contact using the contact form.'));
                \Drupal::messenger()->addMessage(t("agg_checker.py ran with status: " .$status_agg . " and output: " . implode(" ", $out_agg)));
            }
        }

      return $form;  //$form_state->setRebuild();
    }

    public function confirmServices(array &$form, FormStateInterface $form_state)
    {
        \Drupal::messenger()->addMessage(t("Confirm services form action."));
        //Check services selected and create services config file.
        $form_state->set('page', 4);
        $session = \Drupal::request()->getSession();
        $upload_path = $session->get('nird_upload_path');
        $user_id = $this->currentUser->id();

        //$fid = $session->get('current_upload_fid');
        $fid = $form_state->get('upload_fid');
        $file = File::load($fid); //Load the file object
      $filename = $file->getFilename(); //Get
      //$values = $form_state->getValues();
      //var_dump($values);

       $dataset_type = $form_state->getValue('dataset_type');
        $selected_checkboxes = $form_state->getValue($dataset_type);

        //dpm($dataset_type);
        //dpm($selected_checkboxes);
        $contents = '';
        if ($selected_checkboxes['https'] !== 0) {
            $contents = '<thredds:service name="file" serviceType="HTTPServer" base="/opendap/hyrax/">';
            $contents .= "\n";
        }
        if ($selected_checkboxes['opendap'] !== 0) {
            $contents .= '<thredds:service name="dap" serviceType="OPeNDAP" base="/opendap/hyrax/">';
            $contents .= "\n";
        }
        if ($selected_checkboxes['wms'] !== 0) {
            $contents .= '<thredds:service name="wms" serviceType="WMS" base="https://ns9530k.ncwms.sigma2.no/ncWMS2/wms">';
            $contents .= "\n";
        }
        $existing = $upload_path . explode(".", $filename)[0] . '.cfg' ;
        $cfg_file = file_save_data($contents, $existing, FileSystemInterface::EXISTS_REPLACE);
        $cfg_file->save();
        \Drupal::messenger()->addMessage(t("Created servies config file with fid: " .$cfg_file->id()));
        $session->set('services_config_fid', $cfg_file->id());




        //$email_to = variable_get('site_mail', '');
        /*
        $email_to = $user->mail;
        $from = variable_get('site_mail', '');

        $params = array(
          'body' => 'Your dataset has been uploaded on NorDataNet. The dataset will be delivered to NIRD, uploaded into the archive and a DOI will be given. When your dataset will be ready you will be notified. <br> Thank you for submitting your dataset through NorDataNet!<br> The NorDataNet team.',
          'subject' => 'Dataset upload on NordataNet',
        );

        $language = language_default();
        $send = TRUE;
        $result = drupal_mail($module, $key, $email_to, $language, $params, $from, $send);
        if ($result['result'] == TRUE) {
          drupal_set_message(t('A message has been sent to your email: '.$user->mail));
        }
        else {
          drupal_set_message(t('There was a problem sending your message and it was not sent.'), 'error');
        }

        drupal_set_message(t('Your dataset and related info have been submitted.'),'status');
        $form_state['redirect'] = 'dataset_upload/form'; // Redirects the user.
        unset ($form_state['storage']);
     }*/

        $form_state->setRebuild();
    }


    public function licenceSelectCallback(array &$form, FormStateInterface $form_state)
    {
        $response = new AjaxResponse();

        $selected_licence = $form['dataset']['licence'];

        $licence_key = array_search($selected_licence, array_column($this->licences, 'licence'));
        $licence = $this->licences[$licence_key];
        $markup = '<a href="' .$licence['archive'].'">'.$licence['name'].'</a>';

        $response->addCommand(new ReplaceCommand('#licence-info', '<div>'.$markup.'</div>'));
        return $response;
    }

    /**
       * Submit handler for the "remove one" button.
       *
       * Decrements the max counter and causes a form rebuild.
       */
    public function removeCallback(array &$form, FormStateInterface $form_state)
    {
        $articles = $form_state->get('num_articles');
        if ($articles > 1) {
            $remove_button = $articles - 1;
            $form_state->set('num_articles', $remove_button);
        }
        // Since our buildForm() method relies on the value of 'num_names' to
        // generate 'name' form elements, we have to tell the form to rebuild. If we
        // don't do this, the form builder will not call buildForm().
        $form_state->setRebuild();
    }



    public function addManagerCallback(array &$form, FormStateInterface $form_state)
    {
        return  $form['dataset']['data_manager']['manager'];
    }
    public function addManagerPerson(array &$form, FormStateInterface $form_state)
    {
        //$response = new AjaxResponse();

        $num_manager_person = $form_state->get('num_manager_person');
        //  if (empty($num_articles)) {

        //  }
        //\Drupal::logger('nordatanet_nird')->debug("number of articles before: " . $num_articles);
        $add_person = $num_manager_person +1;
        //\Drupal::logger('nordatanet_nird')->debug("number of articles after: " . $add_article);

        $form_state->set('num_manager_person', $add_person);
        $form_state->setRebuild();
      }


  /*    public function addManagerOrgCallback(array &$form, FormStateInterface $form_state)
      {
          return   $form['dataset']['article']['publication'];
      }*/
      public function addManagerOrg(array &$form, FormStateInterface $form_state)
      {
          //$response = new AjaxResponse();

          $num_manager_org = $form_state->get('num_manager_org');
          //  if (empty($num_articles)) {

          //  }
          //\Drupal::logger('nordatanet_nird')->debug("number of articles before: " . $num_articles);
          $add_org = $num_manager_org +1;
          //\Drupal::logger('nordatanet_nird')->debug("number of articles after: " . $add_article);

          $form_state->set('num_manager_org', $add_org);
          $form_state->setRebuild();
        }
}
