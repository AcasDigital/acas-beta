<?php

namespace Drupal\general\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FeedbackForm.
 *
 */
class FeedbackForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feedback_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'fieldset',
      '#title' => t('Filters'),
      '#collapsible' => TRUE,
    );
    $form['filters']['from_date'] = [
      '#type' => 'date',
      '#title' => 'From date',
    ];
    $form['filters']['to_date'] = [
      '#type' => 'date',
      '#title' => 'To date',
    ];
    $form['filters']['url'] = [
      '#type' => 'url',
      '#title' => 'URL',
    ];
    $form['filters']['type'] = [
      '#type' => 'radios',
      '#title' => 'Feedback',
      '#options' => [1 => 'Yes', 2 => 'No', 3 => 'Both'],
      '#default_value' => 3,
    ];
    $form['filters']['issues'] = [
      '#type' => 'checkboxes',
      '#title' => 'Issues',
      '#options' => [1 => 'I do not understand the information', 2 => 'I cannot find the information I\'m looking for', 3 => 'I cannot work out what to do next', 4 => 'Other'],
    ];
    $form['filters']['text'] = [
      '#type' => 'textfield',
      '#title' => 'Text',
    ];
    $manager = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $tree = $manager->loadTree('acas', 0, 2, TRUE);
    $result = [0 => '--Select--'];
    foreach ($tree as $term) {
      if (!empty($manager->loadParents($term->id()))) {
        $result[$term->id()] = $term->getName();
      }
    }
    $form['filters']['taxonomy'] = [
      '#type' => 'select',
      '#title' => 'Taxonomy',
      '#options' => $result,
    ];
    $form['filters']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Apply'),
      '#attributes' => ['class' => ['button, button--primary']],
    ];
    $form['#attached']['library'][] = "general/icheck";
    $form['#suffix'] = $this->table();
    return $form;
  }
  
  public function table() {
    // We are going to output the results in a table with a nice header.
    $header = [
      ['data' => $this->t('Date'), 'field' => 'ws.created'],
      ['data' => $this->t('Page URL'), 'field' => 'ws.uri'],
      ['data' => $this->t('Type'), 'field' => 'ws.webform_id'],
    ];

    
    $query = \Drupal::database()->select('webform_submission', 'ws')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->fields('ws', ['sid', 'created', 'uri', 'webform_id', 'entity_id']);

    // Don't forget to tell the query object how to find the header information.
    $result = $query
      ->orderByHeader($header)
      ->execute();

    $rows = [];
    foreach ($result as $row) {
      $rows[] = ['data' => (array) $row];
    }

    // Build the table for the nice output.
    $build = [
      '#markup' => '<p>' . t('The layout here is a themed as a table
           that is sortable by clicking the header name.') . '</p>',
    ];
    $build['tablesort_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return drupal_render($build);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Form is redirected no need for anything here.
  }
}