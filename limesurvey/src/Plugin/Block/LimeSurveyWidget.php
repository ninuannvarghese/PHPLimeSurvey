<?php

namespace Drupal\limesurvey\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides Lime Survey Widget.
 *
 * @Block(
 *   id = "lime_survey_widget",
 *   admin_label = @Translation("Lime Survey Widget"),
 *   deriver = "Drupal\limesurvey\Plugin\Derivative\LimeSurveyWidgetDeriver"
 * )
 */
class LimeSurveyWidget extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a LimeSurveyWidget object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $formBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block_id = $this->getDerivativeId();
    return [
      '#prefix' => '<div id="nps_survey_block" data-swiftype-index="false">',
      '#suffix' => '</div>',
      'form' => \Drupal::formBuilder()->getForm('Drupal\limesurvey\Form\SurveyForm', $block_id),
      '#cache' => [
        'max-age' => 0,
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  /* protected function blockAccess(AccountInterface $account) {
  return AccessResult::allowedIfHasPermission($account, 'access content');
  }*/
}
