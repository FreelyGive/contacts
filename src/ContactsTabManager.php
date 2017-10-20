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
  public function getTab(UserInterface $contact, $id) {
    /* @var \Drupal\contacts\Entity\ContactTabInterface $tab */
    $tab = $this->entityTypeManager->getStorage('contact_tab')->load($id);

    // Check this tab is valid for the contact.
    if ($tab && $this->verifyTab($tab, $contact)) {
      return $tab;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTabByPath(UserInterface $contact, $path) {
    $tabs = $this->entityTypeManager->getStorage('contact_tab')->loadByProperties(['path' => $path]);
    $tab = reset($tabs);

    // Check this tab is valid for the contact.
    if ($tab && $this->verifyTab($tab, $contact)) {
      return $tab;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTabs(UserInterface $contact) {
    // Load all our active tabs.
    /* @var \Drupal\contacts\Entity\ContactTabInterface[] $tabs */
    $tabs = $this->entityTypeManager->getStorage('contact_tab')->loadByProperties(['status' => TRUE]);
    if (empty($tabs)) {
      return [];
    }

    // Remove any tabs that are not for this contact.
    foreach ($tabs as $id => $tab) {
      if (!$this->verifyTab($tab, $contact)) {
        unset($tabs[$id]);
      }
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
  public function getBlocks(ContactTabInterface $tab, UserInterface $contact = NULL, $verify = TRUE) {
    $blocks = $tab->getBlockPlugins();
    if (empty($blocks)) {
      // Get our block plugin, applying context if relevant..
      $block_configurations = $tab->getBlocks() ?: [];

      $blocks = [];
      foreach ($block_configurations as $key => $block_configuration) {
        /* @var \Drupal\Core\Block\BlockPluginInterface $block */
        $block = $this->blockManager->createInstance($block_configuration['id'], $block_configuration);
        if ($contact && $block instanceof ContextAwarePluginInterface) {
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
            continue;
          }
        }

        // @todo Don't allow block to be added if relationships have not been
        // satisfied.
        $blocks[$key] = $block;
      }

      $tab->setBlockPlugins($blocks);
    }

    // Verify the tab unless we've been asked not to.
    if ($verify) {
      $this->verifyTab($tab, $contact, $blocks);

      // Filter our blocks that don't verify.
      $blocks = array_filter($blocks, function ($block) {
        return $block->_contactTabVerified;
      });
    }

    return $blocks;
  }

  /**
   * {@inheritdoc}
   */
  public function verifyTab(ContactTabInterface $tab, UserInterface $contact, array $blocks = []) {
    // Get the block if we don't already have it.
    if (empty($blocks)) {
      $blocks = $this->getBlocks($tab, $contact, FALSE);

      if (empty($blocks)) {
        return FALSE;
      }
    }

    // Make sure each block is verified and capture if any have.
    $verified = FALSE;
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

      // If any blocks verify, the tab verifies.
      if ($block->_contactTabVerified) {
        $verified = TRUE;
      }
    }

    return $verified;
  }

}
