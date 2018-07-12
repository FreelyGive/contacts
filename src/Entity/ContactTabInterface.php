<?php

namespace Drupal\contacts\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Contact tab entities.
 */
interface ContactTabInterface extends ConfigEntityInterface {

  /**
   * Get the final path part for this tab.
   *
   * @return string
   *   The path for the tab.
   */
  public function getPath();

  /**
   * Set the final path part for this tab.
   *
   * @param string $path
   *   The path for the tab.
   *
   * @return $this
   */
  public function setPath($path);

  /**
   * Get the layout id for this tab.
   *
   * @return string
   *   The layout for the tab.
   */
  public function getLayout();

  /**
   * Set the layout id for this tab.
   *
   * @param string $layout
   *   The layout for the tab.
   *
   * @return $this
   */
  public function setLayout($layout);

  /**
   * Get the relationship definitions.
   *
   * @return array
   *   An array of the relationships:
   *   - id: The relationship plugin id.
   *   - name: The name for the context.
   *   - source: The name of the source context.
   */
  public function getRelationships();

  /**
   * Set the relationships definitions.
   *
   * @param array $relationships
   *   An array of the relationships:
   *   - id: The relationship plugin id.
   *   - name: The name for the context.
   *   - source: The name of the source context.
   *
   * @return $this
   */
  public function setRelationships(array $relationships);

  /**
   * Get the block settings.
   *
   * @param string $id
   *   The id of the block to get.
   *
   * @return array
   *   An array of the block configuration, including:
   *   - id: The block plugin id.
   *   - context_mapping: Any relevant context mapping.
   *   - ...: Other block config.
   */
  public function getBlock($id);

  /**
   * Get the block settings for all blocks.
   *
   * @return array
   *   An array of the block configurations.
   */
  public function getBlocks();

  /**
   * Set the block settings for all blocks.
   *
   * @param array $blocks
   *   The block configurations.
   *
   * @return $this
   */
  public function setBlocks(array $blocks);

  /**
   * Set the block settings.
   *
   * @param string $name
   *   The machine readable name of the block to set.
   * @param array $configuration
   *   The block configuration. Must contain at least the id.
   *
   * @return $this
   */
  public function setBlock($name, array $configuration);

  /**
   * Get all the block plugins for the tab.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface[]|null
   *   The block plugins or NULL if it's not been set.
   */
  public function getBlockPlugins();

  /**
   * Store all the block plugins for the tab.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface[] $blocks
   *   The block plugins.
   *
   * @return $this
   */
  public function setBlockPlugins(array $blocks);

  /**
   * Build metadata about the tab.
   *
   * @return array
   *   Render array of metadata.
   */
  public function getManageMeta();

}
