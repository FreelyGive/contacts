<?php

namespace Drupal\Tests\contacts\Functional;

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
   * Test installing contacts and accessing the contact dashboard.
   */
  public function testViewDashboard() {
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

    // Check our expected users are listed.
    // @todo: Expand this as we get a proper listing in.
    $session->elementTextContains('css', '.views-row:nth-child(1) .views-field-uid .field-content', 1);
    $session->elementTextContains('css', '.views-row:nth-child(1) .views-field-name .field-content', 'admin');
    $session->elementTextContains('css', '.views-row:nth-child(2) .views-field-uid .field-content', 2);
    $session->elementTextContains('css', '.views-row:nth-child(2) .views-field-name .field-content', $this->adminUser->getAccountName());
  }

}
