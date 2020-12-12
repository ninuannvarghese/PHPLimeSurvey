<?php

namespace Drupal\limesurvey\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;

/**
 * Controller for limesurvey surveyid settings.
 */
class LimeSurveyController extends ControllerBase {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a limesurvey controller object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory holding resource settings.
   */
  public function __construct(FormBuilderInterface $form_builder, ConfigFactoryInterface $config_factory) {
    $this->formBuilder = $form_builder;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('config.factory')
    );
  }

  /**
   * Constructs a list of locations.
   */
  public function ConfigureSurvey() {
    $rows = $build = [];
    $surveyData = $this->configFactory->get('limesurvey.lime_survey.surveylist')->get('surveydata');
    $surveyTypes = $this->configFactory->getEditable('limesurvey.lime_survey.surveylist')->get('surveytypes');
    $form_arg = 'Drupal\limesurvey\Form\SurveyList';
    $build['limesurvey_form'] = $this->formBuilder->getForm($form_arg);

    $header = [
      $this->t('surveyid'),
      $this->t('Survey Name'),
      $this->t('Survey Type'),
      [
        'data' => $this->t('Operations'),
        'colspan' => 2,
      ],
    ];

    if (!empty($surveyData)) {

      foreach ($surveyData as $key => $value) {
        $operations = [];
        $operations['delete'] = [
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('limesurvey.delete', ['surveyid' => $key]),
        ];

        $data['surveyid'] = $key;
        $data['surveyname'] = Html::escape($value['surveytitle']);
        $data['surveytype'] = $surveyTypes[$value['surveytype']];

        $data['operations'] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $operations,
          ],
        ];

        $rows[] = $data;
      }
    }

    $build['limesurvey_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No survey available.'),
    ];
    return $build;
  }

}
