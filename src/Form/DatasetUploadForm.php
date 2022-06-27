<?php
/*
 * @file
 * Contains \Drupal\dataset_upload\DatasetUploadForm
 *
 * Form for registering NIRD datasets
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
//use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AppendCommand;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\Exception;

use Drupal\Core\Render\Markup;
use Drupal\Component\Render\FormattableMarkup;


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
   * Drupal\dataset_upload\AttributeExtractor definition.
   *
   * @var AttributeExtractor $attributeExtractor
   */
    protected $attributeExtractor;


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
        $instance->attributeExtractor = $container->get('dataset_upload.attribute_extractor');

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
     * Build form. Calling other buildForm function using hte page attribute in the form stage.
     * for simulating forms with steps.
     *
     * @param $form
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

        //Get the config of the module
        $config = self::config('dataset_upload.settings');

        //Get the current user object.
        $user = \Drupal\user\Entity\User::load($this->currentUser->id());

        /**
         * TODO: Check if user have all the fields filled out.
         *       if not, redirect to user edit form
         */


        /**
         * Check if the logged in user have registered all user fields including
         * custom user fields required for the depositor.
         * If the fields are null or empty string, redirect to user form
         * for filling out those fields.
         */


        /**
         * Test witch step/form page we are on, and call the corresponding buildForm
         * function for that step/page
         */


        //Give an error if something went wrong with NIRD API call.
        if ($form_state->has('nird_error')) {
            $form['nird-error'] = [
            '#type' => 'markup',
           '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-red w3-pale-red w3-padding-16" id="nird-message">',
           '#markup' => '<span>Someting went wrong!! </span>',
           '#suffix' => '</div>',
           '#allowed_tags' => ['div', 'span','strong'],
         ];

            $form = self::formPageFive($form, $form_state);
            $mailManager = \Drupal::service('plugin.manager.mail');
            $module = 'dataset_upload';
            $key = 'nird_api_error';
            $to = 'magnarem@met.no';
            $params['message'] = t('Something went wrong registering the dataset\n The NIRD API JSON request was:\n' . $form_state->get('json'). '\n and the NIRD API response was:\n ' . $form_state->get('nird_error'));
            $params['user'] = $user->getEmail();
            //$params['json'] = $form_state->get('json');

            //$params['doi'] = 'https://doi.org/10.21203/rs.3.rs-361384/v1';
            //$params['doi'] = $data->doi;

            $langcode = $user->getPreferredLangcode();
            $send = true;

            $result = $mailManager->mail($module, $key, $to, $langcode, $params, null, $send);
            //dpm($result);
            $this->cleanUp($this->currentUser->id(), $form_state);
            return $form;
        }

        //Return form for confirming services.
        if ($form_state->has('page') && $form_state->get('page') == 2) {
            //dpm('building dataset form');

            return self::confirmServicesForm($form, $form_state);
            //return self::buildDatasetForm($form, $form_state);
        }

        //Return form for confirming aggregation.
        if ($form_state->has('page') && $form_state->get('page') == 3) {
            return self::confirmAggregationForm($form, $form_state);
        }

        //NOT IN USE AT THE MOMENT
        if ($form_state->has('page') && $form_state->get('page') == 4) {
            return self::registerMissing($form, $form_state);
        }

        //Build the main dataset form for filling out metadata of the uploaded dataset(s).
        if ($form_state->has('page') && $form_state->get('page') == 5) {
            return self::buildDatasetForm($form, $form_state);
            //return self::confirmServicesForm($form, $form_state);
        }

        //Return the confirmation form
        if ($form_state->has('page') && $form_state->get('page') == 6) {

            //Cleanup now during development,
            //$this->cleanUp($this->currentUser->id(), $form_state);
            return self::registrationConfirmedForm($form, $form_state);
            //return self::confirmServicesForm($form, $form_state);
        }

        //Do some cleaning up, if a form was started and not finished.
        if ($session->has('dataset_upload_status')) {
            $status = $session->get('dataset_upload_status');
            if ($status !== 'registered') {
                \Drupal::logger('dataset_upload')->debug("Unsubmitted form found...cleaning up userid: " . $this->currentUser->id());
                $this->cleanUp($this->currentUser->id(), $form_state);
            }
        }


        /**
         * First page/step. Get the dataset_validation form
         */
        //Set form page/step
        $form_state->set('page', 1);
        //Set the upload path
        $form_state->set('upload_basepath', 'private://dataset_upload_folder/');
        //Get the upload valitation form
        $form = parent::buildForm($form, $form_state);

        //Empty the tests (ie. invoke manual)
        $form['container']['creation']['test'] = []; //[

        $form['container']['message'] = [
          '#prefix' => '<div class="w3-card">',
          '#type' => 'markup',
          '#markup' => Markup::create($config->get('helptext_upload')['value']),
          '#suffix' => '</div>',
        ];

        if ($form_state->has('validation_message')) {
            $form_state->get('validation_message');

            foreach ($validation_message as $msg) {
                $form['container']['validation_message'][] = [
              '#type' => 'markup',
              '#prefix' => '<p>',
              '#suffix' => '</p>',
              '#markup' => $msg,
            ];
            }
        }

        /*
         * RETURN USER TO USER EDIT FORM IF MISSING First and last names
         *
         */
        if (!isset($user->field_first_name) || !isset($user->field_last_name)) {
            $form_state->setRedirect('entity.user.edit_form');
        }

        return $form;
    }

    /**
     * Build dataset registration form
     *
     * @param $form
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
        ///Get the config object from config factory.
        $config = self::config('dataset_upload.settings');

        $form['#tree'] = true;
        /*      $form['validation-message'] = [
            '#type' => 'markup',
            '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-green w3-pale-green w3-padding-16" id="nird-message">',
            '#markup' => '<span>Your dataset(s) is compliant with CF and ACDD standards. The submission can now proceed.</span>',
            '#suffix' => '</div>',
            '#allowed_tags' => ['div', 'span'],
          ];
        */

        $form['container']['message'] = [
      '#prefix' => '<div class="w3-card">',
      '#type' => 'markup',
      '#markup' => Markup::create($config->get('helptext_dataset')['value']),
      '#suffix' => '</div>',
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
  '#title' => $this->t("Show extracted metadata"),
];


        /**
         * Show a table of extracted netCDF metadata (ACDD)
         */

        $table_data = [];
        foreach ($metadata as $key => $value) {
            array_push($table_data, [(string) $key, (string) $value]);
        }
        $form['validation']['extracted_metadata']['metadata'] = [
    '#type' => 'table',
    '#caption' => 'Extracted metadata for ' .$form_state->get('filename'),
    '#header' => ['Metadata Key', 'Metadata Value'],
    //'#rows' => array(array_keys($metadata),array_values($metadata)),
    '#rows' => $table_data,
    ];

        if ($form_state->has('archived_files')) {
            /**
             * Special logic for archived netCDF files goes here
             */
        }
        //$metadata = [];
        $prefill = [];
        /*  for ($i = 0; $i < sizeof($metadata); $i++) {
              $key =$metadata[$i][0];
              $prefill+= [$key=>$metadata[$i][1]];
          }
*/

        /**
         * Build the dataset registration form
         */

        $form['dataset'] = [
  '#type' => 'fieldset',
  '#title' => $this->t("Dataset information"),
  //'#open' => true,
//  '#attributes' => [
    //'class' => ['w3-border-green', 'w3-border'],
//  ],

  ];

        $form['dataset']['message'] = array(
'#type' => 'markup',
'#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-yellow w3-pale-yellow w3-padding-16" id="dataset-message">',
'#type' => 'markup',
'#markup' => '<span>Fill out all required fields in the form. Check that prefilled values are correct, and correct them.</span>',
'#suffix' => '</div>',
'#allowed_tags' => ['div', 'span'],
);
        /**
         * Dataset title
         */

        $form['dataset']['title'] = [
  '#type' => 'textfield',
  '#title' => $this->t("Title"),
  '#default_value' => isset($metadata['title']) ? $metadata['title'] : '', //$prefill[' title'],
  '#size' => 120,
  '#required' => true,
  '#disabled' => isset($metadata['title']),
  ];

        /**
         * description
         */


        $form['dataset']['description'] = [
  '#type' => 'textarea',
  '#title' => $this->t("Description"),
  '#value' => isset($metadata['summary']) ? $metadata['summary'] : '', //$prefill[' abstract'],
  '#size' => 120,
  '#required' => true,
  '#disabled' => isset($metadata['summary']),
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

        /*
                if (isset($metadata['id'])) {
                    $form['dataset']['external_identifier'] = [
        '#type' => 'textfield',
        '#title' => $this->t("External identifier"),
        '#default_value' => isset($metadata['id']) ? $metadata['id'] : '', //$prefill[' title'],
        '#size' => 120,
        '#required' => true,
        //'#disabled' => true,
        ];
                }
                */
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



        $licences = $form_state->get('api_licences');
        $licence = array_column($licences, 'id');
        $licence_name = array_column($licences, 'name');
        /*  foreach($lics as $lic ) {
          $licence[] = $licences['licence'];
        }*/
        //dpm($licences);
        $form['dataset']['licence'] = [
  '#type' => 'select',
  '#title' => $this->t("Licence"),
  '#empty_option' => $this->t('- Select licence -'),
  '#options' => array_combine($licence, $licence_name),
  //'#options' => [ 'NLOD' => 'NLOD'],
  //'#default_option' => $metadata['license'],
  '#required' => true,
  //    '#ajax' => [
  //      'callback' => '::licenceSelectCallback',
  //      'wrapper' => 'licence-info',
  //  ],
  ];
        /*
          $form['dataset']['licence-info'] = [
          '#type' => 'markup',
          '#prefix' => '<div id="licence-info">',
          '#markup' => '',
          '#suffix' => '</div>',
          '#allowed_tags' => ['div', 'tr' ,'li'],
          ];
        */

        /**
         * created date
         */

        $date = isset($metadata['date_created']) ? $metadata['date_created'] : date(DATE_ISO8601);
        $date_format = 'Y-m-d';
        $time_format = 'H:i:s';
        $created_date = explode('T', $date)[0];
        $created_time = substr(explode('T', $date)[1], 0, -1);
        //dpm($created_date);
        //dpm($created_time);
        //$date_time_format = trim($date_format . ' ' . $time_format);
        //$date_time_input = trim($created_date . ' ' . $created_time);
        $timezone = $this->currentUser->getTimeZone();
        $form['dataset']['created'] = [
  '#type' => 'date',
  '#title' => $this->t("Created"),
  //'#default_value' => DrupalDateTime::createFromFormat($date_format, $created_date, $timezone),
  '#default_value' => $created_date,
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
  //'#title' => $this->t(),
  //'#prefix' => '<div id="article-wrapper">',
  //'#suffix' => '</div>',
  //'#open' => TRUE,
  //'#tree' => true,
  //'#attributes' => [
  //'class' => ['w3-border-green', 'w3-border'],
  //],

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
          '#discription' => $this->t('Add publications related to this dataset. The first publication added will be consiedered the primary publication. If no publication add motivation/comment.'),
          //'#prefix' => '<div id="publication-wrapper">',
          //'#suffix' => '</div>',
          '#attributes' => ['id' => 'publication-wrapper'],
          //'#tree' => TRUE,
        ];

        //$form['#tree'] = TRUE;
        //for ($i = 0; $i < $num_articles; $i++) {
        //for ($i = 0; $i < $num_articles; $i++) {
        if (null == $form_state->getValue(['dataset','article','publication','article-select'])) {
            $publishedValue = 'published';
        } else {
            $publishedValue = $form_state->getValue(['dataset','article','publication','article-select']);
        }
        $form['dataset']['article']['publication']['article-select'] = [
  '#type' => 'radios',
  '#title' => $this->t('Enter DOI if this dataset have a published article. If not enter a reason/motivation'),
  //'#empty_option' => $this->t('- Select published status -'),

  '#options' => ['published' =>'Yes', 'no_publication' => 'No'],
  '#default_value' => $publishedValue,
  '#required' => true,
  //'#name' => 'article-select',
  '#attributes' => [
        //define static name and id so we can easier select it
        // 'id' => 'colour_select',
        //'name' => 'article-select',
      ],
  /*  '#ajax' => [
      'callback' => '::publicationSelectCallback',
      'wrapper' => 'publication-wrapper',
      'event' => 'change',
      'disable-refocus' => true,

    ],
  */  //'#prefix' => '<div id="article-published">',
    //'#suffix' => '</div>',

  ];
        //if (null == $form_state->getValue(['dataset','article','publication','select'])) {
        $form['dataset']['article']['publication']['published']['reference']['doi']  = [
          '#type' => 'textfield',
          '#title' =>  $this->t('DOI reference'),
          //'#required' => true,
          '#states'=> [
            'invisible' => [
                ':input[name="dataset[article][publication][article-select]"]' => ['value' => 'no_publication'],
              ],
          'visible' => [
              ':input[name="dataset[article][publication][article-select]"]' => ['value' => 'published'],
            ],
            'required' => [
                ':input[name="dataset[article][publication][article-select]"]' => ['value' => 'published'],
            ],
          ],

            ];
        $form['dataset']['article']['publication']['no_publication']['motivation']  = [
          '#type' => 'textfield',
          '#title' =>  $this->t('Motivation'),
          //'#required' => true,
          '#states'=> [
            'invisible' => [
                ':input[name="dataset[article][publication][article-select]"]' => ['value' => 'published'],
              ],
            'visible' => [
              ':input[name="dataset[article][publication][article-select]"]' => ['value' => 'no_publication'],
                ],
                'required' => [
                    ':input[name="dataset[article][publication][article-select]"]' => ['value' => 'no_publication'],
                ],
             ],

            ];
        //}
        /*
              $form['dataset']['article']['publication']['published'] = [
            '#pefix' => '<div id="published">',
            '#suffix' => '</div>',
          ];

              $form['dataset']['article']['publication']['no_publication']  = [
              '#pefix' => '<div id="no-publication">',
              '#suffix' => '</div>',
            ];
*/
        /**
         * ADD AJAX CALLBACK FOR THE CORRECT FORM IF IT IS PUBLISHED OR NOT
         */
        /*
                $form['dataset']['article']['publication']['reference']['doi'] = [
          '#type' => 'textfield',
          '#title' =>  $this->t('DOI reference'),
          '#states' => [
            'required' => [
              ':input[name="publication-published"]' => ['value' => 1],
            ],
          //  'and',
            'visible' => [
              ':input[name="publication-published"]' => ['value' => 1],
            ],
            'optional' => [
              ':input[name="publication-published"]' => ['value' => 0],
            ],
            //'invisible' => [
            //  ':input[name="publication-published"]' => ['value' => 0, 'value' => NULL],
            //],
          ],
        ];*/
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
                  '#value' => $this->t('Add another'),
                  '#submit' => ['::addArticle'],
                  '#ajax' => [
                       'callback' => '::addArticleCallback',
                       'wrapper' => 'publication-wrapper',


                  ],
              );

        if ($num_articles > 1) {
            $form['dataset']['article']['publication']['actions']['remove_article'] = [
        '#type' => 'submit',
        '#value' =>  $this->t('Remove one'),
        '#submit' => ['::removeCallback'],
        '#ajax' => [
          'callback' => '::addArticleCallback',
          'wrapper' => 'publication-wrapper',
        ],
  ];
        }
  */
        /**
         * depositor
         */



        // $depositor_type = $form_state->get('contributor_type');
        // $depositor_type_count = array_count_values($depositor_type);
        // \Drupal::logger('dataset_upload')->debug('depositor count <pre><code>' . print_r($depositor_type_count, true) . '</code></pre>');
        // $depositor_person_count = (int) $depositor_type_count['person'];
        // $depositor_org_count = (int) $depositor_type_count['organization'];
        // if (!$form_state->has('depositor_person_count')) {
        //     $form_state->set('depositor_person_count', $depositor_person_count);
        // }
        // if (!$form_state->has('depositor_org_count')) {
        //     $form_state->set('depositor_org_count', $depositor_org_count);
        // }
        //
        // $depositor_name = explode(', ', $metadata['contributor_name']);
        // $depositor_email = explode(', ', $metadata['contributor_email']);
        // $depositor_url = explode(', ', $metadata['contributor_url']);
        //
        // $depositors = $depositor_org_count + $depositor_person_count;
        //
        // if (!$form_state->has('added_depositor_persons')) {
        //     $form_state->set('added_depositor_persons', 0);
        // }
        // if ($form_state->has('added_depositor_persons')) {
        //     $depositors = $depositors + (int) $form_state->get('added_depositor_persons');
        //     $depositor_person_count = $depositor_person_count + (int) $form_state->get('added_depositor_persons');
        // }
        // if (!$form_state->has('added_depositor_orgs')) {
        //     $form_state->set('added_depositor_orgs', 0);
        // }
        // if ($form_state->has('added_depositor_orgs')) {
        //     $depositors = $depositors + (int) $form_state->get('added_depositor_orgs');
        // }
        // if (is_null($depositors) || $depositors === 0) {
        //     $depositors = 1;
        // }
        //
        // $form_state->set("depositor_count", $depositors);
        // \Drupal::logger('dataset_upload')->debug('Num of depositors: ' . $depositors);

        $depositor_name = $form_state->get('contributor_name');
        $depositor_email = $form_state->get('contributor_email');
        $depositor_url = $form_state->get('contributor_url');
        $depositor_type = $form_state->get('contributor_type');
        $depositor_role = $form_state->get('contributor_role');

        $form['dataset']['depositor'] = [
  '#type' => 'fieldset',
  '#title' =>  $this->t('Depositor(s)'),
  '#description' =>  $this->t('The person or gr oup of people that contributed to the archiving of the dataset.'),
  //'#tree' => true,
  '#prefix' => '<div id="depositor-wrapper">',
  '#suffix' => '</div>',
  ];


        //Prefill logged in user as primary uploader depositor
        $i=0;
        $form['dataset']['depositor'][$i]['member'] = [
'#type' => 'details',
'#title' =>  $this->t('Person(s)'),
'#open' => true,
];


        $form['dataset']['depositor'][$i]['member']['firstname'] = [
'#type' => 'textfield',
'#title' => $this
->t('First name'),
'#default_value' => isset($user->field_first_name->value) ? $user->field_first_name->value : "",
'#required' => true,
//'#default_value' => $user->get('field_first_name'),
'#disabled' => false,
];

        $form['dataset']['depositor'][$i]['member']['lastname'] = [
'#type' => 'textfield',
'#title' => $this
->t('Last name'),
'#default_value' => isset($user->field_last_name->value) ? $user->field_last_name->value : "",
'#required' => true,
//  '#default_value' => $user->get('field_last_name'),
'#disabled' => false,
];
        $form['dataset']['depositor'][$i]['member']['email'] = [
'#type' => 'email',
'#title' => $this
->t('Email'),
'#default_value' => $this->currentUser->getEmail(),
'#required' => true,
'#disabled' => false,
];
        $form['dataset']['depositor'][$i]['member']['federatedid'] = [
'#type' => 'hidden',
'#title' => $this
->t('Federated ID'),
'#default_value' => (string) $this->currentUser->id(),
'#required' => true,
'#disabled' => true,
];
        $uploader = false;
        $type = 'hidden';
        if ($i == 0) {
            $uploader = true;
            $type = 'checkbox';
        }
        $form['dataset']['depositor'][$i]['uploader'] = [
'#type' => $type,
'#title' =>  $this->t('Uploader'),
'#description' =>  $this->t('The primary depositor are considered as the uploader'),
'#value' => $uploader,
'#disabled' => true,
//  '#states' => [
//    'checked' => [
//      ':input[name="uploader"]' => [
//        'checked' => true,
//      ],
//    ],
//    'unchecked' => [
//      ':input[name="uploader"]' => [
//        'checked' => false,

//  ],
//],
//],
];

        // $depositor_person_count =1;
        // $depositor_org_count = 0;
