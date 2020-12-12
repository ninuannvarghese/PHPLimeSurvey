<?php

namespace Drupal\limesurvey\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerTrait;

/**
 *
 */
class LimeSurveyDeleteForm extends ConfirmFormBase {
  use MessengerTrait;

  private $surveyid = NULL;

  /**
   * The Drupal configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a survey form object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory holding resource settings.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lime_survey_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete this survey ID?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('limesurvey.listsurvey');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $surveyid = NULL) {
    $this->surveyid = $surveyid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //@todo: Block cache needs to be invalidated
    $SurveyData = $this->configFactory->get('limesurvey.lime_survey.surveylist')->get('surveydata');
    $survey_id = $this->surveyid;
    if (array_key_exists($survey_id, $SurveyData)) {
      unset($SurveyData[$survey_id]);
      $this->configFactory->getEditable('limesurvey.lime_survey.surveylist')->set('surveydata', $SurveyData)->save();
      $form_state->setRedirect('limesurvey.listsurvey');
      $this->messenger()->addMessage($this->t('The Survey ID @survey_id has been removed.', ['@survey_id' => $survey_id]));
    }
    else {
      $this->messenger()->addError($this->t('The Survey ID @survey_id is invalid.', ['@survey_id' => $survey_id]));

    }
    //clear all video and article node cache
    _limesurvey_clear_article_video_nodes_cache();
  }

}
