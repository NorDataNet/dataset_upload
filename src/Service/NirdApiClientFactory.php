<?php

namespace Drupal\dataset_upload\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Http\ClientFactory;

/**
 * Class NirdApiClientFactory.
 *
 * @package Drupal\dataset_upload\Service
 */
class NirdApiClientFactory {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * Guzzle client factory.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  private $httpClientFactory;

  /**
   * Json serializer.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  private $json;

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   * @param \Drupal\Component\Serialization\Json $json
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientFactory $http_client_factory, Json $json) {
    $this->configFactory = $config_factory;
    $this->httpClientFactory = $http_client_factory;
    $this->json = $json;
  }

  /**
   * Create a new fully prepared instance of NirdApiClient.
   *
   * @return \Drupal\dataset_upload\Service\NirdApiClient
   */
  public function create() {
    $config = $this->configFactory->get('dataset_upload.settings');
    $http_client = $this->httpClientFactory->fromOptions();
    // $this->httpClientFactory->setBaseUrl($config->get('nird_api_base_uri'));
    // $http_client = $this->httpClientFactory;
    return new NirdApiClient($config, $http_client, $this->json);
  }

}
