<?php

namespace Drupal\crm_tools\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Form\FormBuilder;
use Drupal\user\Entity\User;
use Drupal\user\Form\UserLoginForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to unify login and registration pages.
 */
class LoginController implements ContainerInjectionInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilder
   */
  protected $entityFormBuilder;

  /**
   * ChooseBlockController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityFormBuilder $entity_form_builder
   *   The entity form builder.
   */
  public function __construct(FormBuilder $form_builder, EntityFormBuilder $entity_form_builder) {
    $this->formBuilder = $form_builder;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * Provides the UI for choosing a new block.
   *
   * @return array
   *   A render array.
   */
  public function page() {
    $content = [];

    $content['forms'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['row']],
    ];
    // @todo Check if registration page is accessible.
    $content['forms']['login'] = $this->loginForm();
    $content['forms']['register'] = $this->registerForm();

    return $content;
  }

  /**
   * Provides the UI for choosing a new block.
   *
   * @return array
   *   A render array.
   */
  public function loginForm() {
    $form = $this->formBuilder->getForm(UserLoginForm::class);

    return [
      '#type' => 'container',
      '#attributes' => [
        'data-unified-login' => 'login',
        'class' => ['unified-login', 'login'],
      ],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => 'Login',
      ],
      'form' => $form,
    ];
  }

  /**
   * Provides the UI for choosing a new block.
   *
   * @return array
   *   A render array.
   */
  public function registerForm() {
    $user = User::create([]);
    $form = $this->entityFormBuilder->getForm($user, 'register');

    return [
      '#type' => 'container',
      '#attributes' => [
        'data-unified-login' => 'register',
        'class' => ['unified-login', 'register'],
      ],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => 'Register',
      ],
      'form' => $form,
    ];
  }

}
