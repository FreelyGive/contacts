<?php

namespace Drupal\contacts_demo;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DemoFlavour extends PluginBase implements DemoFlavourInterface {

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManger;


  /**
   * The Demo Content Manager.
   *
   * @var \Drupal\contacts_demo\DemoContentManager
   */
  protected $demoContentManager;

  /**
   * Constructs a Migration.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager.
   * @param \Drupal\contacts_demo\DemoContentManager $demo_content_manager
   *   The Demo Content Manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, DemoContentManager $demo_content_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManger = $entity_type_manager;
    $this->demoContentManager = $demo_content_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.demo_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return (string) $this->pluginDefinition['description'];
  }
  
  /**
   * {@inheritdoc}
   */
  public function getModule() {
    $definition = $this->getPluginDefinition();
    return isset($definition['provider']) ? $definition['provider'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFiles() {
    $definition = $this->getPluginDefinition();

    $files = [];
    foreach ($definition['files'] as $type => $paths) {
      $files[] = [
        'type' => $type,
        'paths' => $paths,
      ];
    }
    return $files;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    // @todo count total entities in manifest.
    return 10;
  }

  /**
   * {@inheritdoc}
   */
  public function generate() {
    $this->generateElements();
    drupal_set_message('Generate process complete.');
  }
  
  /**
   * {@inheritdoc}
   */
  protected function generateElements() {
    if ($this->count() <= 20) {
      $this->generateContent();
    }
    else {
      $this->generateBatchContent();
    }
  }

  /**
   * Method responsible for creating content when
   * the number of elements is less than 50.
   */
  private function generateContent() {
    $num_created = 0;

    foreach ($this->getFiles() as $file) {

      $content_type = $file['type'];
      $configuration = [
        'source' => $file['paths'],
      ];

      $plugin = $this->demoContentManager->createInstance($content_type, $configuration);

      /** @var \Drupal\contacts_demo\DemoContentInterface $plugin */
      $plugin->createContent();

      if ($file['type'] == 'user') {
        $num_created += $plugin->count();
      }
    }

    // Only care about number of users created.
    drupal_set_message($this->formatPlural($num_created, '1 users created.', 'Finished creating @count users'));
  }

  /**
   * Method responsible for creating content when
   * the number of elements is greater than 50.
   */
  private function generateBatchContent() {
    // @TODO do batch operations.
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    foreach ($this->getFiles() as $file) {
      $content_type = $file['type'];
      $configuration = [
        'source' => $file['paths'],
      ];

      /** @var \Drupal\contacts_demo\DemoContentInterface $plugin */
      $plugin = $this->demoContentManager->createInstance($content_type, $configuration);
      $plugin->removeContent();
    }
    drupal_set_message('Delete process complete.');
  }

}
