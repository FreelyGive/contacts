<?php

namespace Drupal\contacts\Form;

use Drupal\contacts\ContactsTabManager;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
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
   * The subpage machine name.
   *
   * @var string
   */
  protected $subpage;

  /**
   * The contact user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

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
    $this->block = $form_state->getBuildInfo()['args'][0];

    $form['#tree'] = TRUE;
    $form['settings'] = [];
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->block->buildConfigurationForm($form['settings'], $subform_state);;

    $configuration = $this->block->getConfiguration();
    $this->blockName = $configuration['name'];
    $this->user = $this->getRouteMatch()->getParameter('user');
    $this->subpage = $this->getRouteMatch()->getParameter('subpage');
    $this->tab = $this->tabManager->getTabByPath($this->user, $this->subpage);

    if (isset($form['settings']['admin_label'])) {
      unset($form['settings']['admin_label']);
    }

    if (isset($form['settings']['context_mapping'])) {
      unset($form['settings']['context_mapping']);
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
      '#attributes' => ['class' => ['button--primary']],
      '#ajax' => [
        'callback' => [$this, 'updateBlock'],
        'progress' => [
          'type' => 'throbber',
          'message' => "Updating...",
        ],
      ],
    ];

    $form['cancel'] = [
      '#type' => 'submit',
      '#value' => 'Cancel',
      '#submit' => [[$this, 'cancelBlock']],
      '#limit_validation_errors' => [],
      '#attributes' => [
        'class' => ['btn', 'btn-outline-secondary'],
      ],
      '#ajax' => [
        'callback' => [$this, 'updateBlock'],
        'progress' => [
          'type' => 'throbber',
          'message' => "Updating...",
        ],
      ],
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
   * AJAX response callback to update form markup.
   *
   * @param array $form
   *   Drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Drupal form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response to update markup.
   */
  public function updateBlock(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $block_content = [
      '#theme' => 'contacts_dnd_card',
      '#attributes' => [
        'data-dnd-contacts-block-tab' => $this->tab->id(),
      ],
      '#id' => $this->block->getPluginId(),
      '#block' => $this->block,
      '#user' => $this->user->id(),
      '#subpage' => $this->subpage,
      '#mode' => 'manage',
    ];

    $response->addCommand(new ReplaceCommand("div[data-dnd-contacts-block-name='{$this->blockName}'][data-dnd-block-mode!='meta']", $block_content));
    return $response;
  }

  /**
   * AJAX response callback to update form markup.
   *
   * @param array $form
   *   Drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Drupal form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response to update markup.
   */
  public function removeBlock(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new RemoveCommand("div[data-dnd-contacts-block-name='{$this->blockName}'][data-dnd-block-mode!='meta']"));
    return $response;
  }

}
