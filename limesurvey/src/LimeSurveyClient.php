<?php

namespace Drupal\limesurvey;

use Drupal\Core\Config\ConfigFactory;
use Meritoo\LimeSurvey\ApiClient\Client\Client;
use Meritoo\LimeSurvey\ApiClient\Configuration\ConnectionConfiguration;
use Meritoo\LimeSurvey\ApiClient\Type\MethodType;
use Meritoo\LimeSurvey\ApiClient\Manager\JsonRpcClientManager;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Exception\RequestException;


/**
 * Methods to make an API call and tool to handle the output.
 */
class LimeSurveyClient implements LimeSurveyClientinterface {
  use MessengerTrait;
  use StringTranslationTrait;
  /**
   * Defines the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  public $configFactory;
  public $connectionConfig;
  public $settings;
  public $client;
  public $rpcClientManager;
  public $sessionKey;

  /**
   * An client to make http requests.
   *
   * @var \Meritoo\LimeSurvey\ApiClient\Client\Client
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
    $this->settings = $this->configFactory->get('limesurvey.settings')->get('settings');
    $this->connectionConfig = new ConnectionConfiguration($this->settings['lime_survey_end_point'], $this->settings['lime_survey_username'], $this->settings['lime_survey_password'], FALSE, FALSE);
    $this->client = new Client($this->connectionConfig);
    $this->rpcClientManager = new JsonRpcClientManager($this->connectionConfig);
    if (!empty($this->settings['lime_survey_end_point'])) {

      try {
        $resp = \Drupal::httpClient()->get($this->settings['lime_survey_end_point'], ['connect_timeout' => 3]);
        if($resp) {
          $this->sessionKey = $this->GetSessionKey($this->settings['lime_survey_username'], $this->settings['lime_survey_password']);
        }
      }
      catch (RequestException $e) {
        if ($e->getCode() > 499 || $e->getCode() < 199) {
          \Drupal::logger(' Survey')->error($e->getMessage() . ' Code: ' . $e->getCode());
        }
      }
    }

  }


  /**
   * Make a request to the ww server and return it as an array.
   *
   * @param array $options
   *   Options build the request url.
   *
   * @return array
   *   An array containing survey data.
   */
  public function requestData($SurveyID) {
    $QuestionsProp = [];
    if (!empty($this->settings['lime_survey_end_point']) && !empty($this->settings['lime_survey_username']) && !empty($this->settings['lime_survey_password'])) {
      // List survey.
      $SurveyList = $this->ListSurvey();

      if (is_iterable($SurveyList)) {
        foreach ($SurveyList as $Survey) {

          if (($Survey->isActive()) && ($SurveyID == $Survey->getID())) {
            try {
              $list_questions = $this->ListQuestions($SurveyID);
              foreach ($list_questions as $question) {
                $QuestionID = $question['qid'];
                $QuestionsProp[] = $this->GetQuestionProperties($QuestionID);
              }
            }
            catch (\Exception $exception) {
              \Drupal::logger(' Survey')->error($exception->getMessage());
            }
          }
        }
      }
      return $QuestionsProp;
    }
  }

  /**
   * Get the module settings.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   The configuration object.
   */
  public function GetSettings() {
    return $this->configFactory->get('limesurvey.settings')->get('settings');
  }

  /**
   * Create and return a session key.
   *
   * @param string $username
   * @param string $password
   * @param string $plugin
   *   to be used.
   *
   * @return string|array
   */
  public function GetSessionKey($SurveyUname, $SurveyPass) {
    $SessionKey = NULL;
    try {
      $SessionKey = $this->rpcClientManager->runMethod('get_session_key', [$SurveyUname, $SurveyPass]);

    }
    catch (\Exception $exception) {
      \Drupal::logger(' Survey')->error($exception->getMessage());

    }
    return $SessionKey;
  }

