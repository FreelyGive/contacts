<?php

namespace Drupal\contacts;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\contacts\Entity\ContactTabInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Block\BlockManager;
use Drupal\ctools\Plugin\RelationshipManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The contact tabs manager.
 */
class ContactsTabManager implements ContactsTabManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The relationship manager.
   *
   * @var \Drupal\ctools\Plugin\RelationshipManagerInterface
   */
  protected $relationshipManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The private tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempstore;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Construct the tabs manager service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Block\BlockManager $block_manager
   *   The block plugin manager.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
   * @param \Drupal\ctools\Plugin\RelationshipManagerInterface $relationship_manager
   *   The relationship manager.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param \Drupal\user\PrivateTempStoreFactory $tempstore
   *   The private tempstore.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request statck.
   */
  public function __construct(EntityTypeManager $entity_type_manager, BlockManager $block_manager, ContextHandlerInterface $context_handler, RelationshipManagerInterface $relationship_manager, AccountProxy $current_user, PrivateTempStoreFactory $tempstore, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->blockManager = $block_manager;
    $this->contextHandler = $context_handler;
    $this->relationshipManager = $relationship_manager;
    $this->currentUser = $current_user;
    $this->tempstore = $tempstore;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function getTab($id) {
    return $this->entityTypeManager->getStorage('contact_tab')->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getTabByPath($path) {
    $tabs = $this->entityTypeManager->getStorage('contact_tab')->loadByProperties(['path' => $path]);

    if (!empty($tabs)) {
      $tab = reset($tabs);
      return $tab;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTabs() {
    // Load all our active tabs.
    /* @var \Drupal\contacts\Entity\ContactTabInterface[] $tabs */
    $tabs = $this->entityTypeManager->getStorage('contact_tab')->loadByProperties(['status' => TRUE]);
    if (empty($tabs)) {
      return [];
    }

    // Sort our tabs by weight.
    $entity_type = $this->entityTypeManager->getDefinition('contact_tab');
    uasort($tabs, [$entity_type->getClass(), 'sort']);

    // Return our tabs.
    return $tabs;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlocks(ContactTabInterface $tab, $apply_context = FALSE, UserInterface $contact = NULL) {
    $blocks = $tab->getBlockPlugins();
    if (empty($blocks)) {
      // Get our block plugin, applying context if relevant..
      $block_configurations = $tab->getBlocks() ?: [];

      $blocks = [];
      foreach ($block_configurations as $key => $block_configuration) {
        /* @var \Drupal\Core\Block\BlockPluginInterface $block */
        $block = $this->blockManager->createInstance($block_configuration['id'], $block_configuration);
        $blocks[$key] = $block;
      }
    }

    if ($apply_context && $contact) {
      $this->applyBlockContext($tab, $blocks, $contact);
    }

    $tab->setBlockPlugins($blocks);

    return $blocks;
  }

  /**
   * Apply block plugin context requirements.
   *
   * @param \Drupal\contacts\Entity\ContactTabInterface $tab
   *   The tab entity the blocks are on.
   * @param \Drupal\Core\Block\BlockPluginInterface[] $blocks
   *   Blocks to apply the contexts to.
   * @param \Drupal\user\UserInterface $contact
   *   The contact we are applying contexts for.
   */
  protected function applyBlockContext(ContactTabInterface $tab, array &$blocks, UserInterface $contact) {
    // Apply context to our block plugins if relevant.
    foreach ($blocks as $key => &$block) {
      /* @var \Drupal\Core\Block\BlockPluginInterface $block */
      if ($block instanceof ContextAwarePluginInterface) {
        try {
          // Build our contexts.
          $contexts = [
            'user' => new Context(new ContextDefinition('entity:user'), $contact),
          ];

          // Gather any relationships.
          foreach ($tab->getRelationships() as $relationship) {
            if (isset($contexts[$relationship['source']])) {
              /* @var \Drupal\ctools\Plugin\RelationshipInterface $plugin */
              $plugin = $this->relationshipManager->createInstance($relationship['id']);
              $plugin->setContext('base', $contexts[$relationship['source']]);
              $contexts[$relationship['name']] = $plugin->getRelationship();
            }
          }

          // Apply the mappings.
          $this->contextHandler->applyContextMapping($block, $contexts);
        }
        catch (ContextException $exception) {
          unset($blocks[$key]);
          continue;
        }
      }

      // @todo Don't allow block to be added if relationships have not been
      // satisfied.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function verifyTab(ContactTabInterface $tab, UserInterface $contact) {
    $blocks = $this->getBlocks($tab, TRUE, $contact);

    if (empty($blocks)) {
      return FALSE;
    }

    // Make sure each block is verified and capture if any have.
    foreach ($blocks as $block) {
      /* @var $block \Drupal\Core\Block\BlockPluginInterface */
      if (!isset($block->_contactTabVerified)) {
        // Check access on the block.
        if (!$block->access($this->currentUser)) {
          $block->_contactTabVerified = FALSE;
          continue;
        }

        // @todo: Add additional checks...
        $block->_contactTabVerified = TRUE;
      }
    }

    // Once verified, only store verified blocks on tab.
    $blocks = array_filter($blocks, function ($block) {
      return $block->_contactTabVerified;
    });
    $tab->setBlockPlugins($blocks);

    return !empty($blocks);
  }

}
