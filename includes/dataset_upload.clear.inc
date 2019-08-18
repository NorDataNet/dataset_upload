<?php

// clear when a user has only uploaded the files, but have not done any submission
function dataset_upload_multipage_form_clear_p1($form, &$form_state, $error_msg = 'Your dataset upload has been cancelled') {
  drupal_set_message($error_msg,'warning');

  if ($form['single_file']['#file']){
    file_delete($form['single_file']['#file']);
  }
  if ($form['compressed_file']['#file']){
    file_delete($form['compressed_file']['#file']);
  }

  if (isset($form_state['dataset_dir'])){
    file_unmanaged_delete_recursive($form_state['dataset_dir']);
  }

  $form_state['rebuild'] = TRUE;
  $form_state['input'] = array();
}

function dataset_upload_multipage_form_clear_p2($form, &$form_state) {
  drupal_set_message(t('Your dataset upload has been cancelled'),'warning');

// need to delete records: folder recursively and DB
  file_delete($form_state['dataset_file']);
  foreach ($form_state['unzipped_files'] as $uzf) {
   file_delete($uzf);
  }
  file_unmanaged_delete_recursive($form_state['dataset_dir']);

  if ($form_state['metadata']){
    unset ($form_state['metadata']); 
  }
  unset ($form_state['storage']); 
  $form_state['rebuild'] = TRUE;
  $form_state['input'] = array();
}

function dataset_upload_multipage_form_clear_p3($form, &$form_state) {
  drupal_set_message(t('Your dataset upload has been cancelled'),'warning');
//    drupal_set_message('<pre>'.print_r($form_state['dataset_dir'],TRUE).'</pre>','warning');
//    drupal_set_message('<pre>'.print_r($form_state['unzipped_files'],TRUE).'</pre>','warning');

// need to delete records: folder recursively and DB
  file_delete($form_state['dataset_file']);
  foreach ($form_state['unzipped_files'] as $uzf) {
    file_delete($uzf);
  }
  file_unmanaged_delete_recursive($form_state['dataset_dir']);

  if (isset($form_state['storage'])){
    unset ($form_state['storage']); 
  }

  $form_state['rebuild'] = TRUE;
  $form_state['input'] = array();
}
