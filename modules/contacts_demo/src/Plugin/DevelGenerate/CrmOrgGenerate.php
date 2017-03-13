<?php

namespace Drupal\contacts_demo\Plugin\DevelGenerate;

use Drupal\contacts_demo\CrmGeneratorBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use GuzzleHttp\Client;

/**
 * Generate individuals using the randomuser.me API.
 *
 * @DevelGenerate(
 *   id = "crm_org",
 *   label = @Translation("Contacts (Organisations)"),
 *   description = @Translation("Generate a given number of organisation contacts. Optionally delete current contacts."),
 *   url = "contacts-org",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 50,
 *     "delete" = FALSE
 *   }
 * )
 */
class CrmOrgGenerate extends CrmGeneratorBase {

  /**
   * {@inheritdoc}
   */
  const ROLE_NAME = 'crm_org';

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['copyright'] = array(
      '#markup' => $this->t('Organisation are generated using data provided by <a href=":data-url" target=_"blank">www.mockaroo.com</a> with images from <a href=":image-url" target=_"blank">www.flamingtext.co.uk</a>.', [
        ':data-url' => 'http://www.mockaroo.com/',
        ':image-url' => 'http://www.flamingtext.co.uk/',
      ]),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function generateContacts($number = 1, array $options = array()) {
    // Organisations never have accounts.
    $options['coupled'] = FALSE;

    // Retrieve our results.
    $client = $this->httpClientFactory->fromOptions();
    $results = $client->request('GET', 'https://www.mockaroo.com/dddbdc80/download?count=10&key=ee1f5f90', [
      'query' => [
        'count' => $number,
        'key' => 'ee1f5f90',
      ],
    ]);
    $results = json_decode($results->getBody());

    // Loop over and create the users and associated entities.
    foreach ($results as $result) {
      $user = $this->createUser($result, $options);
      $this->createProfileOrg($result, $user, $options);
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
  protected function createProfileOrg(\stdClass $result, UserInterface $user, $options = array()) {
    $options += [
      'logo' => -10,
    ];

    $values = [
      'crm_org_name' => $result->name,
      'crm_org_email' => $result->email,
      'crm_org_address' => [
        'country_code' => 'GB',
        'address_line1' => ucwords($result->location->street),
        'locality' => ucwords($result->location->city),
        'postal_code' => strtoupper($result->location->postcode),
      ],
      'crm_phone' => '07000 ' . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT),
    ];

    // Generate our photo if required.
    if ($this->getRandomDecision($options['logo'])) {
      $designs = ['minions', 'comics', 'blackbird'];
      $query = [
        'script' => $designs[rand(0, count($designs) - 1)] . '-logo',
        '_loc' => 'generate',
        'imageoutput' => 'true',
        'fillTextType' => 0,
        'fillTextColor' => '#' . dechex(rand(0, 15)) . dechex(rand(0, 15)) . dechex(rand(0, 15)) . dechex(rand(0, 15)) . dechex(rand(0, 15)) . dechex(rand(0, 15)),
        'text' => $result->name,
      ];
      $url = 'http://www.flamingtext.co.uk/net-fu/proxy_form.cgi?' . http_build_query($query);

      $photo = file_save_data(file_get_contents($url));
      $values['crm_logo'] = $photo->id();
    }

    return $this->createProfile('crm_org', $values, $user);
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
      'crm_notes' => $this->getRandomDecision($options['notes_content']) ? $result->slogan : '',
    ];

    return $this->createProfile('crm_notes', $values, $user);
  }

}
