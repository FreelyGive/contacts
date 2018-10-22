<?php

namespace Drupal\crm_tools\Controller;

use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;

/**
 * Defines a controller to unify login and registration pages.
 */
class LoginController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The class resovlver service.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolver
   */
  protected $classResolver;

  /**
   * The argument resolver service.
   *
   * @var \Symfony\Component\HttpKernel\Controller\ArgumentResolver
   */
  protected $argumentResolver;

  /**
   * Constructor for the unified login controller.
   *
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\DependencyInjection\ClassResolver $class_resolver
   *   The class resolver service.
   * @param \Symfony\Component\HttpKernel\Controller\ArgumentResolver $argument_resolver
   *   The argument resolver.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(FormBuilder $form_builder, EntityTypeManagerInterface $entity_type_manager, ClassResolver $class_resolver, ArgumentResolver $argument_resolver, TranslationInterface $string_translation) {
    $this->formBuilder = $form_builder;
    $this->entityTypeManager = $entity_type_manager;
    $this->classResolver = $class_resolver;
    $this->argumentResolver = $argument_resolver;
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('entity_type.manager'),
      $container->get('class_resolver'),
      $container->get('http_kernel.controller.argument_resolver'),
      $container->get('string_translation')
    );
  }

  /**
   * Provides the UI for choosing a new block.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   A render array.
   */
  public function page(RouteMatchInterface $route_match, Request $request) {
    $content = [];

    $content['forms'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['row']],
    ];

    $content['forms']['login'] = $this->loginForm($route_match, $request);
    $content['forms']['register'] = $this->registerForm($route_match, $request);

    return $content;
  }

  /**
   * Provides the UI for choosing a new block.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   A render array.
   */
  public function loginForm(RouteMatchInterface $route_match, Request $request) {
    $form_arg = $route_match->getRouteObject()->getDefault('_login_form');
    $form_object = $this->classResolver->getInstanceFromDefinition($form_arg);

    return [
      '#type' => 'container',
      '#attributes' => [
        'data-unified-login' => 'login',
        'class' => ['unified-login', 'login'],
      ],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Login'),
      ],
      'form' => $this->getForm($form_object, $request),
    ];
  }

  /**
   * Provides the UI for choosing a new block.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   A render array.
   */
  public function registerForm(RouteMatchInterface $route_match, Request $request) {
    $form_arg = $route_match->getRouteObject()->getDefault('_register_form');
    $form_arg .= '.default';
    list ($entity_type_id, $operation) = explode('.', $form_arg);
    $form_object = $this->entityTypeManager->getFormObject($entity_type_id, $operation);

    // Allow the entity form to determine the entity object from a given route
    // match.
    $entity = $form_object->getEntityFromRouteMatch($route_match, $entity_type_id);
    $form_object->setEntity($entity);

    return [
      '#type' => 'container',
      '#attributes' => [
        'data-unified-login' => 'register',
        'class' => ['unified-login', 'register'],
      ],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Register'),
      ],
      'form' => $this->getForm($form_object, $request),
    ];
  }

  /**
   * Build a form from the request.
   *
   * @param \Drupal\Core\Form\FormInterface $form_object
   *   The form object to retrieve the form for.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   The render array.
   *
   * @see \Drupal\Core\Controller\FormController::getContentResult
   */
  protected function getForm(FormInterface $form_object, Request $request) {
    // Add the form and form_state to trick the getArguments method of the
    // controller resolver.
    $form_state = new FormState();
    $request->attributes->set('form', []);
    $request->attributes->set('form_state', $form_state);
    $args = $this->argumentResolver->getArguments($request, [$form_object, 'buildForm']);
    $request->attributes->remove('form');
    $request->attributes->remove('form_state');

    // Remove $form and $form_state from the arguments, and re-index them.
    unset($args[0], $args[1]);
    $form_state->addBuildInfo('args', array_values($args));

    return $this->formBuilder->buildForm($form_object, $form_state);
  }

}