//         for ($i=1; $i<$depositor_person_count+1; $i++) {
//             \Drupal::logger('dataset_upload')->debug('depositors person [i]: ' . $i);
//             if ($depositor_type[$i] === 'person') {
//                 $form['dataset']['depositor'][$i]['member'] = [
//     '#type' => 'details',
//     '#title' =>  $this->t('Person(s)'),
//     '#open' => true,
        //   ];
//
//
//                 $form['dataset']['depositor'][$i]['member']['firstname'] = [
        // '#type' => 'textfield',
        //   '#title' => $this
//     ->t('First name'),
//     '#default_value' => explode(' ', $depositor_name[$i])[0],
//     '#required' => true
//     //'#default_value' => $user->get('field_first_name'),
//     //'#disabled' => true,
        // ];
//
//                 $form['dataset']['depositor'][$i]['member']['lastname'] = [
        //   '#type' => 'textfield',
//     '#title' => $this
//       ->t('Last name'),
//       '#default_value' => array_slice(explode(' ', $depositor_name[$i]), 1),
//       '#required' => true
//     //  '#default_value' => $user->get('field_last_name'),
//       //'#disabled' => true,
        //   ];
//                 $form['dataset']['depositor'][$i]['member']['email'] = [
//     '#type' => 'email',
//       '#title' => $this
//         ->t('Email'),
//       '#default_value' => $depositor_email[$i],
//       '#required' => true
//       //'#disabled' => true,
//     ];
//                 $form['dataset']['depositor'][$i]['member']['federatedid'] = [
//         '#type' => 'textfield',
//           '#title' => $this
//             ->t('Federated ID'),
//             '#default_value' => '',
//             '#required' => true
//             //'#disabled' => true,
//         ];
//                 $uploader = false;
//                 $type = 'hidden';
//                 if ($i == 0) {
//                     $uploader = true;
//                     $type = 'checkbox';
//                 }
//                 $form['dataset']['depositor'][$i]['uploader'] = [
//           '#type' => $type,
//           '#title' =>  $this->t('Uploader'),
//           '#description' =>  $this->t('The primary depositor are considered as the uploader'),
//           '#value' => $uploader,
//           '#disabled' => true,
//         //  '#states' => [
//         //    'checked' => [
//         //      ':input[name="uploader"]' => [
//         //        'checked' => true,
//         //      ],
//         //    ],
//         //    'unchecked' => [
//         //      ':input[name="uploader"]' => [
//         //        'checked' => false,
//
//         //  ],
//         //],
//         //],
//         ];
//             }
//         }
//         $depositors = $depositor_person_count + $depositor_org_count;
//         for ($i=$depositor_person_count; $i<$depositors; $i++) {
//             \Drupal::logger('dataset_upload')->debug('depositors org [i]: ' . $i);
//             if ($depositor_type[$i] === 'organization') {
        // //    $holder_id = $i;
//
//                 $form['dataset']['depositor'][$i]['member'] = [
//       '#type' => 'details',
//       '#title' =>  $this->t('Organization(s)'),
//       '#open' => true,
//     ];
//
//                 $shortname = '';
//                 $longname = '';
//                 $words = str_word_count($depositor_name[$i]);
//                 if ($words > 1) {
//                     $longname = $depositor_name[$i];
//                 } else {
//                     $shortname = $depositor_name[$i];
//                 }
//                 $form['dataset']['depositor'][$i]['member']['longname'] = [
        //   '#type' => 'textfield',
//     '#title' => $this
//       ->t('Long name'),
//       '#default_value' => $depositor_name[$i], //Extract from metadata
//       '#required' => true
//       //'#disabled' => true,
        //   ];
//
//                 $form['dataset']['depositor'][$i]['member']['shortname'] = [
//     '#type' => 'textfield',
//       '#title' => $this
//         ->t('Short name'),
//         '#default_value' => $depositor_name[$i], //Extract from metadata
//         //'#disabled' => true,
//         '#required' => true
//     ];
//                 $form['dataset']['depositor'][$i]['member']['contactemail'] = [
//       '#type' => 'email',
//         '#title' => $this
//           ->t('Contact email'),
//           '#default_value' => $depositor_email[$i], //Extract from metadata
//           '#required' => true
//           //'#disabled' => true,
//       ];
//                 $form['dataset']['depositor'][$i]['member']['homepage'] = [
//           '#type' => 'url',
//             '#title' => $this
//               ->t('Homepage'),
//               '#default_value' => $depositor_url[$i], //Extract from metadata
//             //  '#disabled' => true,
//             '#required' => true
//           ];
//                 if ($i == 0) {
//                     $uploader = true;
//                 } else {
//                     $uploader = false;
//                 }
//                 $form['dataset']['depositor'][$i]['uploader'] = [
//             '#type' => 'hidden',
//             '#title' =>  $this->t('Uploader'),
//             '#value' => $uploader,
//             ];
//             }
//         }
//         $form['dataset']['depositor']['actions'] = [
//       '#type' => 'actions'
        //   ];
//         $form['dataset']['depositor']['actions']['add_person'] = [
//       '#type' => 'submit',
//       '#submit' => ['::addDepositorPerson'],
//       '#value' => $this->t('Add person'),
//       '#ajax' => [
//            'callback' => '::depositorCallback',
//            'wrapper' => 'depositor-wrapper',
//          ],
        //   ];
//         $form['dataset']['depositor']['actions']['add_org'] = [
//       '#type' => 'submit',
//       '#submit' => ['::addDepositorOrg'],
//       '#value' => $this->t('Add organization'),
//       '#ajax' => [
//            'callback' => '::depositorCallback',
//            'wrapper' => 'depositor-wrapper',
//          ],
        //   ];
//
//         $form['dataset']['depositor']['actions']['remove_person'] = [
//       '#type' => 'submit',
//       '#submit' => ['::removeDepositorPerson'],
//       '#value' => $this->t('Remove person'),
//       '#ajax' => [
//            'callback' => '::depositorCallback',
//            'wrapper' => 'depositor-wrapper',
//          ],
        //   ];
