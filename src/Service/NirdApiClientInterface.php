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
   * Get the Domain controlled vocabulary from NIRD API.
   *
   *
   * @return array
   */
  public function getDomain(): array;


  /**
   * Get the Field controlled vocabulary from NIRD API.
   *
   * @param string $domain
   *
   * @return array
   */
  public function getField(string $domain = ''): array;


  /**
   * Get the Subject controlled vocabulary from NIRD API.
   *
   * @param string $domain
   * @param string $field
   * @param string $subfield
   *
   * @return array
   */
  public function getSubField(string $domain = '', string $field = ''): array;



  /**
   * Get the Licence controlled vocabulary from NIRD API.
   *
   * @param string $name
   *
   * @return array
   */
  public function getLicence(string $name = ''): array;



  /**
   * Find person quering the NIRD API.
   *
   * @param string $firstname
   * @param string $lastname
   * @param string $email
   * @param string $federatedid
   *
   * @return array
   */
  public function findPerson(
    string $firstname = '',
    string $lastname = '',
    string $email = '',
    string $federatedid = ''): array;


    /**
     * Find organization quering the NIRD API.
     *
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @param string $federatedid
     *
     * @return array
     */
    public function findOrganization(
      string $longname = '',
      string $shortname = '',
      string $contactemail = '',
      string $homepage = ''): array;


      /**
       * Create person quering the NIRD API.
       *
       * @param string $firstname
       * @param string $lastname
       * @param string $email
       * @param string $federatedid
       *
       * @return array
       */
      public function createPerson(
        string $firstname = '',
        string $lastname = '',
        string $email = '',
        string $federatedid = ''): array;


        /**
         * Create organization quering the NIRD API.
         *
         * @param string $firstname
         * @param string $lastname
         * @param string $email
         * @param string $federatedid
         *
         * @return array
         */
        public function createOrganization(
          array $json = []): array;


  /**
   * Create dataset using NIRD API.
   *
   * @param array $json
   *
   * @return array
   */
  public function createDataset(array $dataset): array;

}
