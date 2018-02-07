<?php

namespace Drupal\contacts\Form;

use Drupal\contacts\ContactsTabManager;
use Drupal\contacts\Controller\DashboardController;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The configuration form for dashboard blocks.
 */
class DashboardBlockConfigureForm extends FormBase {

  /**
   * The block plugin being configured.
   *
   * @var \Drupal\Core\Block\BlockPluginInterface
   */
  protected $block;

  /**
   * The block name key used to identify the block on the tab.
   *
   * @var string
   */
  protected $blockName;

  /**
   * The dashboard tab.
   *
   * @var \Drupal\contacts\Entity\ContactTab
   */
  protected $tab;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The tab manager.
   *
   * @var \Drupal\contacts\ContactsTabManager
   */
  protected $tabManager;

  /**
   * Construct the add contact form object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\contacts\ContactsTabManager $tab_manager
   *   The contacts tab manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager, ContactsTabManager $tab_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tabManager = $tab_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('contacts.tab_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "dashboard_block_configure_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->tab = $form_state->getBuildInfo()['args'][0];
    $this->block = $form_state->getBuildInfo()['args'][1];

    $form['#tree'] = TRUE;
    $form['settings'] = [];
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->block->buildConfigurationForm($form['settings'], $subform_state);;

    $configuration = $this->block->getConfiguration();
    $this->blockName = $configuration['name'];

    // We currently do not support editing of admin label.
    if (isset($form['settings']['admin_label'])) {
      unset($form['settings']['admin_label']);
    }

    // We currently do not support editing of context mapping.
    if (isset($form['settings']['context_mapping'])) {
      unset($form['settings']['context_mapping']);
    }

    $form['actions'] = ['#type' => 'actions'];


    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
      '#ajax' => ['callback' => '::ajaxSubmit'],
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => 'Cancel',
      '#limit_validation_errors' => [],
      '#attributes' => ['class' => ['dialog-cancel']],
    ];

    $form['actions']['actions']['cancel']['#attributes']['class'][] = 'dialog-cancel';


    $form['actions']['remove'] = [
      '#type' => 'submit',
      '#value' => 'Remove',
      '#submit' => [[$this, 'removeBlock']],
      '#limit_validation_errors' => [],
      '#ajax' => ['callback' => '::ajaxSubmit'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $sub_form_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $this->block->validateConfigurationForm($form['settings'], $sub_form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $sub_form_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $this->block->submitConfigurationForm($form, $sub_form_state);
    $this->tab->setBlock($this->blockName, $this->block->getConfiguration());
    $this->tab->save();
  }

  /**
   * Form submission handler; removes block from tab.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancelBlock(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function removeBlock(array &$form, FormStateInterface $form_state) {
    $blocks = $this->tab->getBlocks();
    unset($blocks[$this->blockName]);
    $this->tab->setBlocks($blocks);
    $this->tab->save();
  }

  /**
   * Submit form dialog #ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response that display validation error messages or represents a
   *   successful submission.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if ($form_state->hasAnyErrors()) {
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -1000,
      ];
      $response->addCommand(new ReplaceCommand('[data-drupal-selector="' . $form['#attributes']['data-drupal-selector'] . '"]', $form));
    }
    else {
      $dashboard_controller = \Drupal::service('class_resolver')->getInstanceFromDefinition(DashboardController::class);
      $response = $dashboard_controller->ajaxManageModeRefresh($this->tab);
      $response->addCommand(new CloseDialogCommand('#drupal-off-canvas'));
    }
    return $response;
  }

}
