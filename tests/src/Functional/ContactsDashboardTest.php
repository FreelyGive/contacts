<?php

namespace Drupal\Tests\contacts\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests CRM fields and views.
 *
 * @group contacts
 */
class ContactsDashboardTest extends BrowserTestBase {

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

    \Drupal::service('theme_installer')->install(['contacts_theme']);
    drupal_flush_all_caches();
  }

  /**
   * Function to get information about users from the Admin People view.
   *
   * @return mixed
   *   An associative array of user information or FALSE if not users are found.
   */
  public function testViewDashboard() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/contacts');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->responseContains('Contacts');
  }

}
