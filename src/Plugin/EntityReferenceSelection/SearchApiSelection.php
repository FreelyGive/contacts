<?php

namespace Drupal\contacts\Plugin\EntityReferenceSelection;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "search_api",
 *   label = @Translation("Contact selection"),
 *   group = "search_api",
 *   weight = 2
 * )
 */
class SearchApiSelection extends PluginBase implements SelectionInterface, ConfigurablePluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new selection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'index' => NULL,
      'conditions' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // Merge in defaults.
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $storage = $this->entityTypeManager->getStorage('search_api_index');
    $indexes = [];
    foreach ($storage->loadMultiple() as $index) {
      /* @var \Drupal\search_api\IndexInterface $index */
      foreach ($index->getDatasources() as $datasource) {
        // Include this index if it includes the right entity type.
        if ($datasource->getEntityTypeId() == $this->configuration['target_type']) {
          $indexes[$index->id()] = $index->label();
          // Continue to the next index, we don't need to look at more sources.
          break;
        }
      }
    }

    $form['index'] = [
      '#type' => 'select',
      '#title' => $this->t('Index'),
      '#description' => $this->t('Select the index to search.'),
      '#required' => TRUE,
      '#options' => $indexes,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {
    throw new \Exception('Entity queries are not supported for Search API Selection handler.');
  }

  /**
   * Get the base query for finding matches.
   *
   * @param string|null $match
   *   The string to match, or NULL for any.
   * @param string $match_operator
   *   The match operator.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   The base query.
   */
  protected function getQuery($match = NULL, $match_operator = 'CONTAINS') {
    /* @var \Drupal\search_api\IndexInterface $index */
    $index = $this->entityTypeManager
      ->getStorage('search_api_index')
      ->load($this->configuration['index']);

    $query = $index->query();
    if (isset($match)) {
      $query->keys($match);
    }

    $query->sort('search_api_relevance', 'DESC');

    // Filter on data sources that return the correct entity type.
    $datasources = [];
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() == $this->configuration['target_type']) {
        $datasources[] = $datasource->getPluginId();
      }
    }
    $query->addCondition('search_api_datasource', $datasources, 'IN');

    // Add any additional conditions.
    foreach ($this->configuration['conditions'] as $condition) {
      $query->addCondition(...$condition);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $query = $this->getQuery($match, $match_operator);
    if ($limit) {
      $query->range(0, $limit);
    }

    $results = $query->execute();

    if ($results->getResultCount() == 0) {
      return [];
    }

    $options = [];
    foreach ($results->getResultItems() as $result) {
      /* @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $result->getOriginalObject()->getValue();
      $options[$entity->bundle()][$entity->id()] = Html::escape($this->entityTypeManager->getTranslationFromContext($entity)->label());
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {
    $query = $this->getQuery($match, $match_operator);
    $query->range(0, 0);
    $result = $query->execute();
    return $result->getResultCount();
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    $result = [];
    if ($ids) {
      $target_type = $this->configuration['target_type'];
      $entity_type = $this->entityTypeManager->getDefinition($target_type);
      $query = $this->entityTypeManager->getStorage($target_type)->getQuery();
      $result = $query
        ->condition($entity_type->getKey('id'), $ids, 'IN')
        ->execute();
    }

    return $result;
  }

}
