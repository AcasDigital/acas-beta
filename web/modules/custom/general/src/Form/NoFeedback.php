<?php

namespace Drupal\general\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Class YesFeedback.
 *
 */
class NoFeedback extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'no_feedback';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
    ];
    $form['nid'] = [
      '#type' => 'hidden'
    ];
    $form['radios_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Please tell us why the information did not help'),
      '#description' => $this->t('Select the statement you most agree with:'),
    ];
    $form['radios_wrapper']['radios'] = [
      '#type' => 'radios',
      '#options' => [1 => $this->t('I do not understand the information'), 2 => $this->t('I cannot find the information I\'m looking for'), 3 => $this->t('I cannot work out what to do next'), 4 => $this->t('Other')],
    ];
    $form['questions'] = [
      '#type' => 'hidden',
      '#value' => json_encode([1 => $this->t('What are you trying to find out?'), 2 => $this->t('What are you trying to find out?'), 3 => $this->t('What information are you looking for?'), 4 => $this->t('Tell us more about your answer')]),
    ];
    $form['answer'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Tell us more about your answer'),
      '#description' => $this->t('Please do not include any personal information, for example email address or phone number. Unfortunately we cannot respond to individual requests for information. If you need help, call our helpline on 0300 123 1190<br/><br/>'),
      '#description_display' => 'before',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];
    $form['#attributes']['class'][] = 'webform-submission-form webform-submission-no-feedback-form webform-submission-no-feedback-add-form';
    $form['#attributes']['novalidate'] = 'novalidate';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $entity_id = '';
    $entity_type = NULL;
    $uri = '';
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      $entity_id = $node->id();
      $entity_type = 'node';
      $uri = \Drupal::request()->getRequestUri();
    }else if ($values['nid']){
      $entity_id = $values['nid'];
      $entity_type = 'node';
      $uri = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $values['nid']);
    }
    $query = \Drupal::database()->select('webform_submission', 'ws');
    $query->addExpression('MAX(sid)', 'maxval');
    $sid = (int)$query->execute()->fetchField();
    $sid++;
    $query = \Drupal::database()->select('webform_submission', 'ws');
    $query->addExpression('MAX(serial)', 'maxval');
    $query->condition('ws.webform_id', 'no_feedback', '=');
    $serial = (int)$query->execute()->fetchField();
    $serial++;
    $uuid = \Drupal::service('uuid');
    $fields = [
      'sid' => $sid,
      'webform_id' => 'no_feedback',
      'uuid' => $uuid->generate(),
      'langcode' => 'en',
      'serial' => $serial,
      'uri' => $uri,
      'remote_addr' => $_SERVER['REMOTE_ADDR'],
      'in_draft' => 0,
      'entity_id' => $entity_id,
      'entity_type' => $entity_type,
      'created' => time(),
      'completed' => time(),
      'changed' => time(),
      'uid' => \Drupal::currentUser()->id(),
      'locked' => 0,
      'sticky' => 0,
    ];
    \Drupal::database()->insert('webform_submission')->fields($fields)->execute();
    $fields = [
      'webform_id' => 'no_feedback',
      'sid' => $sid,
      'name' => 'answer',
      'value' => $values['answer'],
      'delta' => 0,
    ];
    if (!$values['radios']) {
      $values['radios'] = 0;
    }
    \Drupal::database()->insert('webform_submission_data')->fields($fields)->execute();
    $fields = [
      'webform_id' => 'no_feedback',
      'sid' => $sid,
      'name' => 'radios',
      'value' => $values['radios'],
      'delta' => 0,
    ];
    \Drupal::database()->insert('webform_submission_data')->fields($fields)->execute();
    
    // Send email
    $config = \Drupal::config('acas.settings');
    if ($config->get('enable_feedback_email')) {
      if ($config->get('site_email')) {
        $to = \Drupal::config('system.site')->get('mail');
      }
      else {
        $to = $config->get('feedback_email');
      }
      $questions = [1 => $this->t('I do not understand the information'), 2 => $this->t('I cannot find the information I\'m looking for'), 3 => $this->t('I cannot work out what to do next'), 4 => $this->t('Other')];
      $mailManager = \Drupal::service('plugin.manager.mail');
      $module = 'general';
      $key = 'feedback';
      $params['body'][] = $questions[(int) $values['radios']];
      $params['body'][] = 'What are you trying to find out?';
      $params['body'][] = $values['answer'];
      $params['subject'] = 'No feedback';
      $langcode = \Drupal::currentUser()->getPreferredLangcode();
      $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, TRUE);
    }
    
    // Redirect for JS disabled
    $form_state->setRedirectUrl(url::fromUserInput('/feedback-thankyou'));
  }
}