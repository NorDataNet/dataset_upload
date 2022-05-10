<?php

namespace Drupal\dataset_upload\Service;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Client;
use Drupal\Core\Config\ImmutableConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NirdApiClient
 *
 * @package Drupal\dataset_upload\Service
 */
class NirdApiClient implements NirdApiClientInterface
{
    /**
     * Config factory.
     *
     * @var \Drupal\Core\Config\ImmutableConfig
     */
    private $config;

    /**
     * Prepared instance of http client.
     *
     * @var \GuzzleHttp\Client
     */
    private $httpClient;

    /**
     * Json serializer.
     *
     * @var \Drupal\Component\Serialization\Json
     */
    private $json;

    /**
     * NIRD API Token
     *
     * @var string
     */
    private $token;

    /**
     * NIRD API Token type
     *
     * @var string
     */
    private $token_type;

    /**
     * cache age
     *
     * @var int
     */
    protected $cache_time; //Hold cache time
    //protected $cache_time = Cache::PERMANENT; //Permanent caching


    /**
     * NirdApiClient constructor.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface
     * @param \GuzzleHttp\Client $http_client
     * @param \Drupal\Component\Serialization\Json $json
     */
    public function __construct(ImmutableConfig $configFactory, Client $http_client, Json $json)
    {
        $this->config = $configFactory;
        $this->httpClient = $http_client;
        $this->cache_time = time() + (14*24*60*60); //Cache for 14-days
        $this->json = $json;
    }

