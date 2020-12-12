<?php

namespace Drupal\limesurvey\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\limesurvey\LimeSurveyClientinterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Provides a form.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class SurveyForm extends FormBase {
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
  public static function create(ContainerInterface $cont) {
    return new static(
      $cont->get('config.factory'),

      $cont->get('limesurvey.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nps_survey_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $arg = NULL) {
    $data = [];
    $service = \Drupal::service('limesurvey.client');
    $SurveyData = $this->configFactory->get('limesurvey.lime_survey.surveylist')->get('surveydata');
    foreach ($SurveyData as $SurveyId => $value) {
      if( $arg ==$SurveyId ){
        $request = $service->requestData($SurveyId);
        $data = $service->BuildSurveyForm($request);
      }
    }
    $service->ReleaseSessionKey($service->sessionKey);
    return($data);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //this form submit is not necessary due to ajax form submit
  }

  /**
   * Implements the submit handler for the modal dialog AJAX call.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Array of AJAX commands to execute on submit of the modal form.
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    if ($form_state->getErrors()) {
      $ajax_response->addCommand(
        new HtmlCommand(
          '.error_message',
          $this->t('<div class="my_top_message messages messages--error">Fields marked with * are required.</div>')
        )
      );
    }
    // If there are no errors, show the output dialog.
    else {
      $values = $form_state->getValues();
      $SurveyID = $values['survey_id'];
      $groupID = $values['survey_gid'];
      foreach ($values as $key => $value) {
        if (strpos($key, 'question') !== FALSE) {
          $quest = explode('_', $key);
          $questionID = $quest[1];
          $answer = $value;
          $sgqa = $SurveyID . 'X' . $groupID . 'X' . $questionID;

          $response[$sgqa] = $answer;
        }
      }

      $this->limesurvey->AddResponse($SurveyID, $response);
      $ajax_response->addCommand(
        new HtmlCommand(
          '.success_message',
          $this->t('<div class="npsgtmscore-result-container"><p>Thank you for your feedback.</p></div>')

        )
      );

    }
    return $ajax_response;
  }

}
