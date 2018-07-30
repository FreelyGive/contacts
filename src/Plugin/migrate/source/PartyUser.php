<?php

namespace Drupal\contacts\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\user\Plugin\migrate\source\d7\User as D7User;

/**
 * Source plugin for party entities.
 *
 * @MigrateSource(
 *   id = "party_user",
 *   source_module = "user"
 * )
 */
class PartyUser extends D7User {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    parent::prepareRow($row);

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('party', 'p')
      ->fields('p', [
        'pid',
        'label',
      ])
      ->fields('u', [
        'uid',
        'pass',
        'signature',
        'signature_format',
        'created',
        'access',
        'login',
        'status',
        'timezone',
        'language',
        'picture',
        'init',
        'data',
      ]);
    $query->addField('p', 'email', 'party_email');
    $query->addField('u', 'name', 'd7_username');

    $query->leftJoin(
      'party_attached_entity',
      'pae',
      'p.pid = pae.pid AND pae.entity_type = :entity', [':entity' => 'user']
    );

    $query->leftJoin('users', 'u', 'pae.eid = u.uid');
    $query->condition('p.pid', 1, '>');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'pid' => $this->t('Party ID from D7'),
      'label' => $this->t('Party label from D7'),
      'party_email' => $this->t('Party email from D7'),
      'd7_username' => $this->t('Username (only if a party has this user entity attached to it'),
      'uid' => $this->t('User ID'),
      'pass' => $this->t('Password'),
      'signature' => $this->t('Signature'),
      'signature_format' => $this->t('Signature format'),
      'created' => $this->t('Registered timestamp'),
      'access' => $this->t('Last access timestamp'),
      'login' => $this->t('Last login timestamp'),
      'status' => $this->t('Status'),
      'timezone' => $this->t('Timezone'),
      'language' => $this->t('Language'),
      'picture' => $this->t('Picture'),
      'init' => $this->t('Init'),
      'data' => $this->t('User data'),
      'roles' => $this->t('Roles'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'pid' => [
        'type' => 'integer',
        'alias' => 'p',
      ],
    ];
  }

}
