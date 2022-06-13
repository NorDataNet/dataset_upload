#!/bin/bash
SOURCE=/var/www/drupal9/files/metsis-dev.local/private/nird/toArchive
DEST=import/test-magnar
find $SOURCE/*  -maxdepth 0 -mmin -15 -type d -exec basename {} \; | while IFS= read -r FILE; do
    echo "Copying $FILE.."
    rclone copy $SOURCE/$FILE minio:$DEST/$FILE
    rclone check $SOURCE/$FILE minio:$DEST/$FILE
done
