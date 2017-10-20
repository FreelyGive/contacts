<?php

namespace Drupal\contacts\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Contact tab entity.
 *
 * @ConfigEntityType(
 *   id = "contact_tab",
 *   label = @Translation("Contact tab"),
 *   handlers = {
 *     "list_builder" = "Drupal\contacts\ContactTabListBuilder",
 *     "form" = {
 *       "add" = "Drupal\contacts\Form\ContactTabForm",
 *       "edit" = "Drupal\contacts\Form\ContactTabForm",
 *       "delete" = "Drupal\contacts\Form\ContactTabDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "contact_tab",
 *   admin_permission = "administer contacts",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/contact-tabs/{contact_tab}",
 *     "add-form" = "/admin/structure/contact-tabs/add",
 *     "edit-form" = "/admin/structure/contact-tabs/{contact_tab}/edit",
 *     "delete-form" = "/admin/structure/contact-tabs/{contact_tab}/delete",
 *     "collection" = "/admin/structure/contact-tabs"
 *   }
 * )
 */
class ContactTab extends ConfigEntityBase implements ContactTabInterface {

  /**
   * The tab ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The tab label.
   *
   * @var string
   */
  protected $label;

  /**
   * The tab path part.
   *
   * @var string
   */
  protected $path;

  /**
   * The contexts in which to show the tab.
   *
   * @var string[]
   */
  protected $contexts = [];

  /**
   * The relationships for the tab.
   *
   * An array including:
   *   - id: The relationship plugin id.
   *   - name: The name for the context.
   *   - source: The name of the source context.
   *
   * @var array
   */
  protected $relationships = [];

  /**
   * The hats required for the tab.
   *
   * @var string[]
   */
  protected $hats = [];

  /**
   * The block configuration.
   *
   * An array including:
   *   - id: The block plugin id.
   *   - context_mapping: Any relevant context mapping.
   *
   * @var array
   */
  protected $blocks = [];

  /**
   * The block plugins for this tab.
   *
   * @var \Drupal\Core\Block\BlockPluginInterface[]
   */
  protected $blockPlugins = [];

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($path) {
    $this->path = $path;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationships() {
    return $this->relationships;
  }

  /**
   * {@inheritdoc}
   */
  public function setRelationships(array $relationships) {
    $this->relationships = $relationships;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHats() {
    return $this->hats;
  }

  /**
   * {@inheritdoc}
   */
  public function setHats(array $hats) {
    $this->hats = $hats;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlock($id) {
    if (isset($this->blocks[$id])) {
      return $this->blocks[$id];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlocks() {
    return $this->blocks;
  }

  /**
   * {@inheritdoc}
   */
  public function setBlock($name, array $block) {
    // Make sure we have an actual id and not just a numeric array key.
    if (empty($block['id']) || is_numeric($block['id'])) {
      throw new \InvalidArgumentException('Missing required ID for block settings.');
    }

    // If there is a problem with the name we can try to create one from ID.
    if (empty($name) || is_numeric($name)) {
      $name = preg_replace("/[^A-Za-z0-9 ]/", '_', $block['id']);
    }

    // Make sure the name is set properly.
    $block['name'] = $name;
    $this->blocks[$name] = $block;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBlocks(array $blocks) {
    $this->blocks = [];
    foreach ($blocks as $key => $block) {
      $name = $block['name'] ?: $key;
      $this->setBlock($name, $block);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockPlugins() {
    return $this->blockPlugins;
  }

  /**
   * {@inheritdoc}
   */
  public function setBlockPlugins(array $blocks) {
    $this->blockPlugins = $blocks;
    return $this;
  }

  /**
   * Load a tab by path.
   *
   * @param string $path
   *   The path to load by.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   The entity for the given path, or FALSE if none is found.
   */
  public static function loadByPath($path) {
    $entity_type_id = \Drupal::service('entity_type.repository')->getEntityTypeFromClass(get_called_class());
    $tabs = \Drupal::entityTypeManager()->getStorage($entity_type_id)->loadByProperties([
      'path' => $path,
    ]);
    return reset($tabs);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    $configs = $this->getBlocks();
    foreach ($configs as $config) {
      $block = \Drupal::service('plugin.manager.block')->createInstance($config['id'], $config);
      $this->calculatePluginDependencies($block);
    }

    return $this;
  }

}
