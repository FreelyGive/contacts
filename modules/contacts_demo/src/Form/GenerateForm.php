<?php

namespace Drupal\contacts_demo\Form;

use Drupal\contacts_demo\DemoContentManager;
use Drupal\contacts_demo\DemoFlavourManager;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that generates demo content.
 */
class GenerateForm extends FormBase {

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
    return 'contacts_demo_admin_generate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    // @todo Work out if flavours have already been generated.
    $form['flavours'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Demo Flavours'),
      '#required' => TRUE,
      '#default_value' => 'default',
      '#options' => [],
      '#description' => $this->t('Select the flavour of contacts demo content you wish to install.'),
    );

    foreach ($this->demoFlavourManager->getDefinitions() as $id => $definition) {
      $form['flavours']['#options'][$id] = $definition['label'];

      $supported = $this->demoFlavourManager->isPluginSupported($id);
      $form['flavours'][$id]['#disabled'] = !$supported;

      $form['flavours'][$id]['#description'] = [
        '#type' => 'inline_template',
        '#template' => '{{ description }}{% if not supported %}<div><small>{% trans %}<strong>Not available</strong>. You may need to install external dependencies for use this plugin.{% endtrans %}</small></div>{% endif %}',
        '#context' => [
          'description' => $definition['description'],
          'supported' => $supported,
        ]
      ];
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
      '#button_type' => 'primary',
    );
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $flavour = $form_state->getValue('flavours');
    $plugin = $this->demoFlavourManager->createInstance($flavour);
    // Generate content.
    $plugin->generate();
  }

}
