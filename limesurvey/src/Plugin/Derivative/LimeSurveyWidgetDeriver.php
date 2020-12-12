<?php

namespace Drupal\limesurvey\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\limesurvey\LimeSurveyClientinterface;

/**
 * Retrieves block plugin definitions for all snippet blocks.
 */
class LimeSurveyWidgetDeriver extends DeriverBase  {

  use StringTranslationTrait;
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    $SurveyIds = \Drupal::config('limesurvey.lime_survey.surveylist')->get('surveyid');
    if ($SurveyIds) {
      foreach ($SurveyIds as $SurveyId => $value) {
        $delta = $SurveyId;
        $this->derivatives[$delta] = $base_plugin_definition;
        $this->derivatives[$delta]['admin_label'] = $this->t('Survey Widget') . ': ' . $value;
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
