<?php

namespace Drupal\dataset_upload\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
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
//    return new static(
//      $container->get('dataset_upload.client'),
 //     $container->get('dataset_upload.breed_factory')
  //  );
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
        $form['aggregation']['message'] = array(
'#type' => 'textfield',
'#required' => true,
'#title' => t('You have selected a multiple file upload. Your files will be aggregated on the server. <br>
         Declare the variable on which you wish to aggregate your netcdf files. <br>
         This field MUST match the exact name of the variables in your netCDF files'),
//'#element_validate' => TODO:: Create custom validation to chek that given variable are common for all datasets to be aggregated
);
}

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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {


  }

}
