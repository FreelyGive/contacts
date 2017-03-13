<?php

namespace Drupal\contacts_demo;

use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Generate individuals using the randomuser.me API.
 */
class RandomUser {

  /**
   * Drupal\Core\Http\ClientFactory definition.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(ClientFactory $http_client_factory, EntityTypeManager $entity_type_manager) {
    $this->httpClientFactory = $http_client_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  public function generateUsers($number = 1, $options = array()) {
    // Set some default options.
    $options += [
      'photos' => 10,
      'api' => [
        'nat' => 'gb',
      ]
    ];

    // Enforce a couple API options.
    $options['api']['results'] = $number;
    $options['api'][] = 'noinfo';

    // Retrieve our results.
    $results = $this->httpClientFactory->fromOptions()->request('https://randomuser.me/api', [
      'query' => $options['api'],
    ]);
    $results = json_decode($results);

    // Loop over and create the users and associated entities.
    foreach ($results->results as $result) {
      $user = $this->createUser($result, $options);
      $this->createProfileIndiv($result, $user, $options);
      $this->createProfileNotes($result, $user, $options);
    }
  }

  protected function createUser(\stdClass $result, $options = array()) {
    // Generate some reasonable timestamps.
    $created = strtotime($result->registered);
    $login = $created + rand(0, time() - $created);
    $access = $login + rand(0, 86400);
    $changed = $access + rand(0, time() - $access);

    $user_values = [
      'mail' => $result->email,
      'status' => 1,
      'created' => $created,
      'changed' => $changed,
      'roles' => array('crm_indiv'),
    ];

    // If we're doing a coupled user, add the additional info.
    if ($this->getRandomDecision($options['coupled']) ?? 2) {
      $user_values += [
        'name' => $result->login->username,
        'pass' => $result->login->password,
        'access' => $access,
        'login' => $login,
      ];
    }

    $user = $this->entityTypeManager->getStorage('user')->create($user_values);
    $user->save();
    return $user;
  }

  /**
   * Make a randomized decision with a given likelihood.
   *
   * @param bool|int $option
   *   Either a boolean indicating we should always return the same answer, or
   *   in integer indicating the likelihood (e.g. 1 in $option will be TRUE).
   *   Negative numbers reverse the result (e.g. 1 in $option will be FALSE).
   *
   * @return bool
   *   The random answer.
   *
   * @throws \Exception
   *   Thrown if the option is invalid.
   */
  protected function getRandomDecision($option) {
    // Check option is valid.
    if (!is_bool($option) && (!is_int($option) || $option === 0)) {
      throw new \Exception(strtr('Invalid argument "@option" for @method.', [
        '@option' => $option,
        '@method' => __METHOD__,
      ]));
    }

    // If it's a boolean, we simply return it.
    if (is_bool($option)) {
      return $option;
    }

    // Otherwise, generate a random number between 0 and it - 1.
    $rand = rand(0, abs($option - 1));

    // If our original option was positive, we want to return whether it's zero.
    // Otherwise return if it's non zero.
    return $rand === 0 xor $option < 0;
  }
}