//
//         $form['dataset']['depositor']['actions']['remove_org'] = [
//       '#type' => 'submit',
//       '#submit' => ['::removeDepositorOrg'],
//       '#value' => $this->t('Remove organization'),
//       '#ajax' => [
//            'callback' => '::depositorCallback',
//            'wrapper' => 'depositor-wrapper',
//          ],
        //   ];

        //           dpm('manager persons: ' .$num_manager_person);
        //         dpm('manager orgs: ' .$num_manager_org);


        $form['dataset']['data_manager'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Data manager'),
            '#description' => $this->t('The person or institution that are responsible for fielding questions on the maintenance and use of the data. There can be more than one data manager'),
            //'#tree' => true,
            '#prefix' => '<div id="manager-wrapper">',
            '#suffix' => '</div>',
      ];
        if (null != $depositor_role) {
            $max = count($depositor_role);
        } else {
            $max = 1;
        }
        for ($i=0; $i< $max ; $i++) {
            //$managers = 2;
            if (strtolower($depositor_role[$i]) === strtolower('Data Manager') && $depositor_type[$i] === 'person') {
                //for($i=0; $i < $managers; $i++ )
                $form['dataset']['data_manager'][$i]['manager'] = [
        '#type' => 'fieldgroup',
        '#title' => $this->t('Data manager person'),
        //'#open' => true,
      //'#type' => 'container',
      //'#title' => $this->t('Data manager'),
      //'#description' => $this->t('The person or organization that are responsible for fielding questions on the maintenance and use of the data. There can be more than one data manager'),
      //'#tree' => true,
      //'#prefix' => '<div id="manager-wrapper">',
      //'#suffix' => '</div>',

    ];


                $form['dataset']['data_manager'][$i]['manager']['firstname'] = [
  '#type' => 'textfield',
    '#title' => $this
      ->t('First name'),
        '#default_value' => trim(explode(' ', $depositor_name[$i])[0]),
      '#required' => true
      //'#default_value' => $user->get('field_first_name'),
      //'#disabled' => true,
  ];

                $form['dataset']['data_manager'][$i]['manager']['lastname'] = [
    '#type' => 'textfield',
      '#title' => $this
        ->t('Last name'),
        '#default_value' =>  array_slice(explode(' ', $depositor_name[$i]), 1),
        '#required' => true
      //  '#default_value' => $user->get('field_last_name'),
        //'#disabled' => true,
    ];
                $form['dataset']['data_manager'][$i]['manager']['email'] = [
      '#type' => 'email',
        '#title' => $this
          ->t('Email'),
        '#default_value' => trim($depositor_email[$i]),
        '#required' => true
        //'#disabled' => true,
      ];
                $form['dataset']['data_manager'][$i]['manager']['federatedid'] = [
          '#type' => 'hidden',
            '#title' => $this
              ->t('Federated ID'),
              '#default_value' => '',
              //'#required' => true
              //'#disabled' => true,
          ];
            } elseif (strtolower($depositor_role[$i]) === strtolower('Data Manager') && ($depositor_type[$i] === 'institution' || $depositor_type[$i] === 'organization')) {
                if (isset($depositor_name[$i])) {
                    $exp = '/([\w\s]+)/';
                    preg_match_all($exp, $depositor_name[$i], $matches);
                    $longname = trim($matches[0][0]);
                    $shortname = isset($matches[0][1]) ? $matches[0][1] : $matches[1][0];
                //\Drupal::logger('dataset_upload_match')->debug('<pre><code>' . print_r($matches, true) . '</code></pre>');
                } else {
                    $shortname = '';
                    $longname = '';
                }

                $form['dataset']['data_manager'][$i]['manager']['longname'] = [
        '#type' => 'textfield',
          '#title' => $this
            ->t('Long name'),
            '#default_value' => trim($longname),
            //'#disabled' => true,
            '#required' => true
        ];

                $form['dataset']['data_manager'][$i]['manager']['shortname'] = [
          '#type' => 'textfield',
            '#title' => $this
              ->t('Short name'),
              '#default_value' => trim($shortname),
              //'#disabled' => true,
              '#required' => true
          ];
                $form['dataset']['data_manager'][$i]['manager']['contactemail'] = [
            '#type' => 'email',
              '#title' => $this
                ->t('Contact email'),
                '#default_value' => trim($depositor_email[$i]),
                //'#disabled' => true,
                '#required' => true
            ];
                $form['dataset']['data_manager'][$i]['manager']['homepage'] = [
                '#type' => 'url',
                  '#title' => $this
                    ->t('Homepage'),
                    '#default_value' => trim($depositor_url[$i]),
                  //  '#disabled' => true,
                  '#required' => true
                ];
            } else {
                $form['dataset']['data_manager'][$i]['manager'] = [
              '#type' => 'fieldgroup',
              '#title' => $this->t('Data manager person'),
              //'#open' => true,
            //'#type' => 'container',
            //'#title' => $this->t('Data manager'),
            //'#description' => $this->t('The person or organization that are responsible for fielding questions on the maintenance and use of the data. There can be more than one data manager'),
            //'#tree' => true,
            //'#prefix' => '<div id="manager-wrapper">',
            //'#suffix' => '</div>',

          ];


                $form['dataset']['data_manager'][$i]['manager']['firstname'] = [
        '#type' => 'textfield',
          '#title' => $this
            ->t('First name'),
              '#default_value' => '',
            '#required' => true
            //'#default_value' => $user->get('field_first_name'),
            //'#disabled' => true,
        ];

                $form['dataset']['data_manager'][$i]['manager']['lastname'] = [
          '#type' => 'textfield',
            '#title' => $this
              ->t('Last name'),
              '#default_value' =>  '',
              '#required' => true
            //  '#default_value' => $user->get('field_last_name'),
              //'#disabled' => true,
          ];
                $form['dataset']['data_manager'][$i]['manager']['email'] = [
            '#type' => 'email',
              '#title' => $this
                ->t('Email'),
              '#default_value' => '',
              '#required' => true
              //'#disabled' => true,
            ];
                $form['dataset']['data_manager'][$i]['manager']['federatedid'] = [
                '#type' => 'hidden',
                  '#title' => $this
                    ->t('Federated ID'),
                    '#default_value' => '',
                    //'#required' => true
                    //'#disabled' => true,
                ];
                break;
            }
        }


        /**
         * TODO: add data manager organization form datamanger role are org
         */
        /*
                $manager_org_idx = array_search('organization', $depositor_type);
                \Drupal::logger('dataset_upload')->debug('manager org idx: ' . $manager_org_idx);
                $shortname = '';
                $longname = '';
                $words = str_word_count($depositor_name[$manager_org_idx]);
                if ($words > 1) {
                    $longname = $depositor_name[$manager_org_idx];
                } else {
                    $shortname = $depositor_name[$manager_org_idx];
                }
                $form['dataset']['data_manager'][1]['manager'] = [
                    '#type' => 'details',
                    '#title' => $this->t('Data manager organization'),
                    '#open' => true,
                    //'#description' => $this->t('The person or organization that are responsible for fielding questions on the maintenance and use of the data. There can be more than one data manager'),
                    //'#tree' => true,
                    //'#prefix' => '<div id="manager-wrapper">',
                    //'#suffix' => '</div>',

                ];
                $form['dataset']['data_manager'][1]['manager']['longname'] = [
          '#type' => 'textfield',
            '#title' => $this
              ->t('Long name'),
              '#default_value' => $longname,
              //'#disabled' => true,
              '#required' => true
          ];

                $form['dataset']['data_manager'][1]['manager']['shortname'] = [
            '#type' => 'textfield',
              '#title' => $this
                ->t('Short name'),
                '#default_value' => $shortname,
                //'#disabled' => true,
                '#required' => true
            ];
                $form['dataset']['data_manager'][1]['manager']['contactemail'] = [
              '#type' => 'email',
                '#title' => $this
                  ->t('Contact email'),
                  '#default_value' => $depositor_email[$manager_org_idx],
                  //'#disabled' => true,
                  '#required' => true
              ];
                $form['dataset']['data_manager'][1]['manager']['homepage'] = [
                  '#type' => 'url',
                    '#title' => $this
                      ->t('Homepage'),
                      '#default_value' => $depositor_url[$manager_org_idx],
                    //  '#disabled' => true,
                    '#required' => true
                  ];
        */

        /*
                      $form['dataset']['data_manager']['actions'] = [
                          '#type' => 'actions'
                      ];
                      $form['dataset']['data_manager']['actions']['add_person'] = [
                          '#type' => 'submit',
                          '#submit' => ['::addManagerPerson'],
                          '#value' => $this->t('Add person'),
                          '#ajax' => [
                               'callback' => '::addManagerCallback',
                               'wrapper' => 'manager-wrapper',
                             ],
                      ];
                      $form['dataset']['data_manager']['actions']['add_org'] = [
                          '#type' => 'submit',
                          '#submit' => ['::addManagerOrg'],
                          '#value' => $this->t('Add organization'),
                          '#ajax' => [
                               'callback' => '::addManagerCallback',
                               'wrapper' => 'manager-wrapper',
                             ],
                      ];
        */
        /**
         * rights holder
         */

        $form['dataset']['rights_holder'] = [
              '#type' => 'fieldset',
              '#title' => $this->t('Rights holder'),
              '#description' => $this->t('The rights holder  are the institution of the prinsipal investigator.'),
              //'#tree' => true,
            ];
        $form['dataset']['rights_holder']['holder'] = [
              '#type' => 'container'
            ];
        /*


                          $form['dataset']['rights_holder']['holder']['organization'] = [
                            '#type' => 'details',
                            '#title' => 'Organization',
                            '#description' => 'The rights holder institution',
                            '#open' => true,
                          ];

        /*
                    $form['dataset']['rights_holder']['holder']['organization']['longname'] = [
                  '#type' => 'textfield',
                    '#title' => $this
                      ->t('Long name'),
                      '#default_value' => '', //Extract from metadata
                      '#required' => true
                      //'#disabled' => true,
                  ];

                    $form['dataset']['rights_holder']['holder']['organization']['shortname'] = [
                    '#type' => 'textfield',
                      '#title' => $this
                        ->t('Short name'),
                        '#default_value' => '', //Extract from metadata
                        //'#disabled' => true,
                        '#required' => true
                    ];
                    $form['dataset']['rights_holder']['holder']['organization']['contactemail'] = [
                      '#type' => 'email',
                        '#title' => $this
                          ->t('Contact email'),
                          '#default_value' => '', //Extract from metadata
                          '#required' => true
                          //'#disabled' => true,
                      ];
                    $form['dataset']['rights_holder']['holder']['organization']['homepage'] = [
                          '#type' => 'url',
                            '#title' => $this
                              ->t('Homepage'),
                              '#default_value' => '', //Extract from metadata
                            //  '#disabled' => true,
                            '#required' => true
                          ];
        */
        //isset($_GET['user']) ? $_GET['user'] : 'nobody'
        $creator_types = $form_state->get('creator_type');
        $creator_roles = $form_state->get('creator_role');
        $creator_names = $form_state->get('creator_name');
        $creator_email = $form_state->get('creator_email');
        $creator_url = $form_state->get('creator_url');
        $creator_institution = $form_state->get('creator_institution');
        // //dpm($creator_email);
        // $creators = count($creator_types);
        // //\Drupal::logger('dataset_upload')->debug('Num of creators: ' . $creators);
        // for ($i=0; $i<$creators; $i++) {
        //     if ($creator_types[$i] === 'organization') {
        //         $holder_id = $i;
        //     }
        // }
        // for ($i=0; $i<$creators; $i++) {
        //     if ($creator_roles[$i] === 'Investigator' || $creator_roles[$i] === 'Principal investigator') {
        //         $holder_person_id = $i;
        //         break;
        //     }
        // }
//         $form['dataset']['rights_holder']['person'] = [
        //   '#type' => 'details',
        //   '#title' => 'Person',
        //   '#description' => 'The contact person of the rights holder institution',
        //   '#open' => true,
        // ];
//
//         $form['dataset']['rights_holder']['person']['firstname'] = [
        // '#type' => 'textfield',
        // '#title' => $this
        //   ->t('First name'),
        //   '#default_value' => explode(' ', $creator_names[$holder_person_id])[0],
        //   '#required' => true
        //   //'#default_value' => $user->get('field_first_name'),
        //   //'#disabled' => true,
        // ];
//
//         $form['dataset']['rights_holder']['person']['lastname'] = [
        // '#type' => 'textfield',
        //   '#title' => $this
//     ->t('Last name'),
//     '#default_value' => array_slice(explode(' ', $creator_names[$holder_person_id]), 1),
//     '#required' => true
        //   //  '#default_value' => $user->get('field_last_name'),
//     //'#disabled' => true,
        // ];
//         $form['dataset']['rights_holder']['person']['email'] = [
        //   '#type' => 'email',
//     '#title' => $this
//       ->t('Email'),
//     '#default_value' => $creator_email[$holder_person_id],
//     '#required' => true
//     //'#disabled' => true,
        //   ];
