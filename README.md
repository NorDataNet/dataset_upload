# Dataset upload with validation and NIRD integration

## Description
This module provides integration between NorDataNet (www.nordatanet.no) and NIRD/AUS
for publishing datasets to the NIRD Archive.

### Dependencies
The following dependencies are needed to be installed on the computer running this module.
* netCDF-tools (ncdump)
* rclone (minIO)



### Form endpoints
* /admin/config/dataset_upload
  - Configuration form for the module
* /dataset_upload/form
  - The dataset upload and registration form

### Services
This module implements a few services, that can be used by other modules. The servies are:
* dataset_upload.nird_api_client_factory
  - Factory service for creating the NirdApiClient.
* dataset_upload.nird_api_client
  - The NIRD API client for communicating with the NIRD API.
* dataset_upload.aggregation_checker
  - Service for checking if netCDF-files can be aggregated.
* dataset_upload.attribute_extractor
  - Service for extracting ACDD attributes from netCDF-files using ncdump.
* dataset_upload.minio_service
  - Service for uploading the registered dataset(s) to the NIRD archive using rclone (minIO).


### Queues
This module implements two Drupal QueueWorkers that are used to process the uploading of datasets, and emailing the contributors when a dataset have been published.
* nird_upload_queue
  - Queue for uploading the registered dataset(s) to the NIRD Archive, using minIO.
* nird_email_queue
  - Queue for checking that the uploaded dataset(s) have been published. If the dataset is published, it will send an email to the depositor user. If not, postpone.

### Drush commands.
This module implements one Drush command for processing the uploaded datasets.
* nird:process
  - process the nird_email_queue. NOT IN USE AS OF now

### CRON.
The two drupal queues needs to be executed by CRON every x minutes.
This is an example crontab.

`*/5 * * * * /usr/bin/php /var/www/staging/vendor/drush/drush/drush queue:run nird_upload_queue --root=/var/www/staging --uri=metsis-staging.met.no > /tmp/nird_upload.log 2>&1`

`*/5 * * * * /usr/bin/php /var/www/staging/vendor/drush/drush/drush queue:run nird_email_queue --root=/var/www/staging --uri=metsis-staging.met.no > /tmp/nird_email.log 2>&1
`
### TODO

## Authors

 [@magnarem](https://github.com/magnarem) - (magnarem@met.no)
