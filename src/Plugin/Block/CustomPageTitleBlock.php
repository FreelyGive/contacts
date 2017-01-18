<?php

namespace Drupal\contacts\Plugin\Block;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'CustomPageTitleBlock' block.
 *
 * @Block(
 *  id = "custom_page_title_block",
 *  admin_label = @Translation("Custom page title"),
 *  context = {
 *    "user" = @ContextDefinition("entity:user", label = @Translation("User"), required = FALSE)
 *  }
 * )
 */
class CustomPageTitleBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a new CustomPageTitleBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'title' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#description' => $this->t('The custom page title text.'),
      '#default_value' => $this->configuration['title'],
      '#maxlength' => 64,
      '#size' => 64,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $data = $this->getContextAsTokenData();
    $title = Markup::create((string) $this->token->replace(new HtmlEscapedText($this->configuration['title']), $data));

    return [
      '#type' => 'page_title',
      '#title' => $title,
    ];
  }

  /**
   * Returns available context as token data.
   *
   * @return array
   *   An array with token data values keyed by token type.
   */
  protected function getContextAsTokenData() {
    $data = array();
    foreach ($this->getContexts() as $context) {
      // @todo Simplify this when token and typed data types are unified in
      //   https://drupal.org/node/2163027.
      if (strpos($context->getContextDefinition()->getDataType(), 'entity:') === 0) {
        $token_type = substr($context->getContextDefinition()->getDataType(), 7);
        if ($token_type == 'taxonomy_term') {
          $token_type = 'term';
        }
        $data[$token_type] = $context->getContextValue();
      }
    }
    return $data;
  }

}
