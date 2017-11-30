<?php

namespace Drupal\contacts\Plugin;

/**
 * Defines an interface for blocks placed on the Contacts Dashboard.
 */
interface DashboardBlockInterface {

  /**
   * A string to indicate that edit link should be rendered in title of block.
   */
  const EDIT_LINK_TITLE = 'title';

  /**
   * A string to indicate that edit link should be rendered in content of block.
   */
  const EDIT_LINK_CONTENT = 'content';

  /**
   * Get the edit link, if applicable.
   *
   * @param string $mode
   *   The mode for adding the edit link.
   *
   * @return \Drupal\Core\Link|false
   *   The edit link, or FALSE if there is none.
   */
  public function getEditLink($mode);

}
