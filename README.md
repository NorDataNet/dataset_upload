# dataset_upload
This module allows a user to upload files to a directory as: 
1) single netCDF
2) single compressed file, which must include only netCDF files
3) multiple netCDF files

A dataset_upload_folder will be created into the public files directory. 


Requirements:
=============
For allowing file upload the /etc/phpX/apache2/php.ini should be changed allowing post_max_size to be 500M and filesize to be 200M 