  /**
   * List the survey belonging to a user.
   *
   * If user is admin he can get surveys of every user (parameter sUser) or all surveys (sUser=null)
   * Else only the surveys belonging to the user requesting will be shown.
   *
   * Returns array with
   * * `sid` the ids of survey
   * * `surveyls_title` the title of the survey
   * * `startdate` start date
   * * `expires` expiration date
   * * `active` if survey is active (Y) or not (!Y)
   *
   * @param string $sSessionKey
   *   Auth credentials.
   * @param string|null $sUsername
   *   (optional) username to get list of surveys.
   *
   * @return array In case of success the list of surveys
   */
  public function ListSurvey() {
    $SurveyList = NULL;
    try {
      $SurveyList = $this->client->run(MethodType::LIST_SURVEYS)->getData();

    }
    catch (\Exception $exception) {
      \Drupal::logger(' Survey')->error($exception->getMessage());

    }
    return $SurveyList;
  }

  /**
   * Return the ids and info of (sub-)questions of a survey/group.
   * Returns array of ids and info.
   *
   * @param string $sSessionKey
   *   Auth credentials.
   * @param int $iSurveyID
   *   ID of the Survey to list questions.
   * @param int $iGroupID
   *   Optional id of the group to list questions.
   * @param string $sLanguage
   *   Optional parameter language for multilingual questions.
   *
   * @return array The list of questions
   */
  public function ListQuestions($SurveyID) {
    $QuestionList = NULL;
    try {
      $QuestionList = $this->rpcClientManager->runMethod('list_questions', [$this->sessionKey, $SurveyID]);

    }
    catch (\Exception $exception) {
      \Drupal::logger(' Survey')->error($exception->getMessage());

    }
    return $QuestionList;
  }

  /**
   * Get properties of a question in a survey.
   *
   * @see \Question for available properties.
   * Some more properties are available_answers, subquestions, attributes, attributes_lang, answeroptions, defaultvalue
   * @param string $sSessionKey
   *   Auth credentials.
   * @param int $iQuestionID
   *   ID of the question to get properties.
   * @param array $aQuestionSettings
   *   (optional) properties to get, default to all.
   * @param string $sLanguage
   *   (optional) parameter language for multilingual questions, default are \Survey->language.
   *
   * @return array The requested values
   */
  public function GetQuestionProperties($QuestionID) {
    $QuestionProperties = NULL;
    try {
      $QuestionProperties = $this->rpcClientManager->runMethod('get_question_properties', [$this->sessionKey, $QuestionID]);

    }
    catch (\Exception $exception) {
      \Drupal::logger(' Survey')->error($exception->getMessage());

    }
    return $QuestionProperties;
  }

  /**
   * Add a response to the survey responses collection.
   * Returns the id of the inserted survey response.
   *
   * @access public
   * @param string $sSessionKey
   *   Auth credentials.
   * @param int $iSurveyID
   *   ID of the Survey to insert responses.
   * @param array $aResponseData
   *   The actual response.
   *
   * @return int|array The response ID or an array with status message (can include result_id)
   */
  public function AddResponse($SurveyID, $ResponseData) {
    $Response = NULL;
    try {
      $new_session_key = $this->GetSessionKey($this->settings['lime_survey_username'], $this->settings['lime_survey_password']);
      $Response = $this->rpcClientManager->runMethod('add_response', [$new_session_key, $SurveyID, $ResponseData]);
      $this->ReleaseSessionKey($new_session_key);
    }
    catch (\Exception $exception) {
      \Drupal::logger(' Survey')->error($exception->getMessage());

    }
    return $Response;
  }

  /**
   * Close the RPC session.
   *
   * Using this function you can close a previously opened XML-RPC/JSON-RPC session.
   *
   * @access public
   * @param string $sSessionKey
   *   the session key.
   *
   * @return string OK
   */
  public function ReleaseSessionKey($SessionKey) {
    $ReleaseSessionKey = NULL;
    try {
      $ReleaseSessionKey = $this->rpcClientManager->runMethod('release_session_key', [$SessionKey]);
    }
    catch (\Exception $exception) {
      \Drupal::logger(' Survey')->error($exception->getMessage());

    }
    return $ReleaseSessionKey;

  }

