<?php

function dataset_upload_multipage_validate_allnc($form, &$form_state) {
  global $user; 
  if($form_state['values']['upload_type'] == 'compressed_file'){
    $fdir = drupal_realpath('public://'); // /var/www/multisite/sites/mysite/files
    // folder defined by the module configuration
    $folder = variable_get('dataset_upload_folder');
    $uid = $user->uid;
    $file_upl_folder = $folder.'/'.$uid;
    $array_uc_files = array();
    $fid = $form["#file"]->fid;         // file id number, e.g. 12444
    $fuuid = $form["#file"]->uuid;      // file uuid, e.g. 79ce9c0d-8bdf-4c1a-8346-d08f926690fa  
    $fname = $form["#file"]->filename;  // file name, e.g. t.nc
    $furi = $form["#file"]->uri;         // public://dataset_upload_folder/uid/t.nc  or public://dataset_upload_folder/uid/t.tgz
  
    //define some paths for handling of validation
    $rel_file = explode("://", $furi)[1];                           // dataset_upload_folder/uid/t.nc or dataset_upload_folder/uid/t.tgz
    $full_path_to_folder  = $fdir.'/'.$file_upl_folder.'/'.$fuuid;  // /var/www/multisite/sites/mysite/files/dataset_upload_folder/uid/uuid 
    $full_path_to_file    = $fdir.'/'.$rel_file;                    // /var/www/multisite/sites/mysite/files/dataset_upload_folder/uuid/file.tgz
    $uri_to_folder  = 'public://'.$file_upl_folder.'/'.$fuuid;      // public://dataset_upload_folder/uid/uuid
   
    //Checks that the directory exists and is writable.
    file_prepare_directory($full_path_to_folder, FILE_CREATE_DIRECTORY);

    if(file_exists($furi)){
      file_move($form["#file"], $uri_to_folder.'/'.$fname, FILE_EXISTS_REPLACE);
      $form_state['dataset_file'] = $form["#file"];
      $form_state['dataset_dir'] = $uri_to_folder;
      $form_state['unzipped_files'] = array();
    
      $cfile = file_load($form["#file"]->fid)->uri;
      $archiver = archiver_get_archiver($cfile);
      try {
        $files = $archiver->listContents();
      } catch(Exception $e) {
         drupal_set_message(t('Cannot extract %file, not a valid archive.', array ('%file' => $fname)),'error');
         //dataset_upload_multipage_form_clear_p1($form, $form_state);
      }
      $archiver->extract($uri_to_folder);
      foreach ($files as $i){
     
        $fut = new stdClass;
        $urif = $uri_to_folder.'/'.$i;
        $fut->uid = $user->uid;
        $fut->filename = $i;
        $fut->uri = $urif;
        $fut->filesize = filesize($urif);
        $fut->status = 1;
      
        file_save($fut);
        array_push($array_uc_files, $fut);
    
        //check that files in the compressed file are of netCDF format
        $val_content = file_validate_extensions($fut, 'nc');
        if (!empty($val_content)){
          foreach ($array_uc_files as $vv){
            file_delete($vv);
          }
          form_set_error('compressed_file','At least one of your files is not netCDF. The submission cannot proceed');
          file_delete($form_state['dataset_file']);
          file_unmanaged_delete_recursive($form_state['dataset_dir']);
        }
      }
      $form_state['unzipped_files'] = $array_uc_files;
    }
  }
}

function dataset_upload_multipage_p1_validate($form, &$form_state) {
  global $user; 

  //only validates page1
  if (isset($form_state['storage']['page_two']) || isset($form_state['storage']['page_three'])) {
    return;
  }
  
  $fdir = drupal_realpath('public://'); // /var/www/multisite/sites/mysite/files
  // drupal_set_message('<pre>'.print_r($fdir,TRUE).'</pre>','warning');
  // folder defined by the module configuration
  $folder = variable_get('dataset_upload_folder');
  $uid = $user->uid;
  $file_upl_folder = $folder.'/'.$uid;

  //validate that a file has been uploaded, if not give form_set_error
  if ($form['single_file']["#file"] || $form['compressed_file']["#file"]){

    $array_uc_files = array();
    //find out which file type has been uploaded (ufile). This is probably the wrong way to do it. 
    if ($form['single_file']["#file"]){
      $ufile = $form['single_file'];
    } else {
      $ufile = $form['compressed_file'];
    }
    
    $fid = $ufile["#file"]->fid;         // file id number, e.g. 12444
    $fuuid = $ufile["#file"]->uuid;      // file uuid, e.g. 79ce9c0d-8bdf-4c1a-8346-d08f926690fa  
    $fname = $ufile["#file"]->filename;  // file name, e.g. t.nc
    $furi = $ufile["#file"]->uri;         // public://dataset_upload_folder/uid/t.nc  or public://dataset_upload_folder/uid/t.tgz

    //define some paths for handling of validation
    $rel_file = explode("://", $furi)[1];                           // dataset_upload_folder/uid/t.nc or dataset_upload_folder/uid/t.tgz
    $full_path_to_folder  = $fdir.'/'.$file_upl_folder.'/'.$fuuid;  // /var/www/multisite/sites/mysite/files/dataset_upload_folder/uid/uuid 
    $full_path_to_file    = $fdir.'/'.$rel_file;                    // /var/www/multisite/sites/mysite/files/dataset_upload_folder/uuid/file.tgz
    $uri_to_folder  = 'public://'.$file_upl_folder.'/'.$fuuid;      // public://dataset_upload_folder/uid/uuid
 
    //Checks that the directory exists and is writable.
    //mv the file to the new directory
    file_prepare_directory($full_path_to_folder, FILE_CREATE_DIRECTORY);
    if ($form['single_file']["#file"]) {
      $ufile['#file'] = file_move($ufile["#file"], $uri_to_folder.'/'.$fname, FILE_EXISTS_REPLACE);
    }
    //pass file and dir
    $form_state['dataset_file'] = $ufile["#file"];
    $form_state['dataset_dir'] = $uri_to_folder;
    $form_state['dataset_fullpath'] = $full_path_to_folder;
    $form_state['unzipped_files'] = array();
    //  drupal_set_message('<pre>'.print_r($form_state['unzipped_files'],TRUE).'</pre>','warning');
 
    if(strpos($furi,'.tgz') || strpos($furi, '.zip') || strpos($furi, '.tar')){
      //load compressed file and decompress
      $cfile = file_load($fid)->uri;
      $archiver = archiver_get_archiver($cfile);
      //if (!$archiver) {
      //   throw new Exception(t('Cannot extract %file, not a valid archive.', array ('%file' => $cfile)));
      //}
      try {
        $files = $archiver->listContents();
      } catch(Exception $e) {
         drupal_set_message(t('Cannot extract %file, not a valid archive.', array ('%file' => $fname)),'error');
         dataset_upload_multipage_form_clear_p1($form, $form_state);
      }
      $archiver->extract($uri_to_folder);
      
      $non_nc_discovered = FALSE;
      //make the files in the tgz Class Object
      foreach ($files as $i){
 
        $fut = new stdClass;
        $urif = $uri_to_folder.'/'.$i;
        $fut->uid = $user->uid;
        $fut->filename = $i;
        $fut->uri = $urif;
        $fut->filesize = filesize($urif);
        $fut->status = 1;
  
        file_save($fut);
        array_push($array_uc_files, $fut);

        //check that files in the compressed file are of netCDF format
        $failed_ext = file_validate_extensions($fut, 'nc');
        // true if violating file is found
        $non_nc_discovered = ($non_nc_discovered || !empty($failed_ext)) ;
      }
      if ($non_nc_discovered) {
        foreach ($array_uc_files as $vv){
          file_delete($vv);
        }
        $array_uc_files = array();
        form_set_error('compressed_file','At least one of your extracted files is not netCDF. The submission cannot proceed');
        dataset_upload_multipage_form_clear_p1($form, $form_state);
        return;
      }
      file_delete($ufile['#file']);
      $form_state['unzipped_files'] = $array_uc_files;
    } else {
      array_push($array_uc_files,$ufile['#file']);
    }

    // extract mmd to the last child
    function depth_mmd($prefix, $iterator) {
       $kv_a = array();
       foreach ($iterator as $k => $v) {
          if ($iterator->hasChildren()) {
             $kv_a = array_merge($kv_a, depth_mmd($prefix . ' ' . $k, $v));
          } else {
             //add mmd keys and values to form_state to be passed to the second page. 
             $kv_a[] = array($prefix . ' ' . $k, (string)$v);
          }
       }
       return $kv_a; //this function returns an array of arrys
    }
 
    // for each file in the tgz, define outputs
    $fls = preg_grep('/^([^.])/', scandir($full_path_to_folder));
    foreach ($fls as $value){
    //foreach ($array_uc_files as $value){
      $sfp = $full_path_to_folder.'/'.$value; 
      $sfn = explode(".",$value)[0]; 
      //define cf output
      $ex_out_cf = $full_path_to_folder.'/'.$sfn.'_cf.html';
      //define acdd output
      $ex_out_acdd = $full_path_to_folder.'/'.$sfn.'_acdd.html';
      //define nc_to_mmd output
      $ex_out_nctommd = $full_path_to_folder.'/'.$sfn.'.xml';
      exec('compliance-checker -v -c lenient --format=html --output='.$ex_out_cf.' --test=cf:1.6 '.$sfp, $out, $status1);
      exec('compliance-checker -v -c lenient --format=html --output='.$ex_out_acdd.' --test=acdd '.$sfp, $out, $status2);
      $status = $status1 + $status2;  // status = 0 passed
      $ex_out = file_get_contents($ex_out_cf).file_get_contents($ex_out_acdd);
 
      //decide what to do according to the compliance checker exit status.
      if($status !==0){
        if (sizeof($array_uc_files) > 1) {
          //do not show the checker output for the upload module. The user has to go back to the validation link and see the output there. 
          form_set_error('single_file','Your dataset '.$fname.' contains at least one file not following the netCDF/CF-1.6 and ACDD-1.3 standards. Your submission can not proceed. <br> 
          Check your dataset and correct the errors before making a new submission.');
        } else {
          //do not show the checker output for the upload module. The user has to go back to the validation link and see the output there. 
          form_set_error('compressed_file','Your dataset '.$value.' is not following the netCDF/CF-1.6 and ACDD-1.3 standards. <br>
                          Your submission can not proceed. <br> 
          Check your dataset and correct the errors before making a new submission.');
       }
        
       //drupal_set_message('<pre>'.print_r($array_uc_files,TRUE).'</pre>','warning');
       if(!empty($array_uc_files)){
         foreach ($array_uc_files as $vv){
           file_delete($vv);
         }
       }
       //drupal_set_message('<pre>'.print_r($array_uc_files,TRUE).'</pre>','warning');

       //if the cf-checker fails then give message and remove submission file also from DB. 
       //dataset_upload_multipage_form_clear_p1($form, $form_state);
       file_unmanaged_delete_recursive($form_state['dataset_dir']);

      }else{
        drupal_set_message(t("Your dataset ".$value." is compliant with CF and ACDD standards. The submission can now proceed."), 'status');
        exec('/usr/local/bin/nc_to_mmd.pl --output='.$ex_out_nctommd.' '.$sfp, $out_nctommd, $status_nctommd);
        //get xml file content
        $xml_content = file_get_contents($ex_out_nctommd); // this is a string from gettype 
        //get xml object iterator 
        $xml = new SimpleXmlIterator($xml_content); // problem with boolean
        //$xml = simplexml_load_file($xml_content):
        //get xml object iterator with mmd namespaces
        $xml_wns = $xml->children($xml->getNamespaces(true)['mmd']);
      
        $form_state['metadata'][$value] = depth_mmd("", $xml_wns);
      } 
      //remove validation files 
      if(file_exists($ex_out_cf)){
        file_unmanaged_delete($ex_out_cf);
      }
      if(file_exists($ex_out_acdd)){
        file_unmanaged_delete($ex_out_acdd);
      }
    }
  } else {
    form_set_error('form','No files have been uploaded.');
  }
}

function dataset_upload_multipage_p2_validate($form, &$form_state) {
  //only validates page2
  if (isset($form_state['storage']['page_three'])) {
    return;
  }

  if ($form_state['values']['aggregation'] === '') {
    form_set_error('','No aggregation variable specified');
    return;
  }

  //create string with list of files which are input to the agg_checker.py
  $files_to_agg = '';
  foreach ($form_state['unzipped_files'] as $unzippedfiles) {
    $files_to_agg .= $form_state['dataset_fullpath'].'/'.$unzippedfiles->filename.' ';
  }

  //check dimensions, variables names and attributes to allow for aggregation
  exec('/usr/local/bin/agg_checker.py '.$files_to_agg.' '.$form_state['values']['aggregation'],$out_agg, $status_agg);

  $fail_agg = False;
  $msg_agg = array();
  //build the message with only the Fail prints from the agg_checker.py
  foreach ($out_agg as $line) {
    if (strpos($line, 'Fail') !== false) {
      $fail_agg = True;
      array_push($msg_agg,$line);
    }
  }

  // agg_checker.py exit with status 0, but gives Fail messages, i.e. the datasets are not suitable for aggregation
  if($fail_agg == True) {
    form_set_error('form','Your datasets cannot be aggregated. Check suggestions below:<br>'.print_r(implode('<br>',$msg_agg),TRUE));
  }
  // agg_checker.py exit with status not 0, i.e. it could not be run. 
  if($status_agg !== 0){
    form_set_error('form','The aggregation validation checker could not be run. Please take contact using the contact form.');
  }
}
