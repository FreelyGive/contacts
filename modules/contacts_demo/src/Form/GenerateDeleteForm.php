<?php

namespace Drupal\contacts_demo\Form;

use Drupal\contacts_demo\DemoContentManager;
use Drupal\contacts_demo\DemoFlavourInterface;
use Drupal\contacts_demo\DemoFlavourManager;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that deletes demo content.
 */
class GenerateDeleteForm extends FormBase {

  /**
   * The Demo Flavour Manager.
   *
   * @var \Drupal\contacts_demo\DemoFlavourManager
   */
  protected $demoFlavourManager;

  /**
   * The Demo Content Manager.
   *
   * @var \Drupal\contacts_demo\DemoContentManager
   */
  protected $demoContentManager;
  
  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\contacts_demo\DemoFlavourManager $demo_flavour_manager
   *   The Demo Flavour Manager.
   * @param \Drupal\contacts_demo\DemoContentManager $demo_content_manager
   *   The Demo Content Manager.
   */
  public function __construct(DemoFlavourManager $demo_flavour_manager, DemoContentManager $demo_content_manager) {
    $this->demoFlavourManager = $demo_flavour_manager;
    $this->demoContentManager = $demo_content_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.demo_flavour'),
      $container->get('plugin.manager.demo_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contacts_demo_admin_generate_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    // @todo Show information about what content will be deleted.
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#button_type' => 'primary',
    );
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Figure out which flavours have been generated.
    $flavour = 'default';

    /* @var $plugin DemoFlavourInterface */
    $plugin = $this->demoFlavourManager->createInstance($flavour);
    // Delete content.
    $plugin->delete();
  }

}
