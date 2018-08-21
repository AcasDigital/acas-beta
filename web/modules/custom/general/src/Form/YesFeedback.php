<?php

namespace Drupal\general\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class YesFeedback.
 *
 */
class YesFeedback extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yes_feedback';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = [
      '#type' => 'email',
      '#title' => 'Email address',
      '#required' => TRUE,
    ];
    $form['nid'] = [
      '#type' => 'hidden'
    ];
    $form['answer'] = [
      '#type' => 'textarea',
      '#title' => 'What you were looking for?',
      '#description' => 'Please do not include any personal information, for example email address or phone number.<br/><br/>',
      '#description_display' => 'before',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Send',
    ];
    $form['#attributes']['class'][] = 'webform-submission-form webform-submission-yes-feedback-form webform-submission-yes-feedback-add-form';
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
    $query->condition('ws.webform_id', 'yes_feedback', '=');
    $serial = (int)$query->execute()->fetchField();
    $serial++;
    $uuid = \Drupal::service('uuid');
    $fields = [
      'sid' => $sid,
      'webform_id' => 'yes_feedback',
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
      'webform_id' => 'yes_feedback',
      'sid' => $sid,
      'name' => 'answer',
      'value' => $values['answer'],
      'delta' => 0,
    ];
    \Drupal::database()->insert('webform_submission_data')->fields($fields)->execute();
    $form_state->setRedirectUrl(url::fromUserInput('/feedback-thankyou'));
  }
}