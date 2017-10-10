<?php

namespace Drupal\contacts;

use Drupal\contacts\Entity\ContactTabInterface;
use Drupal\user\UserInterface;

/**
 * Interface for the contacts tab manager.
 */
interface ContactsTabManagerInterface {

  /**
   * Get a specific tab.
   *
   * @param \Drupal\user\UserInterface $contact
   *   The contact.
   * @param string $id
   *   The tab id.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The tab, if it exists.
   */
  public function getTab(UserInterface $contact, $id);

  /**
   * Get a specific tab from a path.
   *
   * @param \Drupal\user\UserInterface $contact
   *   The contact.
   * @param string $path
   *   The path for the tab.
   *
   * @return \Drupal\contacts\Entity\ContactTab|null
   *   The tab, if it exists.
   */
  public function getTabByPath(UserInterface $contact, $path);

  /**
   * Get the tabs for a contact.
   *
   * @param \Drupal\user\UserInterface $contact
   *   The contact.
   *
   * @return \Drupal\contacts\Entity\ContactTabInterface[]
   *   The tabs, keyed by ID and sorted by weight.
   */
  public function getTabs(UserInterface $contact);

  /**
   * Get the block plugins for a tab.
   *
   * @param \Drupal\contacts\Entity\ContactTabInterface $tab
   *   The tab entity to retrieve the blocks for.
   * @param \Drupal\user\UserInterface|null $contact
   *   (Optional) The contact we are retrieving the tab for. Required if we are
   *   to verify the tab.
   * @param bool $verify
   *   Whether to verify the tab. This may require the contact for context.
   *   Defaults to TRUE.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface[]|false
   *   The build render array or FALSE if we cannot build it.
   */
  public function getBlocks(ContactTabInterface $tab, UserInterface $contact = NULL, $verify = TRUE);

  /**
   * Verify that a tab is valid for the given contact.
   *
   * @param \Drupal\contacts\Entity\ContactTabInterface $tab
   *   The tab.
   * @param \Drupal\user\UserInterface $contact
   *   The contact.
   *
   * @return bool
   *   Whether the tab is valid.
   */
  public function verifyTab(ContactTabInterface $tab, UserInterface $contact);

}
