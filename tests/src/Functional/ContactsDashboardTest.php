<?php

namespace Drupal\Tests\contacts\Functional;

use Drupal\decoupled_auth\Entity\DecoupledAuthUser;
use Drupal\file\Entity\File;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests CRM fields and views.
 *
 * @group contacts
 */
class ContactsDashboardTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'contacts', 'views'];

  /**
   * Testing admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->createUser([], NULL, TRUE);
    // @TODO update page permission requirements.
    $this->adminUser->addRole('administrator');
    $this->adminUser->save();
  }

  /**
   * Create a contact of the given type.
   *
   * @param string $type
   *   The type of contact, either 'crm_indiv' or 'crm_org'.
   * @param bool $decoupled
   *   Whether the user should be decoupled. Defaults to TRUE.
   *
   * @return \Drupal\decoupled_auth\Entity\DecoupledAuthUser
   *   The contact that was created.
   */
  protected function createContact($type, $decoupled = TRUE) {
    // Create our user.
    $name = $this->randomMachineName();
    $contact = DecoupledAuthUser::create([
      'name' => $decoupled ? NULL : $name,
      'mail' => $name . '@example.com',
      'status' => 1,
    ]);
    $contact->addRole($type);
    $contact->save();

    // Generate a random image.
    $filesystem = $this->container->get('file_system');
    $tmp_file = $filesystem->tempnam('temporary://', 'contactImage_');
    $destination = $tmp_file . '.jpg';
    file_unmanaged_move($tmp_file, $destination, FILE_CREATE_DIRECTORY);
    $path = $this->getRandomGenerator()->image($filesystem->realpath($destination), '100x100', '100x100');
    $image = File::create();
    $image->setFileUri($path);
    $image->setOwnerId($contact->id());
    $image->setMimeType($this->container->get('file.mime_type.guesser')->guess($path));
    $image->setFileName($filesystem->basename($path));
    $destination = 'public://contactImage_' . $contact->id();
    $file = file_move($image, $destination, FILE_CREATE_DIRECTORY);

    // Build our profile.
    switch ($type) {
      case 'crm_indiv':
        $values = [
          'type' => 'crm_indiv',
          'crm_name' => $this->randomString(20),
          'crm_gender' => 'female',
          'crm_email' => $contact->getEmail(),
          'crm_address' => [
            'country_code' => 'GB',
            'locality' => $this->randomString(),
          ],
          'crm_photo' => $file->id(),
        ];
        break;

      case 'crm_org':
        $values = [
          'type' => 'crm_org',
          'crm_org_name' => $this->randomString(20),
          'crm_org_email' => $contact->getEmail(),
          'crm_org_address' => [
            'country_code' => 'GB',
            'locality' => $this->randomString(),
          ],
          'crm_logo' => $file->id(),
        ];
        break;

      default:
        return $contact;
    }
    $values += [
      'uid' => $contact->id(),
      'status' => 1,
      'is_default' => 1,
    ];
    $profile = Profile::create($values);
    $profile->save();

    // @todo: Remove when onUpdate is added.
    $contact->updateProfileFields([$type]);
    return $contact;
  }

  /**
   * Test installing contacts and accessing the contact dashboard.
   */
  public function testViewDashboard() {
    // Create some same users.
    $contacts[] = DecoupledAuthUser::load(1);
    $contacts[] = $this->adminUser;
    $contacts[] = $this->createContact('crm_indiv');
    $contacts[] = $this->createContact('crm_indiv', FALSE);
    $contacts[] = $this->createContact('crm_org');

    // Check the site has installed successfully.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);

    // Check that prior to logging in, we can't access the contacts dashboard.
    $this->drupalGet('admin/contacts');
    $this->assertSession()->statusCodeEquals(403);

    // Gain access to the contacts dashboard.
    $this->drupalLogin($this->adminUser);

    // Make sure our items are indexed.
    /* @var \Drupal\search_api\IndexInterface $index */
    $index = \Drupal::entityTypeManager()
      ->getStorage('search_api_index')
      ->load('contacts_index');
    $index->indexItems();

    // Check that the contacts dashboard has the expected content.
    $this->drupalGet('admin/contacts');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->elementTextContains('css', '.page-title', 'Contacts');

    // Sort our contacts.
    usort($contacts, function($a, $b) {
      $a_indiv = $a->hasRole('crm_indiv');
      $b_indiv = $b->hasRole('crm_indiv');

      // Deal with the scenario when one or more is not an individual as the
      // sort is currently on an individual field.
      if (!$a_indiv && !$b_indiv) {
        return 0;
      }
      elseif (!$a_indiv) {
        return -1;
      }
      elseif (!$b_indiv) {
        return 1;
      }

      // Otherwise we use strnatcmp() on the name.
      $a_name = $a->profile_crm_indiv->entity->crm_name->value;
      $b_name = $b->profile_crm_indiv->entity->crm_name->value;
      return strnatcmp($a_name, $b_name);
    });

    // Check our expected users are listed.
    $index = 1;
    foreach ($contacts as $contact) {
      // Gather our relevant values.
      $values = [];

      $roles = user_role_names();
      $values['roles'] = implode(', ', array_intersect_key($roles, array_fill_keys($contact->getRoles(), TRUE)));

      if ($contact->hasRole('crm_indiv')) {
        /* @var \Drupal\profile\Entity\ProfileInterface $profile */
        $profile = $contact->profile_crm_indiv->entity;
        $values['label'] = $profile->crm_name->value;
        $values['city'] = $profile->crm_address->locality;
        $values['image'] = $profile->crm_photo->entity->getFileUri() ?: FALSE;
      }
      elseif ($contact->hasRole('crm_org')) {
        /* @var \Drupal\profile\Entity\ProfileInterface $profile */
        $profile = $contact->profile_crm_org->entity;
        $values['label'] = $profile->crm_org_name->value;
        $values['city'] = $profile->crm_org_address->locality;
        $values['image'] = $profile->crm_logo->entity->getFileUri() ?: FALSE;
      }
      else {
        $values['label'] = $contact->getDisplayName();
        $values['city'] = FALSE;
        $values['image'] = FALSE;
      }

      // Check our row is correctly rendered.
      $base_selector = ".views-row:nth-child({$index}) ";

      if ($values['image']) {
        $session->elementAttributeContains('css', $base_selector . '.contacts-row-image a', 'href', 'admin/contacts/' . $contact->id());
        $session->elementAttributeContains('css', $base_selector . '.contacts-row-image img', 'src', 'contactImage_' . $contact->id());
      }
      else {
        $session->elementNotExists('css', $base_selector . '.contacts-row-image a');
        $session->elementNotExists('css', $base_selector . '.contacts-row-image img');
      }

      if ($values['label']) {
        $session->elementAttributeContains('css', $base_selector . '.contacts-row-main h3.contact-label a', 'href', 'admin/contacts/' . $contact->id());
        $session->elementTextContains('css', $base_selector . '.contacts-row-main h3.contact-label a', $values['label']);
      }
      else {
        $session->elementNotExists('css', $base_selector . '.contacts-row-main h3.contact-label');
      }

      $session->elementAttributeContains('css', $base_selector . '.contacts-row-supporting h6.contact-id a', 'href', 'admin/contacts/' . $contact->id());
      $session->elementTextContains('css', $base_selector . '.contacts-row-supporting h6.contact-id a', 'ID: ' . $contact->id());
      $element = $session->elementExists('css', $base_selector . '.contacts-row-supporting .contact-roles');

      if ($values['roles']) {
        $session->elementTextContains('css', $base_selector . '.contacts-row-supporting .contact-roles', $values['roles']);
      }
      else {
        $session->elementNotExists('css', $base_selector . '.contacts-row-supporting .contact-roles');
      }

      $index++;
    }
  }

}
