<?php

namespace Drupal\limesurvey\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *  Survey Settings Form.
 */
class SurveySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'limesurvey_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'limesurvey.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->configFactory->get('limesurvey.settings')->get('settings');
    $yes_no_options = [
      FALSE => $this->t('No'),
      TRUE => $this->t('Yes'),
    ];

    $form['#tree'] = TRUE;
    $form['settings']['lime_survey_end_point'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lime Survey Endpoint'),
      '#required' => TRUE,
      '#default_value' => empty($settings['lime_survey_end_point']) ? '' : $settings['lime_survey_end_point'],
      '#description' => $this->t('Please enter your Lime Survey End Point'),
    ];

    $form['settings']['lime_survey_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lime Survey User Name'),
      '#required' => TRUE,
      '#default_value' => empty($settings['lime_survey_username']) ? '' : $settings['lime_survey_username'],
      '#description' => $this->t('Please enter your Lime Survey User Name.'),
    ];

    $form['settings']['lime_survey_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lime Survey Secret Key'),
      '#required' => TRUE,
      '#default_value' => empty($settings['lime_survey_password']) ? '' : $settings['lime_survey_password'],
      '#description' => $this->t('Please enter your lime_survey_password Secret Key.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_value = $form_state->getValue('settings');
    $this->config('limesurvey.settings')
      ->set('settings', $form_value)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
