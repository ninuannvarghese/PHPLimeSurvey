<?php

namespace Drupal\_survey\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\limesurvey\LimeSurveyClientinterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerTrait;

/**
 * Controller surveyid for LimeSurvey Form.
 */
class SurveyList extends ConfigFormBase {

  use MessengerTrait;
  /**
   * The Drupal configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   */
  protected $limesurvey;

  /**
   * Constructs a surveyid form object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory holding resource settings.
   * @param Drupal\limesurvey\LimeSurveyClientinterface $lime_survey
   *   The controls of Lime Survey.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LimeSurveyClientinterface $lime_survey) {
    $this->configFactory = $config_factory;
    $this->limesurvey = $lime_survey;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('limesurvey.client')
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'limesurvey_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'limesurvey.surveyid',
    ];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $surveyTypes = ['nps' => 'NPS Survey', 'other' => 'Other'];

    $form['surveyid'] = [
      '#type' => 'textfield',
      '#title' => 'Survey ID',
      '#description' => t('Enter your Survey ID that you have created in the lime survey server'),
      '#required' => TRUE,
    ];

    $form['surveytype'] = [
      '#type' => 'select',
      '#title' => 'Survey Type',
      '#options' => $surveyTypes,
      '#description' => t('Select the survey type'),
      '#required' => TRUE
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $surveyData = $this->configFactory->get('limesurvey.lime_survey.surveylist')->get('surveydata');
    $SurveyID_value = $form_state->getValue('surveyid');
    $SurveyType_value = $form_state->getValue('surveytype');

    //Check Survey ID
    if (empty($SurveyID_value) || (!is_numeric($SurveyID_value))) {
      $form_state->setErrorByName('surveyid', $this->t('Survey ID is invalid.'));
    }
    elseif (!empty($surveyData) && array_key_exists($SurveyID_value, $surveyData)) {
      $form_state->setErrorByName('surveyid', $this->t('Survey ID already exists.'));
    }
    else {
      $SurveyLists = $this->limesurvey->ListSurvey();
      $SurveyExists = false;
      foreach ($SurveyLists as $Survey) {
        if($SurveyID_value == $Survey->getID() && $Survey->isActive()) {
          $SurveyExists = true;
        }
        if (!$Survey->isActive() && ($SurveyID_value == $Survey->getID())) {
          $this->messenger()->addWarning($this->t('This survey  with ID (@survey_id) is not active at this moment.', ['@survey_id' => $Survey->getID()]));
        }
      }
      if(!$SurveyExists) {
        $form_state->setErrorByName('surveyid', $this->t('A survey with that ID does not exist.'));
      }
    }

    //Check Survey Type
    if (!empty($surveyData) && (array_search($SurveyType_value, array_column($surveyData, 'surveytype'))!==false && $SurveyType_value !== 'other')) {
      $form_state->setErrorByName('surveyid', $this->t('A survey with that type already exists.'));
    }

  }

  /**
   * Submit handler.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //@todo: Block cache needs to be invalidated
    $SurveyLists = $this->limesurvey->ListSurvey();
    $SurveyData = $this->configFactory->get('limesurvey.lime_survey.surveylist')->get('surveydata');
    $SurveyType = !empty($this->configFactory->getEditable('limesurvey.lime_survey.surveylist')->get('surveytypes')) ?  $this->configFactory->getEditable('limesurvey.lime_survey.surveylist')->get('surveytypes') : [];
    $SurveyID_value = $form_state->getValue('surveyid');
    $SurveyType_value = $form_state->getValue('surveytype');
    $SurveyTypes = ['nps' => 'NPS Survey', 'other' => 'Other'];

    if (!empty($SurveyID_value) && !empty($SurveyLists) && !empty($SurveyType_value)) {
      foreach ($SurveyLists as $Survey) {

        if ($SurveyID_value == $Survey->getID()) {
          $status = (!$Survey->isActive()) ? '(Inactive)' : '';
          $output = ['surveyid' => ['title' => $Survey->getTitle() . $status]];
          $SurveyData[$SurveyID_value] = [
            'surveytitle' => $output['surveyid']['title'],
            'surveytype' => $SurveyType_value
          ];
        }
      }
      $this->configFactory->getEditable('limesurvey.lime_survey.surveylist')->set('surveydata', $SurveyData)->save();

      //Only save this the first time or if we make an update to the survey types
      if(empty($this->configFactory->getEditable('limesurvey.lime_survey.surveylist')->get('surveytypes')) && count($SurveyTypes)!=count($SurveyType)) {

        $this->configFactory->getEditable('limesurvey.lime_survey.surveylist')->set('surveytypes', $SurveyTypes)->save();
      }
    }

    //clear all video and article node cache
    _limesurvey_clear_article_video_nodes_cache();
    parent::submitForm($form, $form_state);
  }

}
