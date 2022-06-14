# Dataset upload with validation and NIRD integration

## Description
This module provides integration between NorDataNet (www.nordatanet.no) and NIRD/AUS
for publishing datasets to the NIRD Archive.

### Dependencies
The following dependencies are needed to be installed on the computer running this module.
* netCDF-tools (ncdump)
* rclone (for minIO)



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

### TODO

## Authors

 [@magnarem](magnarem@met.no)(https://github.com/magnarem)
