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
/*
 * {@inheritdoc}
 * Form class for the bokeh init form
 */
class DatasetUploadOldForm extends FormBase
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
     * {@inheritdoc}
     */

    /* Custom class attributes */

    //The licences array from the NIRD API
    protected $licences;

    //The extracted metadata
    protected $metadata;

    protected function getEditableConfigNames()
    {
    }


    /**
       * @param \Drupal\Core\Archiver\ArchiverManager $archiver_manager
       *   The archiver plugin manager service.
       */
    /**  public function __construct(ArchiverManager $archiver_manager) {
    *    $this->archiverManager = $archiver_manager;
    * }
*/
    /**
      * {@inheritdoc}
      */
    public static function create(ContainerInterface $container)
    {
        // Instantiates this form class.
        $instance = parent::create($container);
        $instance->archiverManager = $container->get('plugin.manager.archiver');
        $instance->currentUser = $container->get('current_user');
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
        return 'dataset_upload.old_form';
    }

    /*
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

        /**
         * Test witch step/form page we are on, and call the corresponding buildForm
         * function for that step/page
         */
        if ($form_state->has('page') && $form_state->get('page') == 2) {
            return self::formPageTwo($form, $form_state);
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
            \Drupal::logger('dataset_upload')->debug("Unsubmitted form found...cleaning up");
            $this->cleanUp($this->currentUser->id());
        }

        //Set form page/step
        $form_state->set('page', 1);
      //  dpm('building form page 1...');



        //Get current user session
        $user_id = $this->currentUser->id(); //Get the user ID
        $session_id = $session->getId(); //Get the session ID.


        //Set the upload path
        $upload_path = 'public://dataset_upload_folder/' . $user_id . '/' . $session_id . '/';
        $session->set('nird_upload_path', $upload_path);


        //Get supported extensions from ArchiverManager.
        $extensions = $this->archiverManager->getExtensions();

        /**
        * Build the form
        */
        $form['creation'] = array(
   '#type' => 'markup',
   '#prefix' => '<div id="nird-output">',
   '#suffix'  => '</div>',
   //'#attributes' => array('id' => array('nird-output')),
 );
        $form['creation']['message'] = array(
   '#type' => 'markup',
   '#markup' => $this->t('Before you upload your dataset make sure you have validated it against the IOOS compliance checker. This service is provided by this portal.
                         Your dataset will be checked against CF-1.6 and ACDD-1.3 standards. <br> If your dataset is not compliant it will not be accepted for upload
                         and your submission will fail.'),
   '#description' => $this->t('Webform for validation of netCDF files based on the <a href=https://github.com/ioos/compliance-checker>IOOS compliance checker </a> '), // Description of our page
   '#collapsible' => true,
   '#collapsed' => false,
 );

if($form_state->has('validation_error')) {
  $form['creation']['error'] = $form_state->get('validation_error');
}

        $form['creation']['file'] = [
   '#type' => 'managed_file',
   '#title' => t('Upload a single dataset or multiple related datasets in an archive'),
   //'#description' => t('You can upload a single netCDF (.cf) file, or an archive with multiple netCDF files (.zip, .tar.gz, .tar, .tgz). Maximum filesize is 1500M. You need to upload a bigger file, take contact with the website support directly.'),
   '#description' => t('You can upload a single netCDF (.nc) file, or an archive with multiple netCDF files (' .$extensions. ') Maximum filesize is 1500M. You need to upload a bigger file, take contact with the website support directly.'),
   '#required' => true,
   '#multiple' => false,
   '#upload_validators' => [
      //'file_validate_extensions' =>  ['nc zip tar.gz tgz tar'],
      'file_validate_extensions' => ['nc ' . $extensions],
    // IMPORTANT for allowing file upload:
 // this works only when changing the /etc/php5/apache2/php.ini post_max_size and filesize in apache to 200M
      'file_validate_size' => array(1500 * 1024 * 1024),
    ],
   '#upload_location' => $upload_path
 ];
 $form['creation']['actions'] = [
     '#type' => 'actions'
 ];
        $form['creation']['actions']['submit'] = array(
'#type' => 'submit',
'#button_type' => 'primary',
'#value' => t('Next >>'),
'#submit' => ['::validateUploaded'],
);





        return $form;
    }



    /**
     * Build form page 2. Select services.
     */

    public function formPageTwo(array &$form, FormStateInterface $form_state)
    {
        //dpm('building form page 2...');
        /*Check if we hav archive with multiple files */
        $session = \Drupal::request()->getSession();
        $session->set('dataset_upload_status', 'started');
        $upload_archive = $session->get('upload_archive');
        \Drupal::messenger()->addMessage('upload_archive: '. $upload_archive);
        //dpm($form_state->getValue('metadata'));

        $form['validation'] = [
          '#type' => 'fieldset',
          '#title' => $this->t("Dataset validation status"),
        ];

        if ($upload_archive == 1) {
            $number_of_files = $session->get('num_files');
            \Drupal::messenger()->addMessage('processing archive: '. $number_of_files);
            $form['validation']['message'] = [
    '#type' => 'markup',
    '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-green w3-pale-green w3-padding-16" id="nird-message">',
    '#markup' => '<span>Your dataset(s) in archive is compliant with CF and ACDD standards. The submission can now proceed.</span>',
    '#suffix' => '</div>',
    '#allowed_tags' => ['div', 'span'],
  ];
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
  '#title' => $this->t("Show extracted metadata"),
  '#open' => true,
];

            for ($i = 0; $i < $number_of_files; $i++) {
                $form['validation']['extracted_metadata']['metadata' .$i] = [
'#type' => 'table',
'#caption' => 'Extracted metadata for ' .$form_state->getValue('filename')[$i],
'#header' => ['Metadata Key', 'Metadata Value'],
'#rows' => $form_state->getValue('metadata')[$i][0],
];
                //dpm(array_keys($form_state->getValue('metadata')[$i]));
            }
            if ($number_of_files > 1) {
                $form['validation']['aggregation'] = array(
  '#type' => 'textfield',
  '#required' => true,
  '#title' => t('You have selected a multiple file upload. Your files will be aggregated on the server. <br>
                 Declare the variable on which you wish to aggregate your netcdf files. <br>
                 This field MUST match the exact name of the variables in your netCDF files'),
//'#element_validate' => TODO:: Create custom validation to chek that given variable are common for all datasets to be aggregated
);
            }
        } else {
            \Drupal::messenger()->addMessage('processing single netcf');
            $form['validation']['message'] = [
          '#type' => 'markup',
        '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-green w3-pale-green w3-padding-16" id="nird-message">',
          '#markup' => "<span>Your dataset ".$form_state->getValue('filename')." is compliant with CF and ACDD standards. The submission can now proceed.</span>",
          '#suffix' => '</div>',
          '#allowed_tags' => ['div', 'span'],

        ];
            $form['validation']['mmd_check'] = array(
          '#type' => 'markup',
          '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-green w3-pale-green w3-padding-16" id="mmd-message">',
      '#type' => 'markup',
      '#markup' => '<span>Your uploaded dataset has the metadata as reported in the following table. Please make sure they are correct before
      confirming your submission. If the metadata are not correct, cancel your submission, correct your information and proceed with a new submission.</span>',
      '#suffix' => '</div>',
      '#allowed_tags' => ['div', 'span'],

      );
      $form['validation']['extracted_metadata'] = [
        '#type' => 'details',
        '#title' => $this->t("Show extracted metadata"),
      ];


            $form['validation']['extracted_metadata']['metadata'] = [
'#type' => 'table',
'#caption' => 'Extracted metadata for ' .$form_state->getValue('filename'),
'#header' => ['Metadata Key', 'Metadata Value'],
'#rows' => $form_state->getValue(['metadata'])[0],
];
        }


/*
        $form['actions']['submit'] = array(
'#type' => 'submit',
'#button_type' => 'primary',
'#value' => t('Confirm'),
'#submit' => ['::confirmMetadata'],
);

        $form['actions']['cancel'] = array(
'#type' => 'submit',
'#value' => t('Cancel submission'),
'#submit' => ['::cancelSubmission'],
);
*/
        return $form;
    }


    /**
     * BUILD FORM PAGE 3
     */

    public function formPageThree(array &$form, FormStateInterface $form_state)
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
     * BUILD FORM PAGE 4
     */

    public function formPageFour(array &$form, FormStateInterface $form_state)
    {
        //Build the validation message form.
        $form = self::formPageTwo($form, $form_state);





        //Get the extracted metadata to prefill the form.
        $metadata = $this->metadata[0];
        $prefill = [];
        for ($i = 0; $i < sizeof($metadata); $i++) {
            $key =$metadata[$i][0];
            $prefill+= [$key=>$metadata[$i][1]];
        }

        $form['#tree'] = true;
        $form['dataset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Dataset information"),
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
      '#title' => $this->t("Title"),
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
      '#title' => $this->t("Description"),
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
          '#title' => $this->t("State"),
          '#empty_option' => $this->t('- Select state -'),
          '#options' => array_combine($state, $state),
          '#required' => true,
        ];


        /**
         * category
         */

        $category = $form_state->get('api_category');
        $form['dataset']['category'] = [
      '#type' => 'select',
      '#title' => $this->t("Category"),
      '#empty_option' => $this->t('- Select category -'),
      '#options' => array_combine($category, $category),
      '#required' => true,
    ];



        /**
         * language
         */

        $form['dataset']['language'] = [
      '#type' => 'select',
      '#title' => $this->t("Language"),
      '#empty_option' => $this->t('- Select language -'),
      '#options' => ['Norwegian'=>'Norwegian', 'English'=>'English'],
      '#required' => true,
    ];

    /**
     * Licence
     */




    $licence = array_column($this->licences, 'licence');
    $licence_name = array_column($this->licences, 'name');
    /*  foreach($lics as $lic ) {
        $licence[] = $licences['licence'];
      }*/
    $form['dataset']['licence'] = [
  '#type' => 'select',
  '#title' => $this->t("Licence"),
  '#empty_option' => $this->t('- Select licence -'),
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
        $created_date = explode('T', $prefill[' last_metadata_update update datetime'])[0];
        $created_time = substr(explode('T', $prefill[' last_metadata_update update datetime'])[1], 0, -1);
        //dpm($created_date);
        //dpm($created_time);
        $date_time_format = trim($date_format . ' ' . $time_format);
        $date_time_input = trim($created_date . ' ' . $created_time);
        $timezone = $this->currentUser->getTimeZone();
        $form['dataset']['created'] = [
      '#type' => 'datetime',
      '#title' => $this->t("Created"),
      '#default_value' => DrupalDateTime::createFromFormat($date_time_format, $date_time_input, $timezone),
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
      //'#title' => $this->t(),
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
              '#title' => $this->t('The publication(s) that describes the dataset.'),
              '#discription' => $this->t('Add publications related to this dataset. The first publication added will be consiedered the primary publication.'),
              '#prefix' => '<div id="publication-wrapper">',
              '#suffix' => '</div>',
              //'#tree' => TRUE,
            ];

        //$form['#tree'] = TRUE;
        //for ($i = 0; $i < $num_articles; $i++) {
        //for ($i = 0; $i < $num_articles; $i++) {
            $form['dataset']['article']['publication']['published'] = [
      '#type' => 'select',
      '#title' => $this->t('Published'),
      '#empty_option' => $this->t('- Select published status -'),
      '#options' => [true =>'Yes', false => 'No'],
    ];
            $form['dataset']['article']['publication']['reference']['doi'] = [
      '#type' => 'url',
      '#title' => $this->t('DOI reference'),
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
        '#value' => $this->t('Remove one'),
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
      '#title' => $this->t('Contributor'),
      '#description' => $this->t('The person or group of people that contributed to the archiving of the dataset.'),
      '#tree' => true,
    ];
      $form['dataset']['contributor']['member'] = [
        '#type' => 'container',
      ];
        $form['dataset']['contributor']['member']['firstname'] = [
      '#type' => 'textfield',
        '#title' => $this
          ->t('First name'),
          '#default_value' => $this->currentUser->getAccountName(),
      ];

        $form['dataset']['contributor']['member']['lastname'] = [
        '#type' => 'textfield',
          '#title' => $this
            ->t('Last name'),
            '#default_value' => $this->currentUser->getDisplayName(),
        ];
        $form['dataset']['contributor']['member']['email'] = [
          '#type' => 'email',
            '#title' => $this
              ->t('Email'),
            '#default_value' => $this->currentUser->getEmail(),
          ];
        $form['dataset']['contributor']['member']['federatedid'] = [
              '#type' => 'number',
                '#title' => $this
                  ->t('Federated ID'),
                  '#default_value' => $this->currentUser->id(),
              ];
$form['dataset']['contributor']['uploader'] = [
  '#type' => 'hidden',
  '#value' => true,
];

              /**
               * data manager
               */

               $num_manager_person = $form_state->get('num_manager_person');
               $num_manager_org = $form_state->get('num_manager_org');
               //dpm('before: ' .$num_articles);

               if ($num_manager_person === null) {
                   $form_state->set('num_manager_person', 0);
                   $num_manager_person = 0;
               }
               if ($num_manager_org === null) {
                   $form_state->set('num_manager_org', 0);
                   $num_manager_org = 0;
               }

    //           dpm('manager persons: ' .$num_manager_person);
      //         dpm('manager orgs: ' .$num_manager_org);


              $form['dataset']['data_manager'] = [
                  '#type' => 'container',
                  '#tree' =>   true,
          ];

          $form['dataset']['data_manager']['manager'] = [

          '#type' => 'fieldset',
          '#title' => $this->t('Data manager'),
          '#description' => $this->t('The person or organization that are responsible for fielding questions on the maintenance and use of the data. There can be more than one data manager'),
          '#tree' => true,
          '#prefix' => '<div id="manager-wrapper">',
          '#suffix' => '</div>',
        ];





          for ($i = 0; $i < $num_manager_person; $i++) {
            //if($i === 0) {
              $form['dataset']['data_manager']['manager']['person'][$i] = [
                '#type' => 'details',
                '#title' => $this->t('Person'),
                '#open' => true,

              ];
            //}
            $form['dataset']['data_manager']['manager']['person'][$i]['firstname'] = [
          '#type' => 'textfield',
            '#title' => $this
              ->t('First name'),
              '#default_value' => $this->currentUser->getAccountName(),
          ];

            $form['dataset']['data_manager']['manager']['person'][$i]['lastname'] = [
            '#type' => 'textfield',
              '#title' => $this
                ->t('Last name'),
                '#default_value' => $this->currentUser->getDisplayName(),
            ];
            $form['dataset']['data_manager']['manager']['person'][$i]['email'] = [
              '#type' => 'email',
                '#title' => $this
                  ->t('Email'),
                '#default_value' => $this->currentUser->getEmail(),
              ];
            $form['dataset']['data_manager']['manager']['person'][$i]['federadetid'] = [
                  '#type' => 'number',
                    '#title' => $this
                      ->t('Federated ID'),
                      '#default_value' => $this->currentUser->id(),
                  ];


              }

              for ($i = 0; $i < $num_manager_org; $i++) {
                  $form['dataset']['data_manager']['manager']['organization'][$i] = [
                    '#type' => 'details',
                    '#title' => $this->t('Organization'),
                    '#open' => true,

                  ];

                $form['dataset']['data_manager']['manager']['organization'][$i]['longname'] = [
              '#type' => 'textfield',
                '#title' => $this
                  ->t('Long name'),
                  '#default_value' => $form_state->getValue(['dataset','data_manager','manager',$i,'longname']),
              ];

                $form['dataset']['data_manager']['manager']['organization'][$i]['shortname'] = [
                '#type' => 'textfield',
                  '#title' => $this
                    ->t('Short name'),
                    '#default_value' => $form_state->getValue(['dataset','data_manager','manager',$i,'shortname']),
                ];
                $form['dataset']['data_manager']['manager']['organization'][$i]['contactemail'] = [
                  '#type' => 'email',
                    '#title' => $this
                      ->t('Contact email'),
                    '#default_value' => $form_state->getValue(['dataset','data_manager','manager',$i,'contactemail']),
                  ];
                $form['dataset']['data_manager']['manager']['organization'][$i]['homepage'] = [
                      '#type' => 'url',
                        '#title' => $this
                          ->t('Homepage'),
                          '#default_value' => $form_state->getValue(['dataset','data_manager','manager',$i,'homepage']),
                      ];

                  }

                  $form['dataset']['data_manager']['manager']['actions'] = [
                      '#type' => 'actions'
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

                    /**
                     * rights holder
                     */

                    $form['dataset']['rights_holder'] = [
                  '#type' => 'fieldset',
                  '#title' => $this->t('Rights holder'),
                  '#description' => $this->t('The person or organization that hold the rights to the data (or can act as the contact person).'),
                  '#tree' => true,
                ];
                $form['dataset']['rights_holder']['holder'] = [
                  '#type' => 'container'
                ];
                    $form['dataset']['rights_holder']['holder']['firstname'] = [
                  '#type' => 'textfield',
                    '#title' => $this
                      ->t('First name'),
                      '#default_value' => $this->currentUser->getAccountName(),
                  ];

                    $form['dataset']['rights_holder']['holder']['lastname'] = [
                    '#type' => 'textfield',
                      '#title' => $this
                        ->t('Last name'),
                        '#default_value' => $this->currentUser->getDisplayName(),
                    ];
                    $form['dataset']['rights_holder']['holder']['email'] = [
                      '#type' => 'email',
                        '#title' => $this
                          ->t('Email'),
                        '#default_value' => $this->currentUser->getEmail(),
                      ];
                    $form['dataset']['rights_holder']['holder']['federatedid'] = [
                          '#type' => 'number',
                            '#title' => $this
                              ->t('Federated ID'),
                              '#default_value' => $this->currentUser->id(),
                          ];


          /**
           * creator
           */

          $form['dataset']['creator'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Creator'),
        '#description' => $this->t('The person or organization that created the dataset'),
        '#tree' => true,
      ];
      $form['dataset']['creator']['creator'] = [
        '#type' => 'container'
      ];
          $form['dataset']['creator']['creator']['firstname'] = [
        '#type' => 'textfield',
          '#title' => $this
            ->t('First name'),
            '#default_value' => $this->currentUser->getAccountName(),
        ];

          $form['dataset']['creator']['creator']['lastname'] = [
          '#type' => 'textfield',
            '#title' => $this
              ->t('Last name'),
              '#default_value' => $this->currentUser->getDisplayName(),
          ];
          $form['dataset']['creator']['creator']['email'] = [
            '#type' => 'email',
              '#title' => $this
                ->t('Email'),
              '#default_value' => $this->currentUser->getEmail(),
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
      '#title' => $this->t("Subject"),
      '#empty_option' => $this->t('- Select subject -'),
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
    '#value' => $this->t('Confirm and upload dataset.'),
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

      $form = self::formPageThree($form, $form_state);
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
        $upload_path = $session->get('nird_upload_path');
        $user_id = $this->currentUser->id();
        //$values = $form_state->getValues();
        //var_dump($values);

        $config = \Drupal::config('dataset_upload.settings');
        $nird_api_base_uri = $config->get('nird_api_base_uri');
        $nird_api_dataset_endpoint = $config->get('nird_api_dataset_endpoint');

        // $dataset_type = $form_state->getValue('description');
        // $selected_checkboxes = $form_state->getValue($dataset_type);

        $dataset = $form_state->getValues()['dataset'];
        dpm($dataset);

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

        $dataset['created'] = $form_state->getValue(['dataset','created'])->format('Y-m-d');
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


        $dataset['data_manager'] = $manager_new;

        //$dataset['rights_holder'] = $holder

        $dataset['creator'] = [
          $creator
        ];
        $dataset ['subject'] = [
          $subject
        ];

        $json = Json::encode($dataset);

  /*
   * Call the NIRD API create dataset endpoint
  */
        $token_type = $form_state->get('token_type');
        $token = $form_state->get('token');

        try {
            $client = \Drupal::httpClient();
            $request = $client->request(
              'POST',
              $nird_api_base_uri . $nird_api_dataset_endpoint,
              [
                'json' => $json,
                'Accept' => 'application_json',
                'Content-Type' => 'application/json',
                'debug' => false,
                'headers' =>
                    [
                        'Authorization' => "{$token_type} {$token}"
                    ],
              ],
          );
          $responseStatus = $request->getStatusCode();
          \Drupal::logger('dataset_upload')->debug("/api/dataset POST response status: @string", ['@string' => $responseStatus ]);
          $response = $request->getBody();
          \Drupal::logger('dataset_upload')->debug(t("Got original response: @markup", ['@markup' => $data] ) );
        }
        catch (ClientException $e){
          //\Drupal::messenger()->addError("NIRD API ERROR @uri .", [ '@uri' =>   $nird_api_base_uri . $nird_api_dataset_endpoint]);
          //\Drupal::messenger()->addError($e);
          $res = $e->getResponse();
          $response = (string) $res->getBody();
        }
        catch (RequestException $e){
          //\Drupal::messenger()->addError("NIRD API ERROR @uri .", [ '@uri' =>   $nird_api_base_uri . $nird_api_dataset_endpoint]);
          //\Drupal::messenger()->addError($e);
          $res = $e->getResponse();
          $response = (string) $res->getBody();
        }
        catch (Exception $e){
          //\Drupal::messenger()->addError("NIRD API ERROR @uri .", [ '@uri' =>   $nird_api_base_uri . $nird_api_dataset_endpoint]);
          //\Drupal::messenger()->addError($e);
          $res = $e->getResponse();
          $response = (string) $res->getBody();
        }
        $form_state->set('json', $json);
        $form_state->set('dataset_response', $response);
        //Check aggregation and create manifest
        self::confirmMetadata($form, $form_state);
        $form_state->setRebuild();


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
    {
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
        }
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

    /**
     * Form action step 1
     * Validate uploaded netCDF file or contents of archive
     */
    public function validateUploaded(array &$form, FormStateInterface $form_state)
    {

        /*
         * Submit the form and do some actions
         */
        $currentAccount = \Drupal::currentUser();
        $user_id = $currentAccount->id();
        $session = \Drupal::request()->getSession(); //Get current user session
        $session_id = $session->getId(); //Get the session ID.
        $extract_path = $session->get('nird_upload_path') . '/extract/';


        //Generate uuid for this form submission
        $uuid_service = \Drupal::service('uuid');
        $uuid = $uuid_service->generate();
        \Drupal::logger('dataset_upload')->debug("generated uuid: " .$uuid);

        $session->set('current_upload_uuid', $uuid);

        //Get the form values
        $values = $form_state->getValues();


        //Get Information of uploaded file
            $fid = $values['file'][0]; //Get the file id (fid) of the uploaded file
            $file = File::load($fid); //Load the file object
            $session->set('current_upload_fid', $fid);
            $form_state->set('upload_fid', $fid);
        $furi = $file->getFileUri(); //Get the file URI
            $filename = $file->getFilename(); //Get the filename
            $mime_type = $file->getMimeType(); //Get the mime type of file to determine netCDF or archive
            $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager')->getViaUri($furi); //Initialise stream wrapper
            $file_path = $stream_wrapper_manager->realpath(); //Get the real absoulute system path of file
            $path = explode("://", $furi)[1];
        //dpm($furi);
        //dpm($file_path);
        \Drupal::messenger()->addMessage(t($mime_type));

        $options = array();
        $options['filepath'] = \Drupal::service('file_system')->realpath($furi); //Absolute system filepath

        $basepath =  \Drupal::service('file_system')->dirname($furi); //Get the absolute system basepath
        $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager')->getViaUri($basepath);
        $base_path = $stream_wrapper_manager->realpath(); //Get the reale absolute basepath
        \Drupal::logger('dataset_upload_basepath')->debug($base_path);
        $session->set('dataset_upload_basepath', $base_path);

        /**
        * Single netcdf file
        */
        if ($mime_type === 'application/x-netcdf') {
            \Drupal::messenger()->addMessage(t("got single netCDF file"));
            $session->set('upload_archive', 0);
            /**
             * Validate nc-file with compliance checker.
             */
            //$session->set('upload_archive', 'tfalse');
            $compliance = $this->check_compliance($base_path, $filename);
            $status = $compliance[0];



            /**
             * If compliance checker fails, redirect to new page and display the errors
             */
            if ($status !== 0) {
                $msg = "Compliance check failed for file: '.$filename.'. Please review issues, and try again with uptadated files.";
                $session->set('nird_fail_message', 'Compliance check failed for file:' .$filename. '. Please review issues, and try again with uptadated files.');
                $redirect = 'dataset_upload.ccfail';
                $out = $compliance[1];
                //dpm(gettype($out));
                $session->set("nird_failed", $out);
                $this->cleanUp($user_id); //Call cleaning method here.
                //$form_state->setRedirect($redirect);
                //return;
                $error = array();
                $error['error'] = [
                  '#type' => 'markup',
                  '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-red w3-pale-red w3-padding-16" id="mmd-message">',
              '#type' => 'markup',
              '#markup' => "<span>".$msg."</span>",
              '#suffix' => '</div>',
              '#allowed_tags' => ['div', 'span'],
                ];
                $error['error']['details'] = [
                  '#type' => 'details',
                  '#title' => $this->t('Show more details'),
                ];
                $error['error']['details']['msg'] = [
                  '#type' => 'markup',
                  '#markup' => implode(" ", $out),
                  '#allowed_tags' => ['div', 'table', 'tr', 'td', 'style','strong', 'script', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span'],
                ];

                $form_state->set('page', 1);
                $form_state->set('validation_error', $error);
                $form_state->setRebuild();
                return $form;
              /**
             * If compliance checker pass, then try to extract metadata from netCDF file with nc_to_mmd.py
             */
            } else {
                \Drupal::messenger()->addMessage(t("Compliance check pass!"));
                $input_file = \Drupal::service('file_system')->realpath($furi);
                //$upload_path = 'public://dataset_upload_folder/' . $user_id . '/' . $session_id . '/' . $uuid . '/';
                $fname = explode(".nc", $filename)[0];
                $ex_out_nctommd = $base_path. '/' .$fname .'.xml';
                //dpm($ex_out_nctommd);
                  $output_path = $base_path . '/'; //. '/' .$filename;
                  //dpm($sfp);
                  $mmdArr = $this->extractMMD($input_file, $output_path, $filename);
                //$mmdArr = $this->extractMmdMultiple([$input_file], $output_path, $filename);
                $mmd_status = $mmdArr[0];
                $mmd_metadata = $mmdArr[1];
                $mmd_output = $mmdArr[2];
                /**
                 * If metadata extraction fails. Redirect and display an error message
                 */
                if ($mmd_status !== 0) {
                    //$form_state->setValue('result1', '<div><span class="w3-red"><em>Error: </em></span><span> Could not extract metadata from file: '.$filename. '</span></div>');
                    $session->set('nird_failed_message', '<div><span class="w3-red"><em>Error: </em></span><span> Could not extract metadata from file: '.$filename. '</span></div>');
                    $session->set('nird_failed', $mmd_output);
                    $redirect = 'dataset_upload.mmdfail';
                    $this->cleanUp($user_id); //Call cleaning method here.
                  //  $form_state->setRedirect($redirect);
                  //  return;
                  $error = array();
                  $error['error'] = [
                    '#type' => 'markup',
                    '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-red w3-pale-red w3-padding-16" id="mmd-message">',
                '#type' => 'markup',
                '#markup' => "<span>Could not extract metadata from file: ".$filename ."</span>",
                '#suffix' => '</div>',
                '#allowed_tags' => ['div', 'span'],
                  ];
                  $error['error']['details'] = [
                    '#type' => 'details',
                    '#title' => $this->t('Show more details'),
                  ];
                  $error['error']['details']['msg'] = [
                    '#type' => 'markup',
                    '#markup' => (string) implode('',$mmd_output),
                  ];

                  $form_state->set('page', 1);
                  $form_state->set('validation_error', $error);
                  $form_state->setRebuild();
                  return $form;
                }
                /**
                 * If metadata extraction pass, go to next step in form, and display extracted metadata from the file(s)
                 */
                else {
                    $form_state->setValue('metadata', $mmd_metadata);
                    $this->metadata = $mmd_metadata;
                    $form_state->set('upoload_filename', $filename);
                    $form_state->setValue('filename', $filename);




                    //$form_state->setRebuild();
                    $form_state->set('page', 4);
                    $form_state->setRebuild();
                    //return $output;
                }
            }
        }

        /**
         * Archive of netCDF files
         */
        else {
            \Drupal::messenger()->addMessage(t("got archive of netCDF files"));

            //Set a session flag, that we have an archive with possible multiple files.
            $session->set('upload_archive', 1);

            $archiver = $this->archiverManager->getInstance($options);
            //\Drupal::messenger()->addMessage(t());
            //$archiver = $this->archiverManager->getInstance(['f‌​ilepath' => $file_path]);
            //$archiver = $archiver_service->getInstance();
            $archived_files = $archiver->listContents();
            $number_of_files = count($archived_files);
            $session->set('num_files', $number_of_files);
            $session->set('files_in_archive', $archived_files);

            \Drupal::messenger()->addMessage(t('Got archive with ' .count($archived_files) . ' files'));
            $archiver->extract($extract_path);
            //dpm($extract_path);
            //dpm($archived_files);

            $path = $base_path . '/extract';
            $compliance = $this->check_compliance_multiple($path, $archived_files);
            $status = $compliance[0];



            /**
             * If compliance checker fails, redirect to new page and display the errors
             */
            if ($status !== 0) {
                $session->set('nird_fail_message', "Compliance check failed for one ore more file in archive. Please review issues, and try again with uptadated files.");
                $redirect = 'dataset_upload.ccfail';
                $out = $compliance[1];
                $msg = "Compliance check failed for one ore more file in archive. Please review issues, and try again with uptadated files.";
                $session->set("nird_failed", $out);
                $this->cleanUp($user_id); //Call cleaning method here.
              //  $form_state->setRedirect($redirect);
              //  return;
              $error = array();
              $error['error'] = [
                '#type' => 'markup',
                '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-red w3-pale-red w3-padding-16" id="mmd-message">',
            '#type' => 'markup',
            '#markup' => "<span>".$msg."</span>",
            '#suffix' => '</div>',
            '#allowed_tags' => ['div', 'span'],
              ];
              $error['error']['details'] = [
                '#type' => 'details',
                '#title' => $this->t('Show more details'),
              ];
              $error['error']['details']['msg'] = [
                '#type' => 'markup',
                '#markup' => implode(" ", $out),
                '#allowed_tags' => ['div', 'table', 'tr', 'td', 'style','strong', 'script', 'img', 'a', 'span', 'h3', 'h4', 'h5', 'br', 'span'],
              ];

              $form_state->set('page', 1);
              $form_state->set('validation_error', $error);
              $form_state->setRebuild();
              return $form;
            //Do metadata extraction of all files
            } else {
                \Drupal::messenger()->addMessage(t("Compliance check pass for all files in archive!"));
                //dpm($ex_out_nctommd);
                $input_path = $base_path . '/extract';
                $output_path = $base_path . '/'; //. '/' .$filename;
                //dpm($sfp);
                $mmdArr = $this->extractMmdMultiple($archived_files, $output_path, $input_path);
                $mmd_status = $mmdArr[0];
                $mmd_metadata = $mmdArr[1];
                $mmd_output = $mmdArr[2];
                /**
                 * If metadata extraction fails. Redirect and display an error message
                 */
                if ($mmd_status !== 0) {
                    //$form_state->setValue('result1', '<div><span class="w3-red"><em>Error: </em></span><span> Could not extract metadata from file: '.$filename. '</span></div>');
                    $session->set('nird_failed_message', '<div><span class="w3-red"><em>Error: </em></span><span> Could not extract metadata from one or more files in archive</span></div>');
                    $session->set('nird_failed', $mmd_output);
                    $redirect = 'dataset_upload.mmdfail';
                    $this->cleanUp($user_id); //Call cleaning method here.
                    //$form_state->setRedirect($redirect);
                    //return;
                    $error = array();
                    $error['error'] = [
                      '#type' => 'markup',
                      '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-red w3-pale-red w3-padding-16" id="mmd-message">',
                  '#type' => 'markup',
                  '#markup' => "<span>Could not extract metadata from one or more files in archive</span>",
                  '#suffix' => '</div>',
                  '#allowed_tags' => ['div', 'span'],
                    ];
                    $error['error']['details'] = [
                      '#type' => 'details',
                      '#title' => $this->t('Show more details'),
                    ];
                    $error['error']['details']['msg'] = [
                      '#type' => 'markup',
                      '#markup' =>(string) $mmd_output,
                    ];

                    $form_state->set('page', 1);
                    $form_state->set('validation_error', $error);
                    $form_state->setRebuild();
                    return $form;

                }
                /**
                 * If metadata extraction pass, go to next step in form, and display extracted metadata from the file(s)
                 */
                else {
                    $form_state->setValue('metadata', $mmd_metadata);
                    $this->metadata = $mmd_metadata[0];

                    $form_state->setValue('filename', $archived_files);


                    //$form_state->setRebuild();
                    //$form_state->set('page', 2);
                    //$form_state->setRebuild();
                }
            }
        }
        //CALL API:
        //Get the account config, call NIRD API and get a authentication token.
        $config = \Drupal::config('dataset_upload.settings');
        $nird_username = $config->get('nird_username');
        $nird_password = $config->get('nird_password');
        $nird_api_base_uri = $config->get('nird_api_base_uri');
        $nird_api_token_endpoint = $config->get('nird_api_token_endpoint');

        //Get the access token
        $response = $this->getToken();
        $access_token = $response['access_token'];
        $token_type = $response['token_type'];
        $form_state->set('token', $access_token);
        $form_state->set('token_type', $token_type);

        //Get the state controlled vocabulary
        $state = $this->getState($access_token, $access_token);
        $form_state->set('api_state', $state);

        //Get the category controlled vocabulary
        $category = $this->getCategory($access_token, $access_token);
        $form_state->set('api_category', $category);

        //Get the registered licences
        $this->licences = $this->getLicence($access_token, $access_token);

        //Get all subjects
        $subjects = $this->getSubject($access_token, $access_token);
        $form_state->set('api_subjects', $subjects);

        //Add to session variables:
        $session = \Drupal::request()->getSession();
        $session->set('access_token', $access_token);
        $session->set('token_type', $token_type);

        $form_state->set('page', 4);
        $form_state->setRebuild();

    }


    /** Wrapper function to test multiple datasets giving a list of files
      *
      */
    private function check_compliance_multiple($path, $files)
    {
        $status = 0;
        $out = [];
        //Loop through the files
        foreach ($files as $file) {
            //Do compliance check of all files
            $compliance = $this->check_compliance($path, $file);
            $f_status = $compliance[0];
            $status = $status + $f_status;
            if ($status !== 0) {
                $f_out = $compliance[1];
                $out = array_merge($out, $f_out);
            }
        }
        return [$status, $out];
    }

    /**
     * Check dataset compliance using compliance-checker
     */

    private function check_compliance($path, $filename)
    {
        $session = \Drupal::request()->getSession(); //Get current user session
        $uuid = $session->get('current_upload_uuid'); //Get the stored uuid for this form submission

        \Drupal::logger('dataset_upload_check_compliance_filename')->debug($filename);
        \Drupal::logger('dataset_upload_check_compliance_path')->debug($path);



        $user_id = $this->currentUser->id(); //Get the user ID
        //Get the selected tests to run
        $test1 = 'cf:1.6';
        $test2 = 'acdd';
        //\Drupal::logger('dataset_validation')->debug("test1 :" . $test1);
        //\Drupal::logger('dataset_validation')->debug("test2 :" . $test2);

        //create output files for the checker
        //$name = explode(".nc", $filename)[0];
        $name_out_cf = $filename.'_cf.html';
        $name_out_acdd = $filename.'_acdd.html';

        //$fdir = drupal_realpath('public://');
        $fdir = \Drupal::service('file_system')->realpath('public://');

        \Drupal::logger('dataset_upload')->debug("extracted fdir : " .$fdir);
        \Drupal::logger('dataset_upload')->debug("extracted path : " .$path);
        $out = null;
        $out1 = null;
        $out2 = null;
        $status = null;
        $status1 = null;
        $status2 = null;

        $filesystem = \Drupal::service('file_system');
        //$ex_out_cf = $fdir.'/dataset_validation_folder/'.$name_out_cf;
        //$ex_out_acdd = $fdir.'/dataset_validation_folder/'.$name_out_acdd;
        $ex_out_cf = '/tmp/'.$user_id . '/' .$uuid . '/'.$name_out_cf;
        $ex_out_acdd = '/tmp/'.$user_id . '/' .$uuid . '/'.$name_out_acdd;
        \Drupal::logger('dataset_upload')->debug("outfile CF: " . $ex_out_cf);
        \Drupal::logger('dataset_upload')->debug("outfile ACDD: " . $ex_out_acdd);


        //\Drupal::logger('dataset_')->debug("running CF  and ACDD compliance checks");
        //\Drupal::logger('dataset_validation')->debug('compliance-checker -v -c lenient --format=html --output='.$ex_out_cf.' --test='.$test1.' '.$fdir.'/'.$path);
        //\Drupal::messenger()->addMessage(t("You are testing you dataset \"".$filename."\" against CF-1.6 and ACDD convention"), 'status');
        \Drupal::logger('compliance_checker_cmd')->debug('compliance-checker -v -c lenient -f html -o - --test='.$test1.' '.$path.'/'.$filename);
        exec('compliance-checker -v -c lenient -f html -o - --test='.$test1.' '.$path.'/'.$filename, $out1, $status1);
        exec('compliance-checker -v -c lenient -f html -o - --test='.$test2.' '.$path.'/'.$filename, $out2, $status2);

        //exec('compliance-checker -v -c lenient -f html -o - --test='.$test1.' '.$fdir.'/'.$path, $out1, $status);
        //exec('compliance-checker -v -c lenient -f html -o - --test='.$test2.' '.$fdir.'/'.$path, $out2, $status2);
        $status = $status1 + $status2;
        \Drupal::logger('dataset_validation')->debug("got status: " . $status);
        $out = array_merge($out1, $out2);
        //put together the html outputs


        //Clean up the temporary html files:
        //$filesystem->delete($ex_out_cf);
        //$filesystem->delete($ex_out_acdd);

        //Prepare and return status and output
        $retArr = [$status, $out];
        return $retArr;
    }

    /**
     * Extract metadata from multiple netcdf files, and present to user.
     */

    private function extractMmdMultiple($files, $output_path, $input_path)
    {
        $status = 0;
        $out = [];
        $metadataArr = [];
        //Loop through the files
        foreach ($files as $file) {
            //Do compliance check of all files
            $input_file = $input_path .'/' . $file;
            \Drupal::logger('nc_to_mmd_input_file')->debug($input_file);
            $mmd = $this->extractMMD($input_file, $output_path, $file);
            $f_status = $mmd[0];
            $metadataArr[] = $mmd[1];
            $status = $status + $f_status;
            if ($status !== 0) {
                $f_out = $mmd[2];
                $out = array_merge($out, $f_out);
            }
        }
        return [$status, $metadataArr, $out];
    }
    /**
     * Extract metadata from netCDF file, and present to user.
     */

    private function extractMMD($input_file, $output_path, $filename)
    {
        //$ex_out_nctommd = $full_path_to_folder.'/'.$sfn.'.xml'
        $out_nctommd = null;
        $status_nctommd = null;
        //\Drupal::messenger()->addMessage(t('nc to mmd input file: '.$input_file));
        //\Drupal::messenger()->addMessage(t('nc to mmd output path: '.$output_path));
        exec('/usr/local/bin/nc_to_mmd ' .$input_file . ' ' .$output_path . ' 2>&1', $out_nctommd, $status_nctommd);
      //  dpm('mmd status:' .$status_nctommd);
        //dpm($out_nctommd);
        if ($status_nctommd === 0) {
            //get xml file content
          $xml_content = file_get_contents($output_path . substr($filename, 0, -3) . '.xml'); // this is a string from gettype
          //get xml object iterator
           $xml = new \SimpleXmlIterator($xml_content); // problem with boolean
           //$xml = simplexml_load_file($xml_content):
           //get xml object iterator with mmd namespaces
           $xml_wns = $xml->children($xml->getNamespaces(true)['mmd']);
            $metadata[] = $this->depth_mmd("", $xml_wns);
        } else {
            $metadata = 'FAILED';
        }
        $retArr = [$status_nctommd, $metadata, $out_nctommd];
        return $retArr;
    }


    // extract mmd to the last child
    private function depth_mmd($prefix, $iterator)
    {
        $kv_a = array();
        foreach ($iterator as $k => $v) {
            if ($iterator->hasChildren()) {
                $kv_a = array_merge($kv_a, $this->depth_mmd($prefix . ' ' . $k, $v));
            } else {
                //add mmd keys and values to form_state to be passed to the second page.
                $kv_a[] = array($prefix . ' ' . $k, (string)$v);
            }
        }
        return $kv_a; //this function returns an array of arrys
    }


    private function nird_api($base_uri, $endpoint, $json, $method = 'POST')
    {
        \Drupal::logger('nird_api')->debug("Base uri : @string", ['@string' => $base_uri ]);
        \Drupal::logger('nird_api')->debug("Endpoint : @string", ['@string' => $endpoint ]);
        \Drupal::logger('nird_api')->debug("Method : @string", ['@string' => $method ]);
        try {
            $client = \Drupal::httpClient(['base_uri' => $base_uri]);

            //$client->setOptions(['debug' => TRUE]);
            $request = $client->request(
                $method,
                $endpoint,
                [
         'json' =>  $json,

         'debug' => true,
       ],
            );

            $responseStatus = $request->getStatusCode();
            \Drupal::logger('nordatanet_nird')->debug("response status from" . $backend_uri . " : @string", ['@string' => $responseStatus ]);
            $data = (string) $request->getBody();
        } catch (Exception $e) {
            \Drupal::messenger()->addError("Could not  api at @uri .", [ '@uri' => $backend_uri]);
            \Drupal::messenger()->addError($e);
            return [$responseStatus, $e];
        }

        return [$responseStatus, $data];
    }

    private function getToken()
    {

  //Get the account config, call NIRD API and get a authentication token.
        $config = \Drupal::config('dataset_upload.settings');
        $nird_username = $config->get('nird_username');
        $nird_password = $config->get('nird_password');
        $nird_api_base_uri = $config->get('nird_api_base_uri');
        $nird_api_token_endpoint = $config->get('nird_api_token_endpoint');
        //\Drupal::logger('nird')->debug($nird_password);
        //$session = \Drupal::request()->getSession(); //Get current user session
        //$user_id = $this->currentUser->id(); //Get the user ID
        //$session_id = $session->getId();

        $client = \Drupal::httpClient();
        $res = $client->post($nird_api_base_uri . $nird_api_token_endpoint, [
    'form_params' => [
        'grant_type' => '',
        'username' => $nird_username,
        'password' => $nird_password,
        'client_id' => '',
        'client_secret' => '',
    ],
    'debug' => false,
]);
        $json = (string )$res->getBody();

        return Json::decode($json);
    }

    private function getCategory($token, $token_type)
    {

  //Get the account config, call NIRD API and get a authentication token.
        $config = \Drupal::config('dataset_upload.settings');
        $nird_api_base_uri = $config->get('nird_api_base_uri');
        $nird_api_category_endpoint = $config->get('nird_api_category_endpoint');
        //\Drupal::logger('nird')->debug($nird_password);
        //$session = \Drupal::request()->getSession(); //Get current user session
        //$user_id = $this->currentUser->id(); //Get the user ID
        //$session_id = $session->getId();

        $client = \Drupal::httpClient();

        $response = $client->get(
            $nird_api_base_uri . $nird_api_category_endpoint,
            [

    'debug'   => false,
    'headers' =>
        [
            'Authorization' => "{$token_type} {$token}"
        ]
    ]
        )->getBody()->getContents();

        $json = (string) $response;
        $result = Json::decode(Json::decode($json));
        return $result['category'];
    }
    private function getState($token, $token_type)
    {

  //Get the account config, call NIRD API and get a authentication token.
        $config = \Drupal::config('dataset_upload.settings');
        $nird_api_base_uri = $config->get('nird_api_base_uri');
        $nird_api_category_endpoint = $config->get('nird_api_state_endpoint');
        //\Drupal::logger('nird')->debug($nird_password);
        //$session = \Drupal::request()->getSession(); //Get current user session
        //$user_id = $this->currentUser->id(); //Get the user ID
        //$session_id = $session->getId();

        $client = \Drupal::httpClient();

        $response = $client->get(
            $nird_api_base_uri . $nird_api_category_endpoint,
            [

    'debug'   => false,
    'headers' =>
        [
            'Authorization' => "{$token_type} {$token}"
        ]
    ]
        )->getBody()->getContents();

        $json = (string) $response;
        $result = Json::decode(Json::decode($json));
        return $result['state'];
    }
    private function getSubject($token, $token_type)
    {

  //Get the account config, call NIRD API and get a authentication token.
        $config = \Drupal::config('dataset_upload.settings');
        $nird_api_base_uri = $config->get('nird_api_base_uri');
        $nird_api_category_endpoint = $config->get('nird_api_subject_endpoint');
        //\Drupal::logger('nird')->debug($nird_password);
        //$session = \Drupal::request()->getSession(); //Get current user session
        //$user_id = $this->currentUser->id(); //Get the user ID
        //$session_id = $session->getId();

        $client = \Drupal::httpClient();

        $response = $client->get(
            $nird_api_base_uri . $nird_api_category_endpoint,
            [

    'debug'   => false,
    'headers' =>
        [
            'Authorization' => "{$token_type} {$token}"
        ]
    ]
        )->getBody()->getContents();

        $json = (string) $response;
        $result = Json::decode(Json::decode($json));
        return $result['identifiers'];
    }
    private function getLicence($token, $token_type)
    {

  //Get the account config, call NIRD API and get a authentication token.
        $config = \Drupal::config('dataset_upload.settings');
        $nird_api_base_uri = $config->get('nird_api_base_uri');
        $nird_api_category_endpoint = $config->get('nird_api_license_endpoint');
        //\Drupal::logger('nird')->debug($nird_password);
        //$session = \Drupal::request()->getSession(); //Get current user session
        //$user_id = $this->currentUser->id(); //Get the user ID
        //$session_id = $session->getId();

        $client = \Drupal::httpClient();

        $response = $client->get(
            $nird_api_base_uri . $nird_api_category_endpoint,
            [

    'debug'   => false,
    'headers' =>
        [
            'Authorization' => "{$token_type} {$token}"
        ]
    ]
        )->getBody()->getContents();

        $json = (string) $response;
        //$result = Json::decode(Json::decode($json));
        $result = Json::decode($json);
        return $result['licences'];
    }

    public function addArticleCallback(array &$form, FormStateInterface $form_state)
    {
        return   $form['dataset']['article']['publication'];
    }
    public function addArticle(array &$form, FormStateInterface $form_state)
    {
        //$response = new AjaxResponse();

        $num_articles = $form_state->get('num_articles');
        //  if (empty($num_articles)) {

        //  }
        \Drupal::logger('nordatanet_nird')->debug("number of articles before: " . $num_articles);
        $add_article = $num_articles +1;
        \Drupal::logger('nordatanet_nird')->debug("number of articles after: " . $add_article);

        $form_state->set('num_articles', $add_article);
        $form_state->setRebuild();
        /*
          $article['dataset']['article']['publication'][$num_articles]['published'] = [
            '#type' => 'select',
            '#title' => $this->t('Published'),
            '#options' => [TRUE =>'Yes', FALSE => t'No'],
            '#empty_option' => $this->t('- Select published status -'),
            '#required' => TRUE,
          ];
          $article['dataset']['article']['publication'][$num_articles]['doi'] = [
            '#type' => 'url',
            '#title' => $this->t('DOI reference'),
            '#required' => TRUE,
          ];

          //if($num_articles > 1) {
          $article['dataset']['article']['publication'][$num_articles]['primary'] = [
            '#type' => 'hidden',
            '#default_value' => 0,
          ];

          $response->addCommand(new AppendCommand('#publication-wrapper', $article));
          return $response;
          */
//  return $form;
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
