<?php

namespace Drupal\contacts_demo\Plugin\DevelGenerate;

use Drupal\contacts_demo\CrmGeneratorBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

/**
 * Generate individuals using the randomuser.me API.
 *
 * @DevelGenerate(
 *   id = "crm_indiv",
 *   label = @Translation("Contacts (Individuals)"),
 *   description = @Translation("Generate a given number of individual contacts. Optionally delete current contacts."),
 *   url = "contacts-indiv",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 50,
 *     "delete" = FALSE
 *   }
 * )
 */
class CrmIndivGenerate extends CrmGeneratorBase {

  /**
   * {@inheritdoc}
   */
  const ROLE_NAME = 'crm_indiv';

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['copyright'] = array(
      '#markup' => $this->t('Individuals are generated using data provided by <a href=":url" target=_"blank">randomuser.me</a>.', [
        ':url' => 'https://randomuser.me',
      ]),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function generateContacts($number = 1, array $options = array()) {
    // Set some default options.
    $options += [
      'api' => [
        'nat' => 'gb',
      ]
    ];

    // Enforce a couple API options.
    $options['api']['results'] = $number;
    $options['api'][] = 'noinfo';

    // Retrieve our results.
    $results = $this->httpClientFactory->fromOptions()->request('GET', 'https://randomuser.me/api', [
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

  /**
   * Create the individual profile for the result row.
   *
   * @param \stdClass $result
   *   The result row to generate for.
   * @param \Drupal\user\UserInterface $user
   *   The user account to attach the profile to.
   * @param array $options
   *   The options for the generation.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The generated profile.
   */
  protected function createProfileIndiv(\stdClass $result, UserInterface $user, $options = array()) {
    $options += [
      'photo' => -10,
    ];

    $values = [
      'crm_name' => ucwords("{$result->name->title} {$result->name->first} {$result->name->last}"),
      'crm_gender' => $result->gender,
      'crm_email' => $result->email,
      'crm_dob' => substr($result->dob, 0, 10),
      'crm_address' => [
        'country_code' => 'GB',
        'address_line1' => $result->location->street,
        'locality' => $result->location->city,
        'postal_code' => $result->location->postcode,
      ],
    ];

    // Generate our photo if required.
    if ($this->getRandomDecision($options['photo'])) {
      $photo = file_save_data(file_get_contents($result->picture->large));
      $values['crm_photo'] = $photo->id();
    }

    return $this->createProfile('crm_indiv', $values, $user);
  }

  /**
   * Create the notes profile for the result row.
   *
   * @param \stdClass $result
   *   The result row to generate for.
   * @param \Drupal\user\UserInterface $user
   *   The user account to attach the profile to.
   * @param array $options
   *   The options for the generation.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The generated profile.
   */
  protected function createProfileNotes(\stdClass $result, UserInterface $user, $options = array()) {
    $options += [
      'notes' => 3,
      'notes_content' => 2,
    ];

    if (!$this->getRandomDecision($options['notes'])) {
      return NULL;
    }

    $values = [
      'crm_notes' => $this->getRandomDecision($options['notes_content']) ? "{$result->id->name}: {$result->id->value}" : '',
    ];

    return $this->createProfile('crm_indiv', $values, $user);
  }

}
