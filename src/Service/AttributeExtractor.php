<?php

namespace Drupal\dataset_upload\Service;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class AttributeExtractor
 *
 * @package Drupal\dataset_upload\Service
 */
class AttributeExtractor
{
    /**
     * Private state variables
     */

    //Status message
    private $message;



    /**
     * List containing required global attributes
     */
    protected $global_attributes = [
      'title',
      'summary',
      'publisher_url',
      'publisher_name',
      'publisher_type',
      'publisher_email',
      'creator_name',
      'creator_type',
      'creator_role',
      'creator_email',
      'creator_url',
      'creator_institution',
      'contributor_name',
      'contributor_type',
      'contributor_role',
      'contributor_email',
      'contributor_url',
      'date_created',
      'id',
      'institution',
      'institution_short_name'
    ];

    /**
     * Use agg_checker.py to check if datasets can be aggregated.
     *
     * @param string $filename
     *
     * @return array $attributes
     */

    public function extractAttributes(string $filename, string $filepath)
    {

      /** TODO: Use ncdump and get xml back. Extract the global attributes we
       * need for NIRD API using SimpleXML
       */
        //Array to store the extracted metadata
        $metadata = [];

        $bin_path = '';
        $out = null;
        $status = null;
        //\Drupal::logger('dataset_upload_ncdump_status')->debug($bin_path .'ncdump -hx '. $filepath .'/'. $filename);
        exec($bin_path .'ncdump -hx '. $filepath .'/'. $filename, $xml_out, $status);

        //\Drupal::logger('dataset_upload_ncdump_status')->debug($status);

        //\Drupal::logger('dataset_upload_ncdump_xml')->debug('<pre><code>' . print_r($xml_out, TRUE) . '</code></pre>');

        if ($status === 0) {
            $xml = new \SimpleXmlIterator(implode('', $xml_out));
            foreach ($xml->attribute as $att) {
                $attrib = $att['name'];
                $value = $att['value'];

                //Extract the data from values and add to metadata array
                //Validate attrib against global attributes
                if (in_array($attrib, $this->global_attributes)) {
                    $test = 'Attribute: ' . $attrib. ', value: ' . $value;
                    $metadata[(string) $attrib] = (string) $value;
                    //\Drupal::logger('dataset_upload_attribute_extract')->debug($test);
                }
            }
            //\Drupal::logger('dataset_upload_metadata')->debug('<pre><code>' . print_r($metadata, TRUE) . '</code></pre>');

            return $metadata;
        }


        return $out;
    }

    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get the local global attributes array
     *
     * @return array $global_attributes
     */
    public function getGlobalAttributes()
    {
        return $global_attributes;
    }
}
