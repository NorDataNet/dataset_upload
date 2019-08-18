# dataset_upload
This module allows a user to upload files to a directory as: 
1) single netCDF
2) single compressed file, which must include only netCDF files

A dataset_upload_folder will be created into the public files directory according to the 
name give in the configuration page


Requirements:
=============
1) For allowing file upload the /etc/phpX/apache2/php.ini should be changed allowing post_max_size to be 500M and filesize to be 200M 
2) Install php-zip to allow for .zip upload
3) Install agg_checker.py in /usr/local/bin/
4) Install compliance-checker in /usr/local/bin/
5) Install nc_to_mmd.pl in /usr/local/bin/
