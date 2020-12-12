<?php

namespace Drupal\limesurvey;

/**
 *
 */
interface LimeSurveyClientinterface {

  /**
   *
   */
  public function requestData($SurveyID);

  /**
   *
   */
  public function GetSettings();

  /**
   *
   */
  public function GetSessionKey($SurveyUname, $SurveyPass);

  /**
   *
   */
  public function ListSurvey();

  /**
   *
   */
  public function ListQuestions($SurveyID);

  /**
   *
   */
  public function GetQuestionProperties($QuestionID);

  /**
   *
   */
  public function AddResponse($SurveyID, $Response);

  /**
   *
   */
  public function ReleaseSessionKey($SessionKey);

  /**
   *
   */
  public function BuildSurveyForm($Request);

  /**
   *
   */
  public function ProcessQuestionType($type);

  /**
   *
   */
  public function ProcessOptions($answeroptions);

}
