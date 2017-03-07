<?php

namespace Drupal\contacts_demo;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\profile\ProfileStorageInterface;
use Drupal\user\RoleStorageInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Base class for generating contacts.
 */
abstract class CrmGeneratorBase extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * The role name we are generating. Must be overriden by implementing classes.
   */
  const ROLE_NAME = NULL;

  /**
   * Drupal\Core\Http\ClientFactory definition.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The profile storage.
   *
   * @var \Drupal\profile\ProfileStorageInterface
   */
  protected $profileStorage;

  /**
   * {@inheritdoc}
   *
   * @param $http_client_factory \Drupal\Core\Http\ClientFactory
   *   The HTTP Client Factory.
   * @param $user_storage \Drupal\user\UserStorageInterface
   *   The user storage.
   * @param $profile_storage \Drupal\profile\ProfileStorageInterface
   *   The profile storage.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ClientFactory $http_client_factory, UserStorageInterface $user_storage, ProfileStorageInterface $profile_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->httpClientFactory = $http_client_factory;
    $this->userStorage = $user_storage;
    $this->profileStorage = $profile_storage;

    if (empty(static::ROLE_NAME)) {
      throw new \Exception(strtr('Role name not defined for @class', [
        '@class' => __CLASS__,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('http_client_factory'),
      $entity_type_manager->getStorage('user'),
      $entity_type_manager->getStorage('profile')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $roles = user_role_names(TRUE);

    $form['num'] = array(
      '#type' => 'number',
      '#title' => $this->t('How many contacts would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#min' => 0,
    );

    $form['delete'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Delete all @type contacts (except user id 1) before generating new users.', [
        '@type' => $roles[static::ROLE_NAME],
      ]),
      '#default_value' => $this->getSetting('delete'),
    );

    $options = $roles;
    unset($options[AccountInterface::AUTHENTICATED_ROLE]);
    unset($options['crm_indiv']);
    unset($options['crm_org']);
    $form['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Which roles should the users receive?'),
      '#description' => $this->t('Users always receive the <em>authenticated user</em> roles.'),
      '#options' => $options,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    $num = $values['num'];
    $delete = $values['delete'];
    $options = [
      'roles' => array_filter($values['roles']),
    ];
    $options['roles'][] = static::ROLE_NAME;

    if ($delete) {
      $this->deleteContacts(static::ROLE_NAME);
    }

    if ($num > 0) {
      $this->generateContacts($num, $options);
    }

    $this->setMessage($this->t('@num created.', array('@num' => $this->formatPlural($num, '1 contact', '@count contacts'))));
  }

  /**
   * Delete contacts of a given role.
   *
   * @param string $role
   *   The role to delete users for.
   */
  public function deleteContacts($role) {
    $uids = $this->userStorage->getQuery()
      ->condition('uid', 1, '>')
      ->condition('roles', $role)
      ->execute();
    $users = $this->userStorage->loadMultiple($uids);
    $this->userStorage->delete($users);

    $this->setMessage($this->formatPlural(count($uids), '1 contact deleted', '@count contacts deleted.'));
  }

  /**
   * Generate a specified number of individuals based on the given options.
   *
   * @param int $number
   *   The number of contacts to create.
   * @param array $options
   *   Options for the generation.
   */
  abstract public function generateContacts($number = 1, array $options = array());

  /**
   * Create the user for the result row.
   *
   * @param \stdClass $result
   *   The result row to generate for.
   * @param array $options
   *   The options for the generation.
   *
   * @return \Drupal\user\UserInterface
   *   The generated user.
   */
  protected function createUser(\stdClass $result, array $options = array()) {
    $options += [
      'coupled' => 2,
      'roles' => [],
    ];
    $options['roles'][] = static::ROLE_NAME;
    $options['roles'] = array_filter($options['roles']);

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
      'roles' => $options['roles'],
    ];

    // If we're doing a coupled user, add the additional info.
    if ($this->getRandomDecision($options['coupled'])) {
      $user_values += [
        'name' => $result->login->username,
        'pass' => $result->login->password,
        'access' => $access,
        'login' => $login,
      ];
    }

    /* @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->create($user_values);
    $user->save();
    return $user;
  }

  protected function createProfile($type, array $values, UserInterface $user) {
    $values['type'] = $type;
    $values['uid'] = $user->id();
    $values += [
      'status' => 1,
      'is_default' => 1,
      'created' => $user->getCreatedTime(),
      'changed' => $user->getChangedTime(),
    ];

    /* @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->profileStorage->create($values);
    $profile->save();
    return $profile;
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

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams($args) {
    $values = array(
      'num' => array_shift($args),
      'roles' => drush_get_option('roles') ? explode(',', drush_get_option('roles')) : array(),
      'delete' => drush_get_option('delete'),
    );
    return $values;
  }

}