//         $form['dataset']['rights_holder']['person']['federatedid'] = [
//       '#type' => 'textfield',
//         '#title' => $this
//           ->t('Federated ID'),
//           '#default_value' => '',
//           '#required' => true
//           //'#disabled' => true,
//       ];
        if (isset($metadata['institution'])) {
            $exp = '/([\w\s]+)/';
            preg_match_all($exp, $metadata['institution'], $matches);
            $longname = trim($matches[0][0]);
            $shortname = isset($matches[0][1]) ? $matches[0][1] : $matches[1][0];
        //\Drupal::logger('dataset_upload_match')->debug('<pre><code>' . print_r($matches, true) . '</code></pre>');
        } else {
            $shortname = '';
            $longname = '';
        }

        $form['dataset']['rights_holder']['holder']['longname'] = [
'#type' => 'textfield',
'#title' => $this
  ->t('Long name'),
  '#default_value' => rtrim($longname), //Extract from metadata
  '#required' => true
  //'#disabled' => true,
];

        $form['dataset']['rights_holder']['holder']['shortname'] = [
'#type' => 'textfield',
  '#title' => $this
    ->t('Short name'),
    '#default_value' => trim($shortname), //Extract from metadata
    //'#disabled' => true,
    '#required' => true
];
        $form['dataset']['rights_holder']['holder']['contactemail'] = [
  '#type' => 'email',
    '#title' => $this
      ->t('Contact email'),
      '#default_value' => '', //Extract from metadata
      '#required' => true
      //'#disabled' => true,
  ];
        $form['dataset']['rights_holder']['holder']['homepage'] = [
      '#type' => 'url',
        '#title' => $this
          ->t('Homepage'),
          '#default_value' => '', //Extract from metadata
        //  '#disabled' => true,
        '#required' => true
      ];

        /**
         * creator
         */

        $form['dataset']['creator'] = [
    '#type' => 'fieldset',
    '#title' => $this->t('Creator(s)'),
    '#description' => $this->t('The person or institution that created the dataset'),
    //'#tree' => true,
];

        $creators = count($creator_types);
        $form_state->set('creator_count', $creators);
        //\Drupal::logger('dataset_upload')->debug('Num of creators: ' . $creators);
        for ($i=0; $i<$creators; $i++) {
            if ($creator_types[$i] === 'person') {
                $form['dataset']['creator'][$i]['creator'] = [
        '#type' => 'markup',
        '#markup' => '<strong>Person</strong>',
        '#attributes' => ['class' => ['w3-card-2']],
      ];
                $form['dataset']['creator'][$i]['creator']['firstname'] = [
'#type' => 'textfield',
  '#title' => $this
    ->t('First name'),
    '#default_value' => explode(' ', $creator_names[$i])[0],
    '#required' => true
    //'#disabled' => true,
];

                $form['dataset']['creator'][$i]['creator']['lastname'] = [
  '#type' => 'textfield',
    '#title' => $this
      ->t('Last name'),
      '#default_value' => array_slice(explode(' ', $creator_names[$i]), 1),
      '#required' => true
      //'#disabled' => true,
      //'#default_value' => $this->currentUser->getDisplayName(),
  ];
                $form['dataset']['creator'][$i]['creator']['email'] = [
    '#type' => 'email',
      '#title' => $this
        ->t('Email'),
        '#default_value' => $creator_email[$i],
        '#required' => true
        //'#disabled' => true,
      //'#default_value' => $this->currentUser->getEmail(),
    ];
            }
            if (in_array($creator_types[$i], ['organization','group','institution','position'])) {
                $shortname = '';
                $longname = '';
                $exp = '/([\w\s]+)/';
                preg_match_all($exp, $creator_names[$i], $matches);
                $longname = $matches[0][0];
                $shortname = isset($matches[0][1]) ? $matches[0][1] : $matches[1][0];

                $form['dataset']['creator'][$i]['creator'] = [
        '#type' => 'markup',
        '#markup' => '<strong>Institution</strong>',
      ];

                $form['dataset']['creator'][$i]['creator']['longname'] = [
'#type' => 'textfield',
  '#title' => $this
    ->t('Long name'),
    '#default_value' => $longname, //Extract from metadata
    '#required' => true
    //'#disabled' => true,
];

                $form['dataset']['creator'][$i]['creator']['shortname'] = [
  '#type' => 'textfield',
    '#title' => $this
      ->t('Short name'),
      '#default_value' => $shortname, //Extract from metadata
      //'#disabled' => true,
      '#required' => true
  ];
                $form['dataset']['creator'][$i]['creator']['contactemail'] = [
    '#type' => 'email',
      '#title' => $this
        ->t('Contact email'),
        '#default_value' => $creator_email[$i], //Extract from metadata
        '#required' => true
        //'#disabled' => true,
    ];
                $form['dataset']['creator'][$i]['creator']['homepage'] = [
        '#type' => 'url',
          '#title' => $this
            ->t('Homepage'),
            '#default_value' => $creator_url[$i], //Extract from metadata
          //  '#disabled' => true,
          '#required' => true
        ];
            }
        }

        /**
        * subject
        */
        $subjects = $form_state->get('api_subjects');
        //dpm($subjects);
        $domains = array_unique(array_column($subjects, 'domain'));
        //$fields = array_search('Humanities' )




        $form['dataset']['subject'] = [
  '#type' => 'fieldset',
  '#title' =>  $this->t("Subject(s)"),
  '#prefix' => '<div id="subject-wrapper">',
  '#suffix' => '</div>',
  '#description' =>  $this->t("Add at least one subject consisting of domain, field, and subfield, using the select dropdown and press add subject button"),
  '#required' => true
];

        $domain = $form_state->getValue(array('dataset','subject','domain'));
        $field = $form_state->getValue(array('dataset','subject','field'));
        $subfield = $form_state->getValue(array('dataset','subject','subfield'));

        // Gather the number of subjects in the form already.
        $num_subjects = $form_state->get('num_subjects');
        // We have to ensure that there is at least one subject field.
        if ($num_subjects === null) {
            $form_state->set('num_subjects', 0);
            $num_subjects = 0;
        }
        $domain_required = true;
        if ($num_subjects >= 1) {
            $domain_required = false;
        }

        $subjects_added = $form_state->get('subjects_added');
        if ($subjects_added !== null) {
            $form['dataset']['subject']['subjects'] = [
      '#type' => 'container',
  ];
            for ($i = 0; $i < $num_subjects; $i++) {
                $num = (string) $i+1;
                $form['dataset']['subject']['subjects'][$i] = [
      '#type' => 'item',
      '#title' =>  $this->t('Subject #' . $num),
      '#markup' => $subjects_added[$i]['domain'] . ' > ' . $subjects_added[$i]['field'] . ' > ' . $subjects_added[$i]['subfield'],
  ];
            }
        }

        $form['dataset']['subject']['domain'] = [
  '#type' => 'select',
  '#title' => $this->t("Domain"),
  '#empty_option' => $this->t('- Select domain -'),
  '#default_option' => $domain,
  '#options' => array_combine($domains, $domains),
  '#required' => $domain_required,
  '#ajax' => [
    'wrapper' => 'subject-wrapper',
    'callback' => '::subjectCallback',
    'event' => 'change'
  ],
  ];

        if (!empty($domain)) {
            $fields = array_unique(array_column($this->nirdApiClient->getField($domain), 'field'));

            $form['dataset']['subject']['field'] = [
'#type' => 'select',
'#title' => $this->t("Field"),
'#empty_option' => $this->t('- Select field -'),
'#default_option' => $field,
'#options' => array_combine($fields, $fields),
'#required' => true,
'#ajax' => [
  'wrapper' => 'subject-wrapper',
  'callback' => '::subjectCallback',
  'event' => 'change'
],
];
        }

        if (!empty($field) && !empty($domain)) {
            $subfields = array_unique(array_column($this->nirdApiClient->getSubField($domain, $field), 'subfield'));

            $form['dataset']['subject']['subfield'] = [
'#type' => 'select',
'#title' => $this->t("Subfield"),
'#empty_option' => $this->t('- Select subfield -'),
'#options' => array_combine($subfields, $subfields),
'#required' => true,
'#ajax' => [
  'wrapper' => 'subject-wrapper',
  'callback' => '::subjectCallback',
  'event' => 'change'
],
];
        }
        if (!empty($field) && !empty($domain) && !empty($subfield)) {
            $form['dataset']['subject']['actions'] = [
   '#type' => 'actions',
  ];

            $form['dataset']['subject']['actions']['addsubject'] = array(
  '#type' => 'submit',
  '#value' => $this->t('Add subject'),
  '#required' => $domain_required,
  '#submit' => ['::addSubject'],
      '#ajax' => [
        'callback' => '::addSubjectCallback',
        'wrapper' => 'subject-wrapper',
      ],
  '#limit_validation_errors' => [
    ['dataset','subject','domain'],
    ['dataset','subject','field'],
    ['dataset','subject','subfield'],
    ],
    //  '#limit_validation['dataset','subject','domain'],_errors' => array(),
  );
        }

        //}
        //dpm($this->currentUser);
        /**
        * submit actions
        */
        $form['actions'] = [
   '#type' => 'actions',
  ];

        $form['actions']['submit'] = array(
  '#type' => 'submit',
  '#button_type' => 'primary',
  '#value' => $this->t('Confirm and continue.'),
  '#validate' => ['::validateNIRD'],
  //'#submit' => ['::confirmNIRD'],
  );

        $form['actions']['cancel'] = array(
  '#type' => 'submit',
  '#value' =>  $this->t('Cancel submission'),
  '#submit' => ['::cancelSubmission'],
  '#limit_validation_errors' => [],
  );

        return $form;
    }

    /**
    * Override the validate function from parent
    *
    * {@inheritdoc}
    */
    public function validate(array &$form, FormStateInterface $form_state)
    {

      //Get the current session
        $session = \Drupal::request()->getSession();
        //Call the validation function from the parent DatasetValidationForm
    $form_state->set('keep_file', 1); //do not delete uploaded dataset after compliance checker validation
    $form_state->set('tests', ['cf:1.6' => 1, 'acdd' => 1]); //Override the tests to be used in compliance checker
      //\Drupal::logger('dataset_upload')->debug('calling parent validate');
        parent::validate($form, $form_state);
        //\Drupal::logger('dataset_upload')->debug('finished parent validate');
        //\Drupal::logger('dataset_upload')->debug($form_state->get('int_status'));
        //If dataset validation fails, redirect to form page 1.
        if ($form_state->get('int_status') > 0) {
            $form_state->set('page', 1);
        }
        //If success, redirect to form page 2
        else {
            $form_state->set('page', 2);
            //\Drupal::logger('dataset_upload')->debug('call NIRD API prefill controlled vocabulary');


            //Prefetch options from NIRD controlled vocabularies
            $form_state->set('api_state', $this->nirdApiClient->getState());

            $form_state->set('api_state', $this->nirdApiClient->getState());
            //dpm('get category');
            $form_state->set('api_category', $this->nirdApiClient->getCategory());
            //dpm('get licences');
            $form_state->set('api_licences', $this->nirdApiClient->getLicence());
            //$form_state->set('api_licences', []);
            //dpm('get subject');

            $form_state->set('api_subjects', $this->nirdApiClient->getSubject());

            //Extract Metadata from datasets
            //\Drupal::logger('dataset_upload')->debug('extracting metadata');
            $metadata = self::extractMetadata($form, $form_state);
            //\Drupal::logger('dataset_upload')->debug('meta <pre><code>' . print_r($metadata, true) . '</code></pre>');
            $validation_message = [];
            if (empty($metadata)) {
                $form_state->set('page', 1);
                array_push($validation_message, 'Something went wrong extracting metadata.');
                $form_state->set('validation_message', $validation_message);
            }
            /**
             * Extract names, roles, type, emails into arrays
             * and store them in the $form_state
             * TODO: Make a arraystructure instad of lots of arrays
              * Validate roles and types.
             */

            //CONTRIBUTORS (DEPOSITORS)
            if (isset($metadata['contributor_role'])) {
                $contributor_role = explode(', ', $metadata['contributor_role']);
                $form_state->set('contributor_role', $contributor_role);
            }
            if (isset($metadata['contributor_type'])) {
                $contributor_type = explode(', ', $metadata['contributor_type']);
                $form_state->set('contributor_type', $contributor_type);
                $contributor_type_count = array_count_values($contributor_type);

                //\Drupal::logger('dataset_upload')->debug('contributor role <pre><code>' . print_r($contributor_role, true) . '</code></pre>');
            }
            if (isset($contributor_type_count['person'])) {
                $contributor_person_count = (int) $contributor_type_count['person'];
                $form_state->set('contributor_person_count', $contributor_person_count);
            } else {
                $form_state->set('contributor_person_count', 1);
            }
            if (isset($contributor_type_count['institution'])) {
                $contributor_org_count = (int) $contributor_type_count['institution'];
                $form_state->set('contributor_org_count', $contributor_org_count);
            } else {
                $form_state->set('contributor_org_count', 0);
            }


            if (isset($metadata['contributor_name'])) {
                $contributor_name = explode(', ', $metadata['contributor_name']);
                $form_state->set('contributor_name', $contributor_name);
            }

            if (isset($metadata['contributor_email'])) {
                $contributor_email = explode(', ', $metadata['contributor_email']);
                $form_state->set('contributor_email', $contributor_email);
            }
            if (isset($metadata['contributor_url'])) {
                $contributor_url = explode(', ', $metadata['contributor_url']);
                $form_state->set('contributor_url', $contributor_url);
            }

            //CREATOR
            if (isset($metadata['creator_role'])) {
                $creator_role = explode(', ', $metadata['creator_role']);
                $form_state->set('creator_role', $creator_role);
            }
            if (isset($metadata['creator_type'])) {
                $creator_type = explode(', ', $metadata['creator_type']);
                $form_state->set('creator_type', $creator_type);
                $creator_type_count = array_count_values($creator_type);
                //\Drupal::logger('dataset_upload')->debug('depositor count <pre><code>' . print_r($depositor_type_count, true) . '</code></pre>');
                $creator_person_count = (int) $creator_type_count['person'];
                $creator_org_count = (int) $creator_type_count['institution'];
                $form_state->set('creator_person_count', $creator_person_count);
                $form_state->set('creator_org_count', $creator_org_count);
            }
            if (isset($metadata['creator_name'])) {
                $creator_name = explode(', ', $metadata['creator_name']);
                $form_state->set('creator_name', $creator_name);
            }

            if (isset($metadata['creator_email'])) {
                $creator_email = explode(', ', $metadata['creator_email']);
                $form_state->set('creator_email', $creator_email);
            }
            if (isset($metadata['creator_url'])) {
                $creator_url = explode(',', $metadata['creator_url']);
                $form_state->set('creator_url', $creator_url);
            }
            if (isset($metadata['creator_institution'])) {
                $creator_institution = explode(', ', $metadata['creator_institution']);
                $form_state->set('creator_institution', $creator_institution);
            }

            $validation_message = [];
            if (is_null($contributor_role) || (!in_array('Data Manager', $contributor_role))) {
                $form_state->set('page', 1);
                array_push($validation_message, 'Contributor with role "Data Manager" missing. Pleace update your ACDD attributes in your uploaded netCDF files');
                $form_state->set('validation_message', $validation_message);
            }

            if (is_null($contributor_type_count) || $contributor_type_count === 0) {
                $form_state->set('page', 1);
                array_push($validation_message, 'Missing contributors. Contributor with role "Data Manager" missing. Pleace update your ACDD attributes in your uploaded netCDF files');
                $form_state->set('validation_message', $validation_message);
            }
            if (is_null($creator_type_count) || $creator_type_count === 0) {
                $form_state->set('page', 1);
                array_push($validation_message, 'Missing contributors. Contributor with role "Data Manager" missing. Pleace update your ACDD attributes in your uploaded netCDF files');
                $form_state->set('validation_message', $validation_message);
            }
            $form_state->set('validation_message', $validation_message);


            //rightS HOLDER
            //if(isset($metadata['institution'])) {
            //  $exp = '/([\w\s]+)/'
            //  preg_match_all($exp, $metadata['institution'], $mateches);
            //  $form_state->set('rights_')
            //}


            //dpm($metadata);
            $form_state->set('metadata', $metadata);
            //Set upload status flag
            $session->set('dataset_upload_status', 'validated');

            //Run aggregation checker if we have multiple netCDF files in archive
            if (($form_state->has('archived_files') && $form_state->has('aggregate')) && true == $form_state->get('aggregate')) {
                $form_state->set('page', 3);
            }
        }
        $form_state->setRebuild();
    }

    /**
    * Override the validateCallback function from parent
    *
    * {@inheritdoc}
    */
    public function validateCallback(array &$form, FormStateInterface $form_state)
    {
        $message = $form_state->get('validation_message');
        //dpm($message);
        //$form['container']['creation']['file']['#file'] = FALSE;
        //$form['container']['creation']['file']['filename'] = [];
        //$form['container']['creation']['file']['#value']['fid'] = 0;
        //$form['message']['result'] = [];
        if ($form_state->get('int_status') > 0) {
            $form['container']['message'] = $message;
        }

        return $form;
    }


    /**
     * Function for extracting metadata using the metsis_lib.nc_to_mmd service
     *
     * TODO: Fail and return message when required acdd elements are missing or wrong.
     */
    private function extractMetadata(array &$form, FormStateInterface $form_state)
    {
        $metadata = [];
        //dpm($form_state);
        $output_path = \Drupal::service('file_system')->realpath($form_state->get('upload_location')) . '/';
        $file_path = $form_state->get('file_path');
        $filename = $form_state->get('filename');

        //Process single file:
        if (!$form_state->has('archived_files')) {
            //$md = $this->ncToMmd->getMetadata($file_path, $filename, $output_path);
            \Drupal::logger('dataset_upload')->debug('extracting metadata from archive using ncdump....');
            $md = $this->attributeExtractor->extractAttributes($file_path, '');

            //Give back the metadata in a better structure for filling out the form.
      $metadata = $md; //[0];
      //for($i = 0; $i < count($arr); $i++) {
      //  $metadata[(string) ltrim($arr[$i][0])] = $arr[$i][1];
      //}
        //\Drupal::logger('dataset_upload_metadata')->debug('<pre><code>' . print_r($md, true) . '</code></pre>');
            //\Drupal::logger('dataset_upload')->debug(implode(' ', $metadata[$filename]));
      //dpm(array_keys($metadata));
      //dpm($metadata);

            //$metadata = $md;
        }

        //Process archived files
        if ($form_state->has('archived_files')) {
            $archived_files = $form_state->get('archived_files');
            //Loop over the files
            $md = [];
            foreach ($archived_files as $f) {
                $uri = $output_path .'/' .$f;
                $filepath = \Drupal::service('file_system')->realpath($uri);
                $md = $this->attributeExtractor->extractAttributes($filepath, '');

                //$md = $this->ncToMmd->getMetadata($filepath, $f, $output_path);
          /*      $arr = $md[0];
                for ($i = 0; $i < count($arr); $i++) {
                    $metadata[(string) trim($f)] = [
          (string) ltrim($arr[$i][0]) => $arr[$i][1],
        ];
                }
            } */
            }
            //dpm($md);
            /**
             * TODO: Create an unique array of the metadata from all files?
             */
            $metadata = $md;
        }

        /**
         * Return mockup metadata for now. until better metadata extraction service are developed.
         * This structure should be returned..all key values here should be mandatory
         *
         * keys should be acdd attributes
         */
        /*
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
          'depositor_name' => 'Louise Oram, Vegar Kristiansen',
          'depositor_role' => 'Technical contact, Technical contact',
          'license' => 'CCBY40',

        ];
        //dpm($metadata);
        */
        return $metadata;
    }


    /**
     * BUILD FORM PAGE 4
     * Form for registering missing institutions
     */
    public function registerMissing(array &$form, FormStateInterface $form_state)
    {
        $dataset = $form_state->getValues()['dataset'];
        $form_state->set('current_dataset', $dataset);
        $form_state->set('current_state', $form_state);
        //dpm($dataset);
        /*  $form['register'] = [
            '#type' => 'container',
            '#tree' => true,
          ];*/
        //$form['register']['#tree'] = true;
        //If data_manager institution is not registered. show form.
        if ($form_state->has('manager_inst_failed')) {
            $st = $form_state->get('manager_inst_failed');

            if ($st) {
                $form['register']['manager'] = [
               '#type' => 'fieldset',
               '#title' => $this->t('The data manager institution was not found. Please select from list or register new institution'),
               '#discription' => $this->t('Press the choose from list radio, to see if your institution is in the list and select that institution. If not in the list. Click the register radio to register a contact person for your institution.'),
               //'#prefix' => '<div id="publication-wrapper">',
               //'#suffix' => '</div>',
               '#attributes' => ['id' => 'dm-wrapper'],
               '#tree' => true,
             ];
                $i=0;
                $form['register']['manager']['dm-select'] = [
       '#type' => 'radios',
       '#title' => $this->t('Select from list or register new institution'),
       //'#empty_option' => $this->t('- Select published status -'),

       '#options' => ['selectlistdm' =>'Select from list', 'registerdm' => 'Register'],
       '#default_value' => 'selectlistdm',
       '#required' => true,
       //'#name' => 'article-select',
       '#attributes' => [],
     ];
                $query = $dataset['data_manager'][$i]['manager']['shortname'];
                $results = $this->nirdApiClient->searchOrganization($query);
                //  dpm($results);
                $inst_count = (int) $results['results'];
                $inst_arr = $results['organizations'];
                $institutions = [];
                $form_state->set('dm-insts', $results);
                for ($i=0; $i < $inst_count; $i++) {
                    $institutions[] = $inst_arr[$i]['orglongname'];
                }

                $form['register']['manager']['dmlist']  = [
       '#type' => 'select',
       '#title' =>  $this->t('Select your data manager institution from the list.'),
       '#empty_option' => $this->t('- Select institution -'),
       '#options' => $institutions,
       //'#required' => true,
       '#states'=> [
         'invisible' => [
             ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
           ],
       'visible' => [
           ':input[name="manager[dm-select]"]' => ['value' => 'selectlistdm'],
         ],
         'required' => [
             ':input[name="manager[dm-select]"]' => ['value' => 'selectlistdm'],
         ],
       ],

         ];
                $form['register']['manager']['registerdm']['longname']  = [
           '#type' => 'textfield',
           '#title' => $this
             ->t('Long name'),
             '#default_value' =>   $dataset['data_manager'][0]['manager']['longname'], //Extract from metadata
             //'#required' => true,
             //'#disabled' => true,
           '#states'=> [
             'invisible' => [
                 ':input[name="manager[dm-select]"]' => ['value' => 'selectlistdm'],
               ],
           'visible' => [
               ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
             ],
             'required' => [
                 ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
             ],
           ],

             ];

                $form['register']['manager']['registerdm']['shortname']  = [
               '#type' => 'textfield',
               '#title' => $this
                 ->t('Short name'),
                 '#default_value' =>   $dataset['data_manager'][0]['manager']['shortname'], //Extract from metadata
                 //'#required' => true,
                 //'#disabled' => true,

               '#states'=> [
                 'invisible' => [
                     ':input[name="manager[dm-select]"]' => ['value' => 'selectlistdm'],
                   ],
               'visible' => [
                   ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
                 ],
                 'required' => [
                     ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
                 ],
               ],

                 ];
                $form['register']['manager']['registerdm']['contactemail']  = [
                   '#type' => 'textfield',
                   '#title' => $this
                     ->t('Contact email'),
                     '#default_value' =>   $dataset['data_manager'][0]['manager']['contactemail'], //Extract from metadata
                     //'#required' => true,
                     //'#disabled' => true,

                   '#states'=> [
                     'invisible' => [
                         ':input[name="manager[dm-select]"]' => ['value' => 'selectlistdm'],
                       ],
                   'visible' => [
                       ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
                     ],
                     'required' => [
                         ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
                     ],
                   ],

                     ];
                $form['register']['manager']['registerdm']['homepage']  = [
                       '#type' => 'textfield',
                       '#title' => $this
                         ->t('Homepage'),
                         '#default_value' => $dataset['data_manager'][0]['manager']['homepage'], //Extract from metadata
                         //'#required' => true,
                         //'#disabled' => true,

                       '#states'=> [
                         'invisible' => [
                             ':input[name="manager[dm-select]"]' => ['value' => 'selectlistdm'],
                           ],
                       'visible' => [
                           ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
                         ],
                         'required' => [
                             ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
                         ],
                       ],

                         ];

                $form['register']['manager']['registerdm']['contact'] = [
               '#type' => 'fieldset',
               '#title' => $this
                 ->t('Enter the contact person for this institution'),

                 '#states'=> [
                   'invisible' => [
                       ':input[name="manager[dm-select]"]' => ['value' => 'selectlistdm'],
                     ],
                 'visible' => [
                     ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
                   ],
                   'required' => [
                       ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
                   ],
                 ],

                   ];
                $form['register']['manager']['registerdm']['contact']['firstname']  = [
            '#type' => 'textfield',
            '#title' => $this
              ->t('First name'),
              '#default_value' => '', //Extract from metadata
              //'#required' => true,
              //'#disabled' => true,

              '#states'=> [
                'invisible' => [
                    ':input[name="manager[dm-select]"]' => ['value' => 'selectlistdm'],
                  ],
              'visible' => [
                  ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
                ],
                'required' => [
                    ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
                ],
              ],

                ];
                $form['register']['manager']['registerdm']['contact']['lastname']  = [
             '#type' => 'textfield',
             '#title' => $this
               ->t('Last name name'),
               '#default_value' => '', //Extract from metadata
               //'#required' => true,
               //'#disabled' => true,

               '#states'=> [
                 'invisible' => [
                     ':input[name="manager[dm-select]"]' => ['value' => 'selectlistdm'],
                   ],
               'visible' => [
                   ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
                 ],
                 'required' => [
                     ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
                 ],
               ],

                 ];

                $form['register']['manager']['registerdm']['contact']['email']  = [
            '#type' => 'textfield',
            '#title' => $this
              ->t('Email'),
              '#default_value' => '', //Extract from metadata
              //'#required' => true,
              //'#disabled' => true,

              '#states'=> [
                'invisible' => [
                    ':input[name="manager[dm-select]"]' => ['value' => 'selectlistdm'],
                  ],
              'visible' => [
                  ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
                ],
                'required' => [
                    ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
                ],
              ],



              ];
                $form['register']['manager']['registerdm']['contact']['federatedid']  = [
                   '#type' => 'hidden',
                   '#title' => $this
                     ->t('Federaded ID'),
                     '#default_value' => '', //Extract from metadata
                     //'#required' => true,
                     //'#disabled' => true,

                     '#states'=> [
                       'invisible' => [
                           ':input[name="manager[dm-select]"]' => ['value' => 'selectlistdm'],
                         ],
                     'visible' => [
                         ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
                       ],
                       'required' => [
                           ':input[name="manager[dm-select]"]' => ['value' => 'registerdm'],
                       ],
                     ],

                       ];
            }
        }
        //If rights_holder institution is not registered. show form.
        if ($form_state->has('holder_inst_failed')) {
            $st = $form_state->get('holder_inst_failed');
            if ($st) {
                $form['register']['holder'] = [
               '#type' => 'fieldset',
               '#title' => $this->t('The rights holder institution was not found. Please select from list or register new institution'),
               '#description' => $this->t('Press the choose from list radio, to see if your institution is in the list and select that institution. If not in the list. Click the register radio to register a contact person for your institution.'),
               //'#prefix' => '<div id="publication-wrapper">',
               //'#suffix' => '</div>',
               '#attributes' => ['id' => 'rh-wrapper'],
               '#tree' => true,
             ];

                $form['register']['holder']['rh-select'] = [
       '#type' => 'radios',
       '#title' => $this->t('Select from list or register new institution'),
       //'#empty_option' => $this->t('- Select published status -'),

       '#options' => ['selectlistrh' =>'Select from list', 'registerrh' => 'Register'],
       '#default_value' => 'selectlistrh',
       '#required' => true,
       //'#name' => 'article-select',
       //'#attributes' => [],
     ];

                $query = $dataset['rights_holder']['holder']['shortname'];
                $results = $this->nirdApiClient->searchOrganization($query);
                //dpm($results);
                $inst_count = (int) $results['results'];
                $inst_arr = $results['organizations'];
                $institutions = [];
                for ($i=0; $i < $inst_count; $i++) {
                    $institutions[] = $inst_arr[$i]['orglongname'];
                }
                //dpm($institutions);
                $form_state->set('rh-insts', $results);
                $form['register']['holder']['rhlist']  = [
       '#type' => 'select',
       '#title' =>  $this->t('Select your rights holder institution from the list.'),
       //'#required' => true,
       '#empty_option' => $this->t('- Select institution -'),
       '#options' => $institutions,
       '#states' => [
         'invisible' => [
             //':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
           ],
       'visible' => [
           ':input[name="holder[rh-select]"]' => ['value' => 'selectlistrh'],
         ],
         'required' => [
             ':input[name="holder[rh-select]"]' => ['value' => 'selectlistrh'],
         ],
       ],

         ];
                $form['register']['holder']['registerrh']['longname']  = [
           '#type' => 'textfield',
           '#title' => $this
             ->t('Long name'),
             '#default_value' => $dataset['rights_holder']['holder']['longname'], //Extract from metadata
             //'#required' => true,
             //'#disabled' => true,

           '#states'=> [
             'invisible' => [
                 ':input[name="holder[rh-select]"]' => ['value' => 'selectlistrh'],
               ],
           'visible' => [
               ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
             ],
             'required' => [
                 ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
             ],
           ],

             ];

                $form['register']['holder']['registerrh']['shortname']  = [
               '#type' => 'textfield',
               '#title' => $this
                 ->t('Short name'),
                 '#default_value' => $dataset['rights_holder']['holder']['shortname'], //Extract from metadata
                 //'#required' => true,
                 //'#disabled' => true,

               '#states'=> [
                 'invisible' => [
                     ':input[name="holder[rh-select]"]' => ['value' => 'selectlistrh'],
                   ],
               'visible' => [
                   ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                 ],
                 'required' => [
                     ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                 ],
               ],

                 ];
                $form['register']['holder']['registerrh']['contactemail']  = [
                   '#type' => 'textfield',
                   '#title' => $this
                     ->t('Contact email'),
                     '#default_value' => $dataset['rights_holder']['holder']['contactemail'], //Extract from metadata
                     //'#required' => true,
                     //'#disabled' => true,

                   '#states'=> [
                     'invisible' => [
                         ':input[name="holder[rh-select]"]' => ['value' => 'selectlistrh'],
                       ],
                   'visible' => [
                       ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                     ],
                     'required' => [
                         ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                     ],
                   ],

                     ];
                $form['register']['holder']['registerrh']['homepage']  = [
                       '#type' => 'textfield',
                       '#title' => $this
                         ->t('Homepage'),
                         '#default_value' => $dataset['rights_holder']['holder']['homepage'], //Extract from metadata
                         //'#required' => true,
                         //'#disabled' => true,

                       '#states'=> [
                         'invisible' => [
                             ':input[name="holder[rh-select]"]' => ['value' => 'selectlistrh'],
                           ],
                       'visible' => [
                           ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                         ],
                         'required' => [
                             ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                         ],
                       ],

                     ];
                $form['register']['holder']['registerrh']['contact'] = [
                    '#type' => 'fieldset',
                    '#title' => $this
                      ->t('Enter the contact person for this institution'),

                    '#states'=> [
                      'invisible' => [
                          ':input[name="holder[rh-select]"]' => ['value' => 'selectlistrh'],
                        ],
                    'visible' => [
                        ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                      ],
                      'required' => [
                          ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                      ],
                    ],

                  ];
                $form['register']['holder']['registerrh']['contact']['firstname']  = [
                 '#type' => 'textfield',
                 '#title' => $this
                   ->t('First name'),
                   '#default_value' => '', //Extract from metadata
                   //'#required' => true,
                   //'#disabled' => true,

                 '#states'=> [
                   'invisible' => [
                       ':input[name="holder[rh-select]"]' => ['value' => 'selectlistrh'],
                     ],
                 'visible' => [
                     ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                   ],
                   'required' => [
                       ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                   ],
                 ],

                   ];

                $form['register']['holder']['registerrh']['contact']['lastname']  = [
                  '#type' => 'textfield',
                  '#title' => $this
                    ->t('Last name name'),
                    '#default_value' => '', //Extract from metadata
                    //'#required' => true,
                    //'#disabled' => true,

                  '#states'=> [
                    'invisible' => [
                        ':input[name="holder[rh-select]"]' => ['value' => 'selectlistrh'],
                      ],
                  'visible' => [
                      ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                    ],
                    'required' => [
                        ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                    ],
                  ],

                    ];

                $form['register']['holder']['registerrh']['contact']['email']  = [
                 '#type' => 'textfield',
                 '#title' => $this
                   ->t('Email'),
                   '#default_value' => '', //Extract from metadata
                   //'#required' => true,
                   //'#disabled' => true,

                 '#states'=> [
                   'invisible' => [
                       ':input[name="holder[rh-select]"]' => ['value' => 'selectlistrh'],
                     ],
                 'visible' => [
                     ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                   ],
                   'required' => [
                       ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                   ],
                 ],

                   ];
                $form['register']['holder']['registerrh']['contact']['federatedid']  = [
                        '#type' => 'hidden',
                        '#title' => $this
                          ->t('Federaded ID'),
                          '#default_value' => '', //Extract from metadata
                          //'#required' => true,
                          //'#disabled' => true,

                        '#states'=> [
                          'invisible' => [
                              ':input[name="holder[rh-select]"]' => ['value' => 'selectlistrh'],
                            ],
                        'visible' => [
                            ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                          ],
                          'required' => [
                              ':input[name="holder[rh-select]"]' => ['value' => 'registerrh'],
                          ],
                        ],

                      ];
            }
        }
        /**
        * submit actions
        */
        $form['actions'] = [
    '#type' => 'actions',
   ];

        $form['actions']['submit'] = array(
   '#type' => 'submit',
   '#button_type' => 'primary',
   '#value' => $this->t('Confirm and continue.'),
   '#validate' => ['::validateINST'],
   //'#submit' => ['::confirmNIRD'],
   );

        $form['actions']['cancel'] = array(
   '#type' => 'submit',
   '#value' =>  $this->t('Cancel submission'),
   '#submit' => ['::cancelSubmission'],
   '#limit_validation_errors' => [],
   );

        //dpm($form);
        //dpm($form_state->getValues());
        return $form;
    }

    //Validate and register the entered institutions
    public function validateINST(array &$form, FormStateInterface  $form_state)
    {
        //Get all form values.
        $dataset = $form_state->getValues();
        //dpm($dataset);

        //Save the input values of this form for later.
        $form_state->set('inst_reg', $dataset);
        //Proecess Data Manager Institution
        if (array_key_exists('manager', $dataset)) {
            $select  = $dataset['manager']['dm-select'];
            if ($select === 'selectlistdm') {
                $form_state->set('selected_dm', $dataset['manager']['dmlist']);
            }
            if ($select === 'registerdm') {
                $form_state->set('registered_dm', $dataset['manager']['registerdm']);
                $manager = $dataset['manager']['registerdm'];
                $req_obj = [
                'person' => [
                  'firstname' =>  $manager['contact']['firstname'],
                  'lastname' =>  $manager['contact']['lastname'],
                  'email' =>  $manager['contact']['email'],
                  'federatedid' =>  $manager['contact']['federatedid'],
                ],
                'organization' => [
                    'longname' => $manager['longname'],
                    'shortname' => $manager['shortname'],
                    'contactemail' => $manager['contactemail'],
                    'homepage' => $manager['homepage'],

                ],
              ];
                $statusdm = $this->nirdApiClient->createOrganization($req_obj);
                //dpm($statusdm);
            }
        }


        //Process Rights Holder Institution
        if (array_key_exists('holder', $dataset)) {
            $select  = $dataset['holder']['rh-select'];
            if ($select === 'selectlistrh') {
                $form_state->set('selected_rh', $dataset['holder']['rhlist']);
            }

            if ($select === 'registerrh') {
                $form_state->set('registered_rh', $dataset['holder']['registerrh']);
                $holder = $dataset['holder']['registerrh'];
                $req_obj = [
                'person' => [
                  'firstname' =>  $holder['contact']['firstname'],
                  'lastname' =>  $holder['contact']['lastname'],
                  'email' =>  $holder['contact']['email'],
                  'federatedid' =>  $holder['contact']['federatedid'],
                ],
                'organization' => [
                    'longname' => $holder['longname'],
                    'shortname' => $holder['shortname'],
                    'contactemail' => $holder['contactemail'],
                    'homepage' => $holder['homepage'],

                ],
              ];
                $statusrh = $this->nirdApiClient->createOrganization($req_obj);
                //dpm($statusrh);
            }
        }
    }

    /**
     * final form to show when registration is finished
     */

    public function registrationConfirmedForm(array &$form, FormStateInterface $form_state)
    {
        $form['registration-message'] = [
    '#type' => 'markup',
    '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-green w3-pale-green w3-padding-16" id="nird-message">',
    '#markup' => '<span>Your dataset was successfully registerd with id <strong>'.$form_state->get('dataset_id').'</strong>. You will get an email when the dataset are published and uploaded to the archive.</span>',
    '#suffix' => '</div>',
    '#allowed_tags' => ['div', 'span','strong'],
  ];
        /*  $yaml = $form_state->get('yaml_file');
          $form['services-yaml'] = [
          '#type' => 'textarea',
          '#title' => 'dataset services config yaml',
          '#value' => Yaml::encode($yaml),
        ];

          $form['json'] = [
        '#type' => 'textarea',
        '#title' => 'Dataset registration summary as JSON object',
        '#default_value' => $form_state->get('json'),
    ];*/

        $form['another'] = [
  '#type' => 'submit',
  '#value' => $this->t('Upload and register another dataset'),
  '#submit' => ['::another'],
  '#limit_validation_errors' => [],
];


        return $form;
    }


    //Reupload another dataset.
    public function another(array &$form, FormStateInterface $form_state)
    {
        $form_state->setRedirect('dataset_upload.form');
    }

    /**
     * BUILD FORM PAGE 3
     */

    public function confirmServicesForm(array &$form, FormStateInterface $form_state)
    {
        //  dpm('building form page 3...');

        //$metadata = $form_state->getValue('metadata');
        //$form = self::formPageFive($form, $form_state);
        $form['validation-message'] = [
      '#type' => 'markup',
      '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-green w3-pale-green w3-padding-16" id="nird-message">',
      '#markup' => '<span>Your dataset(s) is compliant with CF and ACDD standards. The submission can now proceed.</span>',
      '#suffix' => '</div>',
      '#allowed_tags' => ['div', 'span'],
    ];



        $form['services'] = [
  '#type' => 'fieldset',
  '#title' => $this->t('Select type of dataset and services to be enabled'),
];
        $form['services']['select_conf']['dataset_type'] = array(
    '#title' => $this->t('Select the type of dataset you are uploading and the services you would like to activate for your dataset'),
    '#type' => 'radios',
    '#required' => true,
    '#options' => array('gridded_data' => $this->t('Gridded data'),
                        'time_seriesg' => $this->t('Time series gridded data'),
                        'time_series' => $this->t('Time series not gridded data')),
    '#default_value' => 'gridded_data',
  );


        // here we just upload a tgz that will have to be uncompressed and validated.
        $form['services']['select_conf']['gridded_data'] = array(
    '#title' => $this->t('Services'),
    '#type' => 'checkboxes',
    '#options' => array('https' => $this->t('Download of dataset (http(s))'),
                        'opendap' => $this->t('OPeNDAP (Remote access)'),
                        'wms' => $this->t('WMS client (Web Map Server)')
                  ),
    '#default_value' => array('https', 'opendap', 'wms'),
    '#states'=> array(
    'visible' => array(
        ':input[name="dataset_type"]' =>array('value' => 'gridded_data'),
                 ),
                 ),
  );

        $form['services']['select_conf']['time_seriesg'] = array(
    '#title' => $this->t('Services'),
    '#type' => 'checkboxes',
    '#options' => array('https' => $this->t('Download of dataset (http(s))'),
                        'opendap' => $this->t('OPeNDAP (Remote access)'),
                        'wms' => $this->t('WMS client (Web Map Server)')
                  ),
    '#default_value' => array('https', 'opendap', 'wms'),
    '#states'=> array(
    'visible' => array(
        ':input[name="dataset_type"]' =>array('value' => 'time_seriesg'),
                 ),
                 ),
  );

        $form['services']['select_conf']['time_series'] = array(
    '#title' => $this->t('Services'),
    '#type' => 'checkboxes',
    '#options' => array('https' => $this->t('Download of dataset (http(s))'),
                        'opendap' => $this->t('OPeNDAP (Remote access)')
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
      '#value' => $this->t('Confirm'),
      '#submit' => ['::confirmServices'],
      );

        $form['actions']['cancel'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Cancel submission'),
        '#submit' => ['::cancelSubmission'],
        '#limit_validation_errors' => [],
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
        '#default_value' => $form_state->get('nird_error'),
      ];

        //$form = self::confirmServicesForm($form, $form_state);
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

    public function validateNIRD(array &$form, FormStateInterface  $form_state)
    {
        //Get all form values.
        $dataset = $form_state->getValues()['dataset'];
        $dataset['subject'] =  $form_state->get('subjects_added');
        $form_state->set('current_dataset', $dataset);
        $depositors = count($dataset['depositor']);
        //\Drupal::logger('dataset_upload_validate_dataset')->debug('validation depositors: ' . $depositors);
        $creators = count($dataset['creator']);

        $holders = 1;
        $managers = count($dataset['data_manager']);
        //\Drupal::logger('dataset_upload_validate_dataset')->debug('validation depositors: ' . $creators);
        //\Drupal::logger('dataset_upload_validate_dataset')->debug('<pre><code>' . print_r($dataset, true) . '</code></pre>');

        if ($form_state->has('page') && $form_state->get('page') == 5) {
            /**
             * Validate depositor using Find Person API Callback
             */
            for ($i=0; $i<$depositors; $i++) {
                $depositor = $this->nirdApiClient->findPerson(
                    $dataset['depositor'][$i]['member']['firstname'],
                    $dataset['depositor'][$i]['member']['lastname'],
                    $dataset['depositor'][$i]['member']['email'],
                    $dataset['depositor'][$i]['member']['federatedid']
                );
                if (!(bool) $depositor['registered']) {
                    //\Drupal::logger('dataset_upload')->error('depositor not registered...trying to register.');
                    //$form_state->setErrorByName('dataset][depositor][member',"depositor not registered");
                    $depositor = $this->nirdApiClient->createPerson(
                        $dataset['depositor'][$i]['member']['firstname'],
                        $dataset['depositor'][$i]['member']['lastname'],
                        $dataset['depositor'][$i]['member']['email'],
                        $dataset['depositor'][$i]['member']['federatedid']
                    );
                //\Drupal::logger('dataset_upload')->debug('depositor registered? ' . $depositor['registered']);
                } else {
                    \Drupal::logger('dataset_upload')->debug('depositor registered');
                }
            }
            /**
             * Validate data manager using Find Organization API Callback
             */
            $i = 0;
            $manager = $dataset['data_manager'][$i]['manager'];
            if (array_key_exists('firstname', $manager)) {
                $data_manger = $this->nirdApiClient->findPerson(
                    $dataset['data_manager'][$i]['manager']['firstname'],
                    $dataset['data_manager'][$i]['manager']['lastname'],
                    $dataset['data_manager'][$i]['manager']['email'],
                    $dataset['data_manager'][$i]['manager']['federatedid']
                );
                if (!(bool) $data_manger['registered']) {
                    //\Drupal::logger('dataset_upload')->error('data_manger not registered...trying to register.');
                    //$form_state->setErrorByName('dataset][data_manger][member',"data_manger not registered");
                    $data_manger = $this->nirdApiClient->createPerson(
                        $dataset['data_manager'][$i]['manager']['firstname'],
                        $dataset['data_manager'][$i]['manager']['lastname'],
                        $dataset['data_manager'][$i]['manager']['email'],
                        $dataset['data_manager'][$i]['manager']['federatedid']
                    );
                //\Drupal::logger('dataset_upload')->debug('data_manger registered');
                } else {
                    \Drupal::logger('dataset_upload')->debug('data_manger registered');
                }
            }

            if (array_key_exists('longname', $manager)) {
                $data_manger = $this->nirdApiClient->findOrganization(
                    $dataset['data_manager'][$i]['manager']['longname'],
                    $dataset['data_manager'][$i]['manager']['shortname'],
                    $dataset['data_manager'][$i]['manager']['contactemail'],
                    $dataset['data_manager'][$i]['manager']['homepage']
                );
                if (!(bool) $data_manger['registered']) {
                    //\Drupal::logger('dataset_upload')->error('data_manger not registered...trying to register.');
                    //$form_state->setErrorByName('dataset][data_manger][member',"data_manger not registered");
                    /*    $data_manger = $this->nirdApiClient->createOrganization(
                            $dataset['data_manager'][$i]['manager']['longname'],
                            $dataset['data_manager'][$i]['manager']['shortname'],
                            $dataset['data_manager'][$i]['manager']['contactemail'],
                            $dataset['data_manager'][$i]['manager']['homepage']
                        );
                        \Drupal::logger('dataset_upload')->debug('data_manger institution registered status <pre><code>' . print_r($data_manger, true) . '</code></pre>');
                        if (!(bool) $data_manger['registered']) {*/
                    $form_state->set('manager_inst_failed', true);
                    $form_state->set('page', 4);
                    $form_state->setRebuild();
                //\Drupal::logger('dataset_upload')->debug('data_manger registered');
                    //}
                } else {
                    \Drupal::logger('dataset_upload')->debug('data_manger registered');
                }
            }


            /*        $json = [
               'person' => $dataset['rights_holder']['person'],
               'organization' => $dataset['rights_holder']['holder'],
             ];*/
            $rights_holder = $this->nirdApiClient->findOrganization(
                $dataset['rights_holder']['holder']['longname'],
                $dataset['rights_holder']['holder']['shortname'],
                $dataset['rights_holder']['holder']['contactemail'],
                $dataset['rights_holder']['holder']['homepage']
            );


            if (!(bool) $rights_holder['registered']) {
                //\Drupal::logger('dataset_upload')->error('rights_holder not registered...');
                //$form_state->setErrorByName('dataset][data_manager][manager',"Data manager not registered");
                /*      $holder_person = $this->nirdApiClient->findPerson(
                          $dataset['rights_holder']['person']['firstname'],
                          $dataset['rights_holder']['person']['lastname'],
                          $dataset['rights_holder']['person']['email'],
                          $dataset['rights_holder']['person']['federatedid']
                      );
                      if (!(bool) $holder_person['registered']) {
                          $holder_person = $this->nirdApiClient->createPerson(
                              $dataset['rights_holder']['person']['firstname'],
                              $dataset['rights_holder']['person']['lastname'],
                              $dataset['rights_holder']['person']['email'],
                              $dataset['rights_holder']['person']['federatedid']
                          );

                          \Drupal::logger('dataset_upload')->debug('rights holder person registered');
                      } else {
                          \Drupal::logger('dataset_upload')->debug('rights holder person registered');
                      }
                      $rights_holder = $this->nirdApiClient->createOrganization(
                        $dataset['rights_holder']['holder']
                          //$json
                      );
                      \Drupal::logger('dataset_upload')->debug('rights_holder registered');
                      \Drupal::logger('dataset_upload_validate')->debug('<pre><code>' . print_r($rights_holder, true) . '</code></pre>');
*/
                $form_state->set('holder_inst_failed', true);
                $form_state->set('page', 4);
                $form_state->setRebuild();
            }
            //}
            else {
                \Drupal::logger('dataset_upload')->debug('rights_holder registered');
            }


            /**
             * Validate rights holder using Find Organization API Callback
             */
      /*
      $rights_holder = $this->nirdApiClient->findOrganization(
        $dataset['rights_holder']['holder']['longname'],
        $dataset['rights_holder']['holder']['shortname'],
        $dataset['rights_holder']['holder']['contactemail'],
        $dataset['rights_holder']['holder']['homepage']
      );

      if(!(bool) $rights_holder['registered']) {
          \Drupal::logger('dataset_upload')->error('Rights holder not registered...trying to register.');
          //$form_state->setErrorByName('dataset][rights_holder][holder',"Rights holder not registered");
          $rights_holder = $this->nirdApiClient->createOrganization(
            $dataset['rights_holder']['holder']['longname'],
            $dataset['rights_holder']['holder']['shortname'],
            $dataset['rights_holder']['holder']['contactemail'],
            $dataset['rights_holder']['holder']['homepage']
          );
          \Drupal::logger('dataset_upload')->debug('Rights holder registered');
      }
      else {
        \Drupal::logger('dataset_upload')->debug('Rights holder registered');
      }*/
        }
    }

    /*
   * {@inheritdoc}
   * Main submit form. (Last step).
   */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        //\Drupal::messenger()->addMessage(t("Final submitform"));
        $form_state->set('page', 6);
        //dpm($form_state);
        //Check services selected and create services config file.
        $session = \Drupal::request()->getSession();
        $upload_path = $session->get('upload_path');
        $user_id = $this->currentUser->id();

        $dataset = $form_state->getValues()['dataset'];

        //Do some processing if we created or selected data manager or rights holder institutions
        if (is_null($dataset)) {
            //\Drupal::logger("Warning dataset empty..get from formstate");
            $dataset = $form_state->get('current_dataset');
            //$register =
            //$form_state = $form_state->get('current_state');
        }

        //dpm($form_state);

        //\Drupal::logger('dataset_upload_dataset_before')->debug('<pre><code>' . print_r($dataset, true) . '</code></pre>');

        /**
         * Modify array of form values and encode to json for
         * the create dataset api call
         */
        unset($dataset['message']);
        $category = $dataset['category'];
        $lang = $dataset['language'];
        $licence = $dataset['licence'];

        //Override licence for testing.
        /*  $licence = [
            'name' => 'Norwegian Licence for Open Government Data (NLOD)',
            'archive' => 'http://data.norge.no/nlod/en/1.0',
            'access' => 'http://data.norge.no/nlod/en/1.0',
          ];
*/
        $article = $dataset['article'];
        $depositor = $dataset['depositor'];
        unset($dataset['rights_holder']['person']);
        $holder = $dataset['rights_holder'];
        $published = $article['publication']['article-select'];
        //$published  = $form_state->getValue(['dataset','article','publication','article-select']);
        //dpm($published);
        if ($published === 'published') {
            unset($article['publication']['article-select']);
            unset($article['publication']['no_publication']);
            $article['publication'] = [
              'published' => true,
              'reference' => $article['publication']['published']['reference'],
            ];
        } else {
            unset($article['publication']['article-select']);
            unset($article['publication']['published']);
            $article['publication'] = [
            'no_publication' => true,
            'motivation' => $article['publication']['no_publication']['motivation'],
          ];
        }
        //Datamanger
        $manager = $dataset['data_manager'];
        unset($manager['actions']);

        $manager_new = [];
        /*
        if(isset($manager['manager']['person'])) {
          $dm_person = $manager['manager']['person'];
*/
        /*    if (isset($manager['manager'])) {
                $dm_person = $manager['manager'];

                foreach ($dm_person as $p) {
                    $obj = (object) [
                'manager' => $p
              ];
                    array_push($manager_new, $obj);
                }
            }

            if (isset($manager['manager']['organization'])) {
                $dm_org = $manager['manager']['organization'];

                foreach ($dm_org as $o) {
                    $obj = (object) [
                'manager' => $o
              ];
                    array_push($manager_new, $obj);
                }
            }*/
        /*
                //Rights holder
                //$holder = [];
                //array_push($holder,$dataset['rights_holder']['holder']['person']);
                //$dataset['rights_holder']['holder']['person']['lastname'],
                //$dataset['rights_holder']['holder']['person']['email'],
                //$dataset['rights_holder']['holder']['person']['federatedid']]
                //); //[ (object) [ 'holder' => $dataset['rights_holder']['holder']]]; //,
                //array_push($holder,$dataset['rights_holder']['holder']['organization']);
                //$dataset['rights_holder']['holder']['organization']['shortname'],
                //$dataset['rights_holder']['holder']['organization']['contactemail'],
                //$dataset['rights_holder']['holder']['organization']['homepage']]); //[ (object) [ 'holder' => $dataset['rights_holder']['holder']]]; //,

                //( object)[ 'holder' => $dataset['rights_holder']['holder']['organization']]];
        */
        //Creator
        $creator = $dataset['creator'];

        //Subject
        $subject = $dataset['subject'];

        //FOR testing
        /*  $subject = (object) [
             'domain' => 'Natural sciences',
             'field' => 'Space science',
             'subfield' => 'Astrophysics'
           ];
           */
        $created = $dataset['created'];
        $dataset['created'] = $dataset['created'];

        //$dataset['created'] = $form_state->getValue(['dataset','created']);
        //$dataset['created'] = $form_state->getValue(['dataset','created']); //->format('Y-m-d');
        $dataset['category'] = [[
          'name' => $category,
        ]];
        $dataset['language'] = [[
          'name' => $lang,
        ]];


        $dataset['licence'] = [
          'id' => $licence,
        ];
        //    'id' => $licence,
        //  ];
        $dataset['article'] = [
          $article
        ];
        $dataset['depositor'] = $depositor;



        //$dataset['data_manager'] = $manager; //_new;
        if ($form_state->has('selected_rh')) {
            $rht = $form_state->get('rh-insts');
            $idx = $form_state->get('selected_rh');
            $rh = $rht['organizations'][$idx];
            $dataset['rights_holder'] = [
            'holder' => [
              'longname' => $rh['orglongname'],
              'shortname' => $rh['orgshortname'],
              'contactemail' => $rh['contactemail'],
              'homepage' => $rh['homepage'],
            ],
        ];
        } elseif ($form_state->has('registered_rh')) {
            $inst = $form_state->get('registered_rh');
            $dataset['rights_holder'] = [
            'holder' => [
              'longname' => $inst['longname'],
              'shortname' => $inst['shortname'],
              'contactemail' => $inst['contactemail'],
              'homepage' => $inst['homepage'],
            ],
        ];
        } else {
            $dataset['rights_holder'] = $holder;
        }

        if ($form_state->has('selected_dm')) {
            $dmt = $form_state->get('dm-insts');
            $idx = $form_state->get('selected_dm');
            $dm = $dmt['organizations'][$idx];
            $dataset['data_manager'] = [[
            'manager' => [
              'longname' => $dm['orglongname'],
              'shortname' => $dm['orgshortname'],
              'contactemail' => $dm['contactemail'],
              'homepage' => $dm['homepage'],
            ]],
        ];
        } elseif ($form_state->has('registered_dm')) {
            $inst = $form_state->get('registered_dm');
            $dataset['data_manager'] = [
        'holder' => [
          'longname' => $inst['longname'],
          'shortname' => $inst['shortname'],
          'contactemail' => $inst['contactemail'],
          'homepage' => $inst['homepage'],
        ],
    ];
        } else {
        }

        //  'holder' =>$h
        //];

        $dataset['creator'] = $creator;

        /**
         * TODO: Maybe have some extra check if the lists are selected but no subject added to array.
          */
        if ($form_state->has('subjects_added')) {
            $dataset['subject'] =  $form_state->get('subjects_added');
        } else {
            $dataset['subject'] = [[
                'domain' => $subject['domain'],
                'field' => $subject['field'],
                'subfield' => $subject['subfield'],
            ]];
        }
        /*[
          $subject
        ];*/

        // Save the json response in the from_state.
        $json = Json::encode($dataset);
        $form_state->set('json', $json);
        //\Drupal::logger('dataset_upload')->debug($json);
        //\Drupal::logger('dataset_upload')->debug('<pre><code>' . print_r($dataset, true) . '</code></pre>');
        //\Drupal::logger('dataset_upload')->debug('<pre><code>' . print_r(Json::encode($json), true) . '</code></pre>');


        //\Drupal::logger('dataset_upload_dataset_after')->debug('<pre><code>' . print_r($dataset, true) . '</code></pre>');

        /*
         * Call the NIRD API create dataset endpoint
        */
        //$result = '';
        $result = $this->nirdApiClient->createDataset($dataset);
        //\Drupal::logger('dataset_upload')->debug('<pre><code>' . print_r($result, true) . '</code></pre>');
        $form_state->set('dataset_response', Json::encode($result));
        $form_state->set('dataset', $dataset);
        //Store the given dataset_id for success

        if (isset($result['dataset_id'])) {
            /** NIRD dataset registration SUCCESS */
            $form_state->set('dataset_id', $result['dataset_id']);
            \Drupal::logger('nird')->notice('Registered dataset with id: ' . $result['dataset_id']);
            /**
             * Creating YAML dataset THREDDS information object
             */
            $yaml = [
              'services' => $form_state->get('yaml_services'),
              'dataset' => [
                'name' => $result['dataset_id'],
                'title' => $dataset['title'],
              ],
            ];
            if ($form_state->has('agg_var')) {
                $yaml['aggregation'] = [
                'variable' => $form_state->get('agg_var'),
              ];
            }

            //Create md5sum for uploaded netcdf file
            $file_id = $form_state->get('upload_fid');
            $file = File::load($file_id);
            $uri = $file->getFileUri();
            $base_path = \Drupal::service('file_system')->realpath($form_state->get('upload_location'));

            $filename = $file->getFilename();
            //dpm($base_path);
            //dpm($filename);
            $mime_type = $file->getMimeType();
            if ($mime_type === 'application/x-netcdf') {
                //$md5sum = md5_file($base_path.'/'.$filename);
                //file_put_contents($base_path.'/'.$filename.'.md5', $md5sum, \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE);
            } else {
                $file->delete(); //Delete the uploaded archive after extraction
            }
            $source_path = \Drupal::service('file_system')->realpath($form_state->get('upload_location'));
            //$dest_path = $form_state->get('upload_basepath') . $result['dataset_id']. '/';
            $dest_path = 'private://nird/toArchive/' . $result['dataset_id'];

            $manifest = '/projects/NS45678/import/data/' .$result['dataset_id'];

            $fs = \Drupal::service('file_system');
            $fs->move($source_path, $dest_path);
            $yaml_filepath = $dest_path.'/'. $result['dataset_id'] . '.yml';
            //$yaml_file = fopen($yaml_filepath, 'w');
            //fwrite($yaml_file, Yaml::dump($yaml));
            //fwrite($yaml_file, $yaml);
            //fclose($yaml_file);
            $yml = Yaml::encode($yaml);
            //Write YAML file to disk
            file_put_contents($yaml_filepath, $yml, \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE);

            //Write manifest to disk
            file_put_contents($dest_path .'/MANIFEST', $manifest, \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE);


            //$yaml_file = file_save_data(Yaml::dump($yaml), $yaml_filepath, FileSystemInterface::EXISTS_REPLACE);
            //$yaml_file->save();
            $form_state->set('yaml_file', $yaml);
            $session->set('dataset_upload_status', 'registered');

            /** Add dataset to custom QueueWorker */
            $queue = \Drupal::service('queue')->get('nird_upload_queue');
            $item = new \stdClass();
            $item->uid = $this->currentUser->id();
            $item->dataset_id = $result['dataset_id'];
            $item->nird_status = 'registered';
            $item->path =  \Drupal::service('file_system')->realpath($dest_path);
            $item->doi = null;
            $item->title = $dataset['title'];

            // Add the file id to queue item if the file is a sigle netCDF
            if ($file->getMimeType() === 'application/x-netcdf') {
                $item->fid = $file_id;
            }
            //Add queue item to Queue.
            $queue->createItem($item);
        }
        if (isset($result['error'])) {
            /** NIRD dataset registration FAIL */
            $form_state->set('nird_error', $result['error']);
            $this->cleanUp($user_id, $form_state);
        }
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
        $this->cleanUp($user_id, $form_state);

        $form_state->setRedirect('dataset_upload.form');
    }

    private static function cleanUp($user_id, $form_state)
    {
        //\Drupal::logger('dataset_upload')->debug("Clean up session variables and files.");
        $session = \Drupal::request()->getSession();
        if ($form_state->has('upload_fid')) {
            $fid = $form_state->get('upload_fid');
            $file = File::load($fid);
            if (isset($file)) {
                $file->delete();
            }
        }
        $upload_path = 'private://dataset_upload_folder/';
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
        //$filesystem->deleteRecursive($upload_path . $user_id . '/' . $session_id);
        $filesystem->deleteRecursive($form_state->get('upload_location'));
        /*
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
        }*/
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
        //$form_state->cach
    }

    /**
     * Form action step 3
     * Confirm metadata and display selection of services form
     */
    public function confirmAggregationForm(array &$form, FormStateInterface $form_state)
    {
        $form['aggregation-message'] = [
     '#type' => 'markup',
     '#prefix' => '<div class="w3-panel w3-leftbar w3-container w3-border-yellow w3-pale-yellow w3-padding-16" id="agg-message">',
     '#markup' => '<span>Your uploaded archive contains more than one netCDF file. If you want the datasets to be aggregated on the THREDDS service, tick this checkbox and enter a common variable name for aggregation.</span>',
     '#suffix' => '</div>',
     '#allowed_tags' => ['div', 'span'],
   ];

        $form['agg-fail'] = $this->aggChecker->getMessage();
        $form['doagg'] = [
          '#type' => 'checkbox',
          '#title' => t('Tick this box to aggregate datasets'),

        ];
        $form['aggregation'] = [
'#type' => 'textfield',
'#title' => $this->t('Enter the variable name for aggregation'),
'#states' => array(
    // Only show this field when the 'toggle_me' checkbox is enabled.
    'visible' => array(
      ':input[name="doagg"]' => array(
        'checked' => true,
      ),
    ),
  ),
];


        $form['actions']['submit'] = array(
'#type' => 'submit',
'#button_type' => 'primary',
'#value' => $this->t('Confirm'),
'#submit' => ['::confirmAggregation'],
);

        $form['actions']['cancel'] = array(
'#type' => 'submit',
'#value' => $this->t('Cancel submission'),
'#submit' => ['::cancelSubmission'],
'#limit_validation_errors' => [],
);

        return $form;
    }
    public function confirmAggregation(array &$form, FormStateInterface $form_state)
    {
        //\Drupal::messenger()->addMessage(t("Metadata confirmation form"));
        $form_state->set('page', 2);
        $user_id = $this->currentUser->id();
        $base_path = \Drupal::service('file_system')->realpath($form_state->get('upload_location'));
        $agg_var = $form_state->getValue('aggregation');
        //\Drupal::logger('dataset_upload')->debug($form_state->getValue('aggregation'));
        $archived_files = $form_state->get('agg_files');
        //dpm($archived_files);
        //\Drupal::messenger()->addMessage($archived_files);
        //create string with list of files which are input to the agg_checker.py
        $files_to_agg = '';
        if ($form_state->getValue('doagg')) {
            //Loop the files in archive and create md5sum and get filepaths
            foreach ($archived_files as $file) {
                //$files_to_agg .= $base_path.'/extract/'.$file.' ';
                /*
                $files_to_agg .= $base_path. '/' .$file.' ';
                $md5sum = md5_file($base_path. '/' .$file);
                file_put_contents($base_path. '/' .$file.'.md5', $md5sum, \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE);
                */
                $files_to_agg .= $file.' ';
                //$md5sum = md5_file($file);
                //file_put_contents($file.'.md5', $md5sum, \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE);
            }

            //Call the aggregation checker service
            $status = $this->aggChecker->check($files_to_agg, $agg_var);
            //dpm($status);
            if (!$status) {
                $form_state->set('page', 3);
                $form_state->setRebuild();
            }

            $form_state->set('agg_var', $agg_var);
        }

        $form_state->setRebuild();
        //return $form;
    }

    public function confirmServices(array &$form, FormStateInterface $form_state)
    {
        \Drupal::messenger()->addMessage(t("Confirm services form action."));
        //Check services selected and create services config file.
        $form_state->set('page', 5);


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
        $services = [];
        if ($selected_checkboxes['https'] !== 0) {
            $services[] = 'http';
        }
        if ($selected_checkboxes['opendap'] !== 0) {
            $services[] = 'opendap';
        }
        if ($selected_checkboxes['wms'] !== 0) {
            $services[] = 'wms';
        }
        //dpm($services);

        $form_state->set('yaml_services', $services);
        /*
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
        */



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

    public function publicationSelectCallback(array &$form, FormStateInterface $form_state)
    {
        $response = new AjaxResponse();

        $published = $form_state->getValue(['dataset', 'article', 'publication', 'select']);
        //\Drupal::logger('dataset_upload')->debug('published?: ' .$published);
        if ($published) {
            /*    $form['dataset']['article']['publication']['published'] = [
                '#type' => 'hidden',
                '#value' => true,
                //'#pefix' => '<div id="published">',
                //'#suffix' => '</div>',
              ];*/
            $form['dataset']['article']['publication']['published']['reference']['doi'] = [
    '#type' => 'textfield',
    '#title' =>  $this->t('DOI reference'),
    '#required' => true,
  ];
            //$form_state->set('has_publication', $form['dataset']['article']['publication']['published']['reference']['doi']);
            $response->addCommand(new ReplaceCommand('#publication-wrapper', $form['dataset']['article']['publication']));
            return $form['dataset']['article']['publication'];
        } else {
            /*$form['dataset']['article']['publication']['no_publication'] = [
    '#type' => 'hidden',
    '#value' => false,
  ];*/
            $form['dataset']['article']['publication']['no_publication']['motivation'] = [
'#type' => 'textfield',
'#title' =>  $this->t('Motivation'),
'#required' => true,
];
            //$form_state->set('no_publication', $form['dataset']['article']['publication']['no_publication']['motivation']);
            $response->addCommand(new ReplaceCommand('#publication-wrapper', $form['dataset']['article']['publication']));
            return $form['dataset']['article']['publication'];
        }
        //return $form['dataset']['article']['publication'];
    }


    public function subjectCallback(array &$form, FormStateInterface $form_state)
    {
        return  $form['dataset']['subject'];
    }

    public function addSubjectCallback(array &$form, FormStateInterface $form_state)
    {
        /*    $response = new AjaxResponse();
            $domain = $form_state->getValue(array('dataset','subject','domain'));
            $field = $form_state->getValue(array('dataset','subject','field'));
            $subfields = array_unique(array_column($this->nirdApiClient->getSubField($domain, $field), 'subfield'));
            //dpm($fields);
            $field = [
              'subfield' => [
              '#type' => 'select',
              '#title' => $this->t("Field"),
              '#empty_option' => $this->t('- Select field -'),
              '#options' => array_combine($fields,$fields),
              '#ajax' => [
                'wrapper' => 'subject-wrapper',
                'callback' => '::fieldCallback',
                'event' => 'change'
              ],
            ],
            ];

            $response->addCommand(new AppendCommand('#subject-wrapper', $field));
            return $response;
*/
        $form_state->unsetValue(array('dataset','subject','domain'));
        $form_state->unsetValue(array('dataset','subject','field'));
        $form_state->unsetValue(array('dataset','subject','subfield'));
        $form['dataset']['subject']['domain']['#value'] = '';
        return  $form['dataset']['subject'];
    }



    /**
     * Submit handler for the "add  subject" button.
     *
     * Increments the max counter and causes a rebuild.
     */
    public function addSubject(array &$form, FormStateInterface $form_state)
    {
        $num_subjects = $form_state->get('num_subjects');
        $add_button = $num_subjects + 1;
        $form_state->set('num_subjects', $add_button);


        //Store the previous added subjects
        $domain = $form_state->getValue(array('dataset','subject','domain'));
        $field = $form_state->getValue(array('dataset','subject','field'));
        $subfield = $form_state->getValue(array('dataset','subject','subfield'));
        //dpm($domain);
        $subjects_added = $form_state->get('subjects_added');
        if ($subjects_added === null) {
            $subjects_added = [];
        }
        if (!empty($field) && !empty($domain) && !empty($subfield)) {
            array_push($subjects_added, [
        'domain' => $domain,
        'field' => $field,
        'subfield' => $subfield,
      ]);
            //$form_state->unsetValue(array('dataset','subject','domain'), '- Select domain -');

            $form_state->unsetValue(array('dataset','subject','domain'));
            $form_state->unsetValue(array('dataset','subject','field'));
            $form_state->unsetValue(array('dataset','subject','subfield'));
        }
        //dpm($subjects_added);
        $form_state->set('subjects_added', $subjects_added);

        // Since our buildForm() method relies on the value of 'num_names' to
        // generate 'name' form elements, we have to tell the form to rebuild. If we
        // don't do this, the form builder will not call buildForm().
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

    public function depositorCallback(array &$form, FormStateInterface $form_state)
    {
        return  $form['dataset']['depositor'];
    }

    public function addDepositorPerson(array &$form, FormStateInterface $form_state)
    {
        //$response = new AjaxResponse();
        $persons = 0;
        if ($form_state->has('added_depositor_persons')) {
            $persons = $form_state->get('added_depositor_persons');
        }
        //  if (empty($num_articles)) {

        //  }
        //\Drupal::logger('nordatanet_nird')->debug("number of articles before: " . $num_articles);
        $n = $persons +1;
        //\Drupal::logger('nordatanet_nird')->debug("number of articles after: " . $add_article);

        $form_state->set('added_depositor_persons', $n);
        $form_state->setRebuild();
    }
    public function removeDepositorPerson(array &$form, FormStateInterface $form_state)
    {
        //$response = new AjaxResponse();

        $persons = $form_state->get('added_depositor_persons');
        //  if (empty($num_articles)) {

        //  }
        //\Drupal::logger('nordatanet_nird')->debug("number of articles before: " . $num_articles);
        $n = $persons -1;
        if ($n < 1) {
            $n = 1;
        }
        //\Drupal::logger('nordatanet_nird')->debug("number of articles after: " . $add_article);

        $form_state->set('added_depositor_persons', $n);
        $form_state->setRebuild();
    }


    /*    public function addManagerOrgCallback(array &$form, FormStateInterface $form_state)
        {
            return   $form['dataset']['article']['publication'];
        }*/
    public function addDepositorOrg(array &$form, FormStateInterface $form_state)
    {
        //$response = new AjaxResponse();
        $orgs = 0;
        if ($form_state->has('added_depositor_orgs')) {
            $orgs = $form_state->get('added_depositor_orgs');
            //  if (empty($num_articles)) {
        }
        //  }
        //\Drupal::logger('nordatanet_nird')->debug("number of articles before: " . $num_articles);
        $n = $orgs +1;
        //\Drupal::logger('nordatanet_nird')->debug("number of articles after: " . $add_article);

        $form_state->set('added_depositor_orgs', $n);
        $form_state->setRebuild();
    }
    public function removeDepositorOrg(array &$form, FormStateInterface $form_state)
    {
        //$response = new AjaxResponse();

        $orgs = $form_state->get('added_depositor_orgs');
        //  if (empty($num_articles)) {

        //  }
        //\Drupal::logger('nordatanet_nird')->debug("number of articles before: " . $num_articles);
        $n = $orgs -1;
        if ($n < 0) {
            $n = 0;
        }
        //\Drupal::logger('nordatanet_nird')->debug("number of articles after: " . $add_article);

        $form_state->set('added_depositor_orgs', $n);
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
