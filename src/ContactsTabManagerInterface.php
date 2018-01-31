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
   * @param string $id
   *   The tab id.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The tab, if it exists.
   */
  public function getTab($id);

  /**
   * Get a specific tab from a path.
   *
   * @param string $path
   *   The path for the tab.
   *
   * @return \Drupal\contacts\Entity\ContactTab|null
   *   The tab, if it exists.
   */
  public function getTabByPath($path);

  /**
   * Get the tabs for a contact.
   *
   * @return \Drupal\contacts\Entity\ContactTabInterface[]
   *   The tabs, keyed by ID and sorted by weight.
   */
  public function getTabs();

  /**
   * Get the block plugins for a tab.
   *
   * @param \Drupal\contacts\Entity\ContactTabInterface $tab
   *   The tab entity to retrieve the blocks for.
   * @param bool $apply_context
   *   Whether to apply context mappings to the block plugins. This require the
   *   contact for context. Defaults to TRUE.
   * @param \Drupal\user\UserInterface|null $contact
   *   (Optional) The contact we are retrieving the tab for. Required if we are
   *   applying block contexts.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface[]
   *   The tab block plugins array.
   */
  public function getBlocks(ContactTabInterface $tab, $apply_context = FALSE, UserInterface $contact = NULL);

  /**
   * Verify that a tab is valid for the given contact.
   *
   * @param \Drupal\contacts\Entity\ContactTabInterface $tab
   *   The tab.
   * @param \Drupal\user\UserInterface| $contact
   *   The contact to verify against.
   *
   * @return bool
   *   Whether the tab is valid.
   */
  public function verifyTab(ContactTabInterface $tab, UserInterface $contact);

}
