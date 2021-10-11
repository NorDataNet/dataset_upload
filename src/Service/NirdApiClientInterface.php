<?php

namespace Drupal\dataset_upload\Service;

/**
 * Interface NirdApiClientInterfce.
 *
 * @package Drupal\dataset_upload\Service
 *
 * TODO: Implement all methods availablie in the NIRD API. (some missing)
 *
 */
interface NirdApiClientInterface {


  /**
   * Athenticate against NIRD API, and get authentication token..
   *
   * @param string $grant_type
   * @param string $username
   * @param string $password
   * @param string $scope
   * @param string $client_id
   * @param string $client_secret
   *

   */
  public function getToken(
    string $grant_type = '',
    string $username,
    string $password,
    string $scope = '',
    string $client_id = '',
    string $client_secret = ''
  );

  /**
   * Get the State controlled vocabulary from NIRD API.
   *
   * @return array
   */
  public function getState(): array;

  /**
   * Get the Category controlled vocabulary from NIRD API.
   *
   * @return array
   */
  public function getCategory(): array;


  /**
   * Get the Subject controlled vocabulary from NIRD API.
   *
   * @param string $domain
   * @param string $field
   * @param string $subfield
   *
   * @return array
   */
  public function getSubject(string $domain = '', string $field = '', string $subfield = ''): array;

  /**
   * Get the Licence controlled vocabulary from NIRD API.
   *
   * @param string $name
   *
   * @return array
   */
  public function getLicence(string $name = ''): array;

  /**
   * Create dataset using NIRD API.
   *
   * @param array $json
   *
   * @return array
   */
  public function createDataset(array $dataset): array;

}