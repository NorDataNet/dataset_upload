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

      ]);

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
            self::getToken('',
            $user,
            $pass,
            '',
            '',
            ''
        );
        }

        $response = $this->httpClient->get(
          $this->config->get('nird_api_state_endpoint'), [
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
            self::getToken('',
            $user,
            $pass,
            '',
            '',
            ''
        );
        }

        $response = $this->httpClient->get(
          $this->config->get('nird_api_category_endpoint'), [
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
            //dpm($contents);
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
            self::getToken('',
            $user,
            $pass,
            '',
            '',
            ''
        );
        }

        $response = $this->httpClient->get(
          $this->config->get('nird_api_subject_endpoint'), [
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
            return $this->json::decode($contents)['identifiers'];
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
            self::getToken('',
            $user,
            $pass,
            '',
            '',
            ''
        );
        }
        $response = $this->httpClient->get(
          $this->config->get('nird_api_license_endpoint'), [
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
            self::getToken('',
            $user,
            $pass,
            '',
            '',
            ''
        );
        }
        $json = $this->json::encode($dataset);

        var_dump($json);
        $endpoint = $this->config->get('nird_api_dataset_endpoint');
        $response = $this->httpClient->post(
        $this->config->get('nird_api_dataset_endpoint'), [
        'base_uri' => $this->config->get('nird_api_base_uri'),
        'json' => $dataset,
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

        return [];
    }
}