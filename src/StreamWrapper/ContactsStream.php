<?php

namespace Drupal\contacts\StreamWrapper;

use Drupal\Core\StreamWrapper\LocalReadOnlyStream;

/**
 * Defines a Contacts (contacts://) stream wrapper class.
 *
 * Provides support for accessing Contact's files with the Drupal file
 * interface.
 *
 * @todo: Remove if https://www.drupal.org/node/1308152 lands.
 */
class ContactsStream extends LocalReadOnlyStream {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t("Contact's files");
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t("Contact's files served by the webserver.");
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    return drupal_get_path('module', 'contacts');
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());
    return $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'contacts') . '/' . $path;
  }

}
