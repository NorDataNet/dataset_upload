<?php

// General Dataset Upload administration page to set the name of the upload folder
function dataset_upload_admin($form, &$form_state) {
  $form = array();

  $form['#prefix']  = '<h2>Dataset Upload Administration</h2>';

  $form['dataset_upload_folder'] = array(
    '#type' => 'textfield',
    '#title' => t('Select folder name'),
    '#description' => t("Select folder name for dataset upload. It will be created into the public:// folder of the site"),
    '#default_value' => variable_get('dataset_upload_folder','dataset_upload_folder'),
  );

  return system_settings_form($form);
}

//function dataset_upload_admin_validate($form, &$form_state){
//  $folder = $form_state['values']['dataset_upload_folder'];
//  // Check if directory exists.
//  if (is_dir($folder)){
//     drupal_set_message(t('Directory name exists already. Choose another name and save settings'),'warning');
//  } 
//}

