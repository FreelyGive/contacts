<?php

namespace Drupal\contacts\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests CRM fields and views.
 *
 * @group contacts
 */
class ContactsDashboardTest extends WebTestBase {

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
   * Function to get information about users from the Admin People view.
   *
   * @return mixed
   *   An associative array of user information or FALSE if not users are found.
   */
  protected function testViewDashboard() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/contacts');
    $this->assertResponse(200);

    $this->assertRaw('Contacts');
  }

}
