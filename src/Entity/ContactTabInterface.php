<?php

namespace Drupal\contacts\Entity;

use Drupal\Core\Block\BlockPluginInterface;
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
   * @return array
   *   An array of the block configuration, including:
   *   - id: The block plugin id.
   *   - context_mapping: Any relevant context mapping.
   *   - ...: Other block config.
   */
  public function getBlock();

  /**
   * Set the block settings.
   *
   * @param array $configuration
   *   The block configuration. Must contain at least the id.
   *
   * @return $this
   */
  public function setBlock(array $configuration);

  /**
   * Get the block plugin for the tab.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface|null
   *   The block plugin or NULL if it's not been set.
   */
  public function getBlockPlugin();

  /**
   * Store the block plugin for the tab.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   The block plugin.
   *
   * @return $this
   */
  public function setBlockPlugin(BlockPluginInterface $block);

}