  /**
   * @param $request
   *
   * @return mixed
   */
  public function BuildSurveyForm($request) {
    $form = [];
    $form['#attached']['library'][] = 'limesurvey/survey';
    $form['#attached']['library'][] = 'core/drupal.ajax';
    if (!empty($request)) {
      foreach ($request as $surveyitem) {
        $hidden = (isset($surveyitem['attributes']['hidden']) && $surveyitem['attributes']['hidden']) ? true : false;

        $inputType = $this->ProcessQuestionType($surveyitem['type']);

        $css_class = isset($surveyitem['attributes']['cssclass'])?$surveyitem['attributes']['cssclass']:'';

        $full_node_url = \Drupal::urlGenerator()->generateFromRoute('<current>', [], ['absolute' => TRUE]);

        $referrer = ($surveyitem['title'] == 'referrer') ? ($full_node_url) : '';

        $form['message'] = [
          '#type' => 'markup',
          '#markup' => '<div class="error_message"></div>',
        ];
        $form[$surveyitem['sid']]['question_' . $surveyitem['qid']] = [
          '#type' => $inputType['type'],
          '#attributes' => ['class' => ['container-inline',$css_class]],
          '#title' => ($surveyitem['question']),
          '#required' => ($surveyitem['mandatory'] == 'Y') ? TRUE : FALSE,
          '#weight' => $surveyitem['question_order'],
          '#description' => $surveyitem['help'],
        ];
        if (is_array($surveyitem['answeroptions'])) {
          $options = $this->ProcessOptions($surveyitem['answeroptions']);
          $form[$surveyitem['sid']]['question_' . $surveyitem['qid']]['#options'] = $options;
        }

        if ($hidden) {
          $form[$surveyitem['sid']]['question_' . $surveyitem['qid']] = [
            '#type' => 'hidden',
            '#value' => $referrer,
          ];
        }

        $form[$surveyitem['sid']]['survey_id'] = [
          '#type' => 'hidden',
          '#value' => $surveyitem['sid'],
        ];
        $form[$surveyitem['sid']]['survey_gid'] = [
          '#type' => 'hidden',
          '#value' => $surveyitem['gid'],
        ];
        $form['actions'] = [
          '#type' => 'actions',
        ];

        // Add a submit button that handles the submission of the form.
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => 'Submit',
          '#weight' => $surveyitem['question_order'] + 1,
          '#ajax' => [
            'callback' => '::ajaxSubmitForm',
            'event' => 'click',
          ],
        ];

      }
      $form['#cache']['contexts'][] = 'session';
      $form['#prefix'] = '<div class="npsgtmscore-container success_message">';
      $form['#suffix'] = '</div>';
      return $form;

    }
    else {
      $user = \Drupal::currentUser();
      $user_anonymous = $user->isAnonymous();
      //show the error only for loged in users
      if (!$user_anonymous) {
        $this->messenger()->addError($this->t('Survey not available!'));
      }
    }

  }

  /**
   * @param $type
   *   The Input Field Type
   *   Translates the Field type code to html input type
   *   Please check your Lime Survey's folder /limesurvey/application/helpers/export_helper.php
   * @return array with html input type and option (bool)
   */
  public function ProcessQuestionType($type) {
    switch ($type) {
      // 5 point radio button.
      case "5":
        // LIST drop-down/radio-button list.
      case "L":
        // ARRAY (5 POINT CHOICE) radio-buttons.
      case "A":
        // ARRAY (10 POINT CHOICE) radio-buttons.
      case "B":
        // ARRAY (YES/UNCERTAIN/NO) radio-buttons.
      case "C":
        // ARRAY (Increase/Same/Decrease) radio-buttons.
      case "E":
        // YES/NO radio-buttons.
      case "Y":

        $input = ['type' => 'radios', 'options' => TRUE];
        break;

      // LONG FREE TEXT.
      case "T":
        $input = ['type' => 'textarea'];
        break;

      // HUGE FREE TEXT.
      case "U":
        $input = ['type' => 'text_format'];
        break;

      // GENDER drop-down list.
      case "G":
        $input = ['type' => 'select', 'options' => TRUE];
        break;

      // Hidden field.
      case "S":
        $input = ['type' => 'hidden'];
        break;

    }
    return $input;
    // End Switch.
  }

  /**
   * Process the given array's and convert them into radio oprions.
   *
   * @param $answeroptions
   *
   * @return array
   */
  public function ProcessOptions($answeroptions) {
    $choices = [];
    foreach ($answeroptions as $key => $answeroption) {
      $choices[$key] = $answeroption['answer'];
    }
    return $choices;
  }

}