    /**
      * {@inheritdoc}
      */
    public static function create(ContainerInterface $container)
    {
        $this->httpClient = $container->get('http_client');

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getToken(
        $grant_type = '',
        $username,
        $password,
        $scope = '',
        $client_id = '',
        $client_secret = ''
    ) {
        $response = $this->httpClient->post(
        //$response = \Drupal::httpClient()->post(
          //$endpoint,
        $this->config->get('nird_api_token_endpoint'),
            [
       'form_params' => [
           'grant_type' => $grant_type,
           'username' => $username,
           'password' => $password,
          // 'scope' => $scope,
           'client_id' => $client_id,
           'client_secret' => $client_secret,
       ],
       'base_uri' => $this->config->get('nird_api_base_uri'),
       'debug' => false,
       'headers' => [
          'Content-Type' => 'application/x-www-form-urlencoded',
          'Accept' => 'application/json',
       ],

      ]
        );

        if ($response->getStatusCode() === 200) {
            $contents = $this->json::decode($response->getBody()->getContents());
            //Store tokens in client class for accesability
            $this->token = $contents['access_token'];
            $this->token_type = $contents['token_type'];
            //Add the authentication token to the httpclient default header for future requests by this client

            //$this->httpClient = $this->httpClient->fromOptions($options);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getState(): array
    {
        if (empty($this->token)) {
            $user = $this->config->get('nird_username');
            $pass = $this->config->get('nird_password');
            self::getToken(
                '',
                $user,
                $pass,
                '',
                '',
                ''
            );
        }
        /**
         * Call NIRD API to prefetch controlled vocabularies
         * We cache those lists to reduce API calls
         * The time is specified by cache time
         */

        if ($cache = \Drupal::cache()->get('nird_state')) {
            $options = $cache->data;
            return $options;
        }

        $response = $this->httpClient->get(
            $this->config->get('nird_api_state_endpoint'),
            [
          'base_uri' => $this->config->get('nird_api_base_uri'),
          'headers' => [
            'Authorization' => "{$this->token_type} {$this->token}",
            'Content-Type' => "application/json",
            'Accept' => 'application/json',
          ],
        ],
        );

        if ($response->getStatusCode() === 200) {
            $contents = $this->json::decode($response->getBody()->getContents());
            $states = $this->json::decode($contents);
            \Drupal::cache()->set('nird_state', $states['state'], $this->cache_time);
            return $states['state'];
        }

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getCategory(): array
    {
        if (empty($this->token)) {
            $user = $this->config->get('nird_username');
            $pass = $this->config->get('nird_password');
            self::getToken(
                '',
                $user,
                $pass,
                '',
                '',
                ''
            );
        }
        if ($cache = \Drupal::cache()->get('nird_category')) {
            $options = $cache->data;
            return $options;
        }

        $response = $this->httpClient->get(
            $this->config->get('nird_api_category_endpoint'),
            [
          'base_uri' => $this->config->get('nird_api_base_uri'),
          'headers' => [
            'Authorization' => "{$this->token_type} {$this->token}",
            'Content-Type' => "application/json",
            'Accept' => 'application/json',
          ],
        ],
        );

        if ($response->getStatusCode() === 200) {
            $contents = $this->json::decode($response->getBody()->getContents());
            \Drupal::cache()->set('nird_category', $this->json::decode($contents)['category'], $this->cache_time);

            return $this->json::decode($contents)['category'];
        }

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getSubject(string $domain = '', string $field = '', string $subfield = ''): array
    {
        if (empty($this->token)) {
            $user = $this->config->get('nird_username');
            $pass = $this->config->get('nird_password');
            self::getToken(
                '',
                $user,
                $pass,
                '',
                '',
                ''
            );
        }
        if ($cache = \Drupal::cache()->get('nird_subject')) {
            $options = $cache->data;
            return $options;
        }

        $response = $this->httpClient->get(
            $this->config->get('nird_api_subject_endpoint'),
            [
          'base_uri' => $this->config->get('nird_api_base_uri'),
          'query' => [
            'domain' => $domain,
            'field' => $field,
            'subfield' => $subfield,
          ],
          'headers' => [
            'Authorization' => "{$this->token_type} {$this->token}",
            'Content-Type' => "application/json",
            'Accept' => 'application/json',
          ],
        ],
        );

        if ($response->getStatusCode() === 200) {
            $contents = $this->json::decode($response->getBody()->getContents());
            \Drupal::cache()->set('nird_subject', $this->json::decode($contents)['identifiers'], $this->cache_time);
            return $this->json::decode($contents)['identifiers'];
        }

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getDomain(): array
    {
        if (empty($this->token)) {
            $user = $this->config->get('nird_username');
            $pass = $this->config->get('nird_password');
            self::getToken(
                '',
                $user,
                $pass,
                '',
                '',
                ''
            );
        }

        $response = $this->httpClient->get(
            $this->config->get('nird_api_domain_endpoint'),
            [
          'base_uri' => $this->config->get('nird_api_base_uri'),
          'headers' => [
            'Authorization' => "{$this->token_type} {$this->token}",
            'Content-Type' => "application/json",
            'Accept' => 'application/json',
          ],
        ],
        );

        if ($response->getStatusCode() === 200) {
            $contents = $this->json::decode($response->getBody()->getContents());
            return $this->json::decode($contents)['domains'];
        }

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getField(string $domain = ''): array
    {
        if (empty($this->token)) {
            $user = $this->config->get('nird_username');
            $pass = $this->config->get('nird_password');
            self::getToken(
                '',
                $user,
                $pass,
                '',
                '',
                ''
            );
        }

        $response = $this->httpClient->get(
            $this->config->get('nird_api_field_endpoint'),
            [
          'base_uri' => $this->config->get('nird_api_base_uri'),
          'query' => [
            'domain' => $domain,
          ],
          'headers' => [
            'Authorization' => "{$this->token_type} {$this->token}",
            'Content-Type' => "application/json",
            'Accept' => 'application/json',
          ],
        ],
        );

        if ($response->getStatusCode() === 200) {
            $contents = $this->json::decode($response->getBody()->getContents());
            return $this->json::decode($contents)['fields'];
        }

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getSubField(string $domain = '', string $field = ''): array
    {
        if (empty($this->token)) {
            $user = $this->config->get('nird_username');
            $pass = $this->config->get('nird_password');
            self::getToken(
                '',
                $user,
                $pass,
                '',
                '',
                ''
            );
        }

        $response = $this->httpClient->get(
            $this->config->get('nird_api_subfield_endpoint'),
            [
          'base_uri' => $this->config->get('nird_api_base_uri'),
          'query' => [
            'domain' => $domain,
            'field' => $field,
          ],
          'headers' => [
            'Authorization' => "{$this->token_type} {$this->token}",
            'Content-Type' => "application/json",
            'Accept' => 'application/json',
          ],
        ],
        );

        if ($response->getStatusCode() === 200) {
            $contents = $this->json::decode($response->getBody()->getContents());
            return $this->json::decode($contents)['subfields'];
        }

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getLicence(string $name = ''): array
    {
        if (empty($this->token)) {
            $user = $this->config->get('nird_username');
            $pass = $this->config->get('nird_password');
            self::getToken(
                '',
                $user,
                $pass,
                '',
                '',
                ''
            );
        }
        if ($cache = \Drupal::cache()->get('nird_license')) {
            $options = $cache->data;
            return $options;
        }

        $response = $this->httpClient->get(
            $this->config->get('nird_api_license_endpoint'),
            [
        'base_uri' => $this->config->get('nird_api_base_uri'),
        'query' => [
          'name' => $name,
        ],
        'headers' => [
          'Authorization' => "{$this->token_type} {$this->token}",
          'Content-Type' => "application/json",
          'Accept' => 'application/json',
        ],
      ],
        );


        if ($response->getStatusCode() === 200) {
            $contents = $this->json::decode($response->getBody()->getContents());
            \Drupal::cache()->set('nird_license', $contents['licences'], $this->cache_time);
            return $contents['licences'];
        }

        return [];
    }


    /**
     * {@inheritDoc}
     */

    public function createDataset(array $dataset): array
    {
        if (empty($this->token)) {
            $user = $this->config->get('nird_username');
            $pass = $this->config->get('nird_password');
            self::getToken(
                '',
                $user,
                $pass,
                '',
                '',
                ''
            );
        }
        $json = $this->json::encode($dataset);

        //var_dump($json);
        $endpoint = $this->config->get('nird_api_dataset_endpoint');
        try {
            $response = $this->httpClient->post(
                $this->config->get('nird_api_dataset_endpoint'),
                [
        'base_uri' => $this->config->get('nird_api_base_uri'),
        'json' => $dataset,
        'http_erros' => false,
        'headers' => [
          'Authorization' => "{$this->token_type} {$this->token}",
          'Content-Type' => "application/json",
          'Accept' => 'application/json',
        ],
      ]
            );

            if ($response->getStatusCode() === 200) {
                $contents = $this->json::decode($response->getBody()->getContents());
                return $contents;
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // you can catch here 400 response errors and 500 response errors
            // You can either use logs here use Illuminate\Support\Facades\Log;
            $error['error'] = $e->getMessage();
            $error['request'] = $e->getRequest();
            return $error;
            \Drupal::logger('dataset_upload')->error('Error occurred in get request.', ['error' => $error]);
        } catch (Exception $e) {
            $error['error'] = $e->getMessage();
            $error['request'] = $e->getRequest();
            return $error;
        }



        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function findPerson(
        string $firstname = '',
        string $lastname = '',
        string $email = '',
        string $federatedid = ''
    ): array {
        if (empty($this->token)) {
            $user = $this->config->get('nird_username');
            $pass = $this->config->get('nird_password');
            self::getToken(
                '',
                $user,
                $pass,
                '',
                '',
                ''
            );
        }

        $response = $this->httpClient->get(
            $this->config->get('nird_api_person_endpoint'),
            [
          'base_uri' => $this->config->get('nird_api_base_uri'),
          'query' => [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'federatedid' => $federatedid,
          ],
          'headers' => [
            'Authorization' => "{$this->token_type} {$this->token}",
            'Content-Type' => "application/json",
            'Accept' => 'application/json',
          ],
        ],
        );
        //dpm($response->getStatusCode());
        if ($response->getStatusCode() === 200) {
            $contents = $this->json::decode($response->getBody()->getContents());
            //$msg = $this->json::decode($contents);
            //dpm($contents);
            return $contents;
        }

        return [];
    }

    public function findOrganization(
        string $longname = '',
        string $shortname = '',
        string $contactemail = '',
        string $homepage = ''
    ): array {
        if (empty($this->token)) {
            $user = $this->config->get('nird_username');
            $pass = $this->config->get('nird_password');
            self::getToken(
                '',
                $user,
                $pass,
                '',
                '',
                ''
            );
        }

        $response = $this->httpClient->get(
            $this->config->get('nird_api_organization_endpoint'),
            [
          'base_uri' => $this->config->get('nird_api_base_uri'),
          'query' => [
            'longname' => $longname,
            'shortname' => $shortname,
            'contactemail' => $contactemail,
            'homepage' => $homepage,
          ],
          'headers' => [
            'Authorization' => "{$this->token_type} {$this->token}",
            'Content-Type' => "application/json",
            'Accept' => 'application/json',
          ],
        ],
        );
        //dpm($response->getStatusCode());
        if ($response->getStatusCode() === 200) {
            $contents = $this->json::decode($response->getBody()->getContents());
            //$msg = $this->json::decode($contents);
            return $contents;
        }

        return [];
    }


    /**
     * {@inheritDoc}
     */
    public function createPerson(
        string $firstname = '',
        string $lastname = '',
        string $email = '',
        string $federatedid = ''
    ): array {
        if (empty($this->token)) {
            $user = $this->config->get('nird_username');
            $pass = $this->config->get('nird_password');
            self::getToken(
                '',
                $user,
                $pass,
                '',
                '',
                ''
            );
        }

        $response = $this->httpClient->post(
            $this->config->get('nird_api_person_endpoint'),
            [
            'base_uri' => $this->config->get('nird_api_base_uri'),
            'json' =>
            [
              'firstname' => $firstname,
              'lastname' => $lastname,
              'email' => $email,
              'federatedid' => $federatedid,
            ],
            'http_erros' => false,
            'headers' => [
              'Authorization' => "{$this->token_type} {$this->token}",
              'Content-Type' => "application/json",
              'Accept' => 'application/json',
            ],
          ],
        );
        //dpm($response->getStatusCode());
        if ($response->getStatusCode() === 200) {
            $contents = $this->json::decode($response->getBody()->getContents());
            //$msg = $this->json::decode($contents);
            //dpm($contents);
            return $contents;
        }

        return [];
    }

    public function createOrganization(
        array $json = []
    ): array {
        if (empty($this->token)) {
            $user = $this->config->get('nird_username');
            $pass = $this->config->get('nird_password');
            self::getToken(
                '',
                $user,
                $pass,
                '',
                '',
                ''
            );
        }

        $response = $this->httpClient->post(
            $this->config->get('nird_api_organization_endpoint'),
            [
            'base_uri' => $this->config->get('nird_api_base_uri'),
            'json' => $json,
            'http_erros' => false,
            /*[
              'longname' => $longname,
              'shortname' => $shortname,
              'contactemail' => $contactemail,
              'homepage' => $homepage,
            ],*/
            'headers' => [
              'Authorization' => "{$this->token_type} {$this->token}",
              'Content-Type' => "application/json",
              'Accept' => 'application/json',
            ],
          ],
        );
        //dpm($response->getStatusCode());
        if ($response->getStatusCode() === 200) {
            $contents = $this->json::decode($response->getBody()->getContents());
            //$msg = $this->json::decode($contents);
            return $contents;
        }

        return [];
    }

    public function getDatasetStatus(string $dataset_id = ''): array
    {
        if (empty($this->token)) {
            $user = $this->config->get('nird_username');
            $pass = $this->config->get('nird_password');
            self::getToken(
                '',
                $user,
                $pass,
                '',
                '',
                ''
            );
        }

        $response = $this->httpClient->get(
            $this->config->get('nird_api_dataset_status_endpoint').$dataset_id,
            [
              'base_uri' => $this->config->get('nird_api_base_uri'),
            //  'query' => [
            //    'dataset_id' => $dataset_id,
            //  ],
              'headers' => [
                'Authorization' => "{$this->token_type} {$this->token}",
                'Content-Type' => "application/json",
                'Accept' => 'application/json',
              ],
            ],
        );

        if ($response->getStatusCode() === 200) {
            $contents = $this->json::decode($response->getBody()->getContents());
            //return $this->json::decode($contents);
            return $contents;
        }

        return [];
    }

    public function getDatasetLandingPage(string $doi = ''): array
    {
        if (empty($this->token)) {
            $user = $this->config->get('nird_username');
            $pass = $this->config->get('nird_password');
            self::getToken(
                '',
                $user,
                $pass,
                '',
                '',
                ''
            );
        }

        $response = $this->httpClient->get(
            $this->config->get('nird_api_dataset_landing_page_endpoint'),
            [
              'base_uri' => $this->config->get('nird_api_base_uri'),
              'query' => [
                'dataset_id' => $doi,
              ],
              'headers' => [
                'Authorization' => "{$this->token_type} {$this->token}",
                'Content-Type' => "application/json",
                'Accept' => 'application/json',
              ],
            ],
        );

        if ($response->getStatusCode() === 200) {
            $contents = $this->json::decode($response->getBody()->getContents());
            return $this->json::decode($contents);
        }

        return [];
    }

    public function searchOrganization(string $query = ''): array
    {
        if (empty($this->token)) {
            $user = $this->config->get('nird_username');
            $pass = $this->config->get('nird_password');
            self::getToken(
                '',
                $user,
                $pass,
                '',
                '',
                ''
            );
        }

        $response = $this->httpClient->get(
            $this->config->get('nird_api_search_org_endpoint'),
            [
              'base_uri' => $this->config->get('nird_api_base_uri'),
              'query' => [
                'dataset_id' => $doi,
              ],
              'headers' => [
                'Authorization' => "{$this->token_type} {$this->token}",
                'Content-Type' => "application/json",
                'Accept' => 'application/json',
              ],
            ],
        );

        if ($response->getStatusCode() === 200) {
            $contents = $this->json::decode($response->getBody()->getContents());
            return $this->json::decode($contents);
        }

        return [];
    }
}
