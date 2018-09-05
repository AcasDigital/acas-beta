<?php

namespace Drupal\general\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\link\Plugin\Field\FieldWidget;

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
  protected function getEditableConfigNames() {
    return [
      'feedback.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('feedback.settings');
    $form['filters'] = array(
      '#type' => 'fieldset',
      '#title' => t('Filters'),
      '#collapsible' => TRUE,
    );
    $form['filters']['from_date'] = [
      '#type' => 'date',
      '#title' => 'From date',
      '#default_value' => $config->get('from_date') ?: '',
    ];
    $form['filters']['to_date'] = [
      '#type' => 'date',
      '#title' => 'To date',
      '#default_value' => $config->get('to_date') ?: '',
    ];
    $form['filters']['url'] = [
      '#type' => 'textfield',
      '#title' => 'Filter by URL',
      '#default_value' => $config->get('url') ?: '',
    ];
    /*
    $node = \Drupal::service('entity_type.manager')->getStorage('node')->create(array('type' => 'page'));
    $entity_form_display = \Drupal::service('entity_type.manager')->getStorage('entity_form_display')->load('node.page.default');
    if ($widget = $entity_form_display->getRenderer('field_test')) { //Returns the widget class
      $items = $node->get('field_test'); //Returns the FieldItemsList interface
      $items->filterEmptyItems();
      $form['#parents'] = [];
      $form['filters']['url'] = $widget->form($items, $form, $form_state); //Builds the widget form and attach it to your form
    }
    */
    $form['filters']['type'] = [
      '#type' => 'radios',
      '#title' => 'Feedback',
      '#options' => [1 => 'Yes', 2 => 'No', 3 => 'Both'],
      '#default_value' => $config->get('type') ?: 3,
    ];
    $options = [1 => 'I do not understand the information', 2 => 'I cannot find the information I\'m looking for', 3 => 'I cannot work out what to do next', 4 => 'Other'];
    $form['filters']['issues'] = [
      '#type' => 'checkboxes',
      '#title' => 'Filter by feedback issue',
      '#options' => $options,
    ];
    if ($config->get('issues')) {
      $form['filters']['issues']['#default_value'] = $config->get('issues');
    }
    $form['filters']['text'] = [
      '#type' => 'textfield',
      '#title' => 'Filter by additional text',
      '#default_value' => $config->get('text') ?: '',
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
      '#title' => 'Filter by topic',
      '#options' => $result,
      '#default_value' => $config->get('taxonomy') ?: '',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Apply'),
      '#attributes' => ['class' => ['button, button--primary']],
    ];
    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => t('Clear filters'),
      '#attributes' => ['class' => ['button']],
      '#submit' => array('::submitFormReset'),
    ];
    $form['#attached']['library'][] = "general/icheck";
    $form['actions']['reset']['#suffix'] = '<br /><br />' . $this->table($form_state, $options);
    $form['reset'] = [
      '#type' => 'submit',
      '#value' => t('Download CSV File'),
      '#attributes' => ['class' => ['button, button--primary']],
      '#submit' => array('::submitDownload'),
    ];
    return $form;
  }
  
  public function table(FormStateInterface $form_state, $options) {
    $header = [
      'created' => $this->t('Date'),
      'uri' => $this->t('URL'),
      'type' => $this->t('Yes/No'),
      'issue' => $this->t('Feedback issue'),
      'text' => $this->t('Additional text'),
      'taxonomy' => $this->t('Topic'),
    ];
    
    $query = \Drupal::database()->select('webform_submission', 'ws');
    $query->fields('ws', ['sid', 'created', 'uri', 'webform_id', 'entity_id']);
    if (!$values = $form_state->getValues()) {
      $config = $this->config('feedback.settings');
      $values = $config->get();
    }
    if ($values) {
      if ($values['from_date'] && $values['to_date']) {
        $query->condition('ws.created', [strtotime($values['from_date']), strtotime($values['to_date'])], 'BETWEEN');
      }else if ($values['from_date']) {
        $query->condition('ws.created', strtotime($values['from_date']), '>=');
      }else if ($values['to_date']) {
        $query->condition('ws.created', strtotime($values['to_date']), '<=');
      }
      if ($values['type'] == 1) {
        $query->condition('ws.webform_id', 'yes_feedback');
      }else if ($values['type'] == 2) {
        $query->condition('ws.webform_id', 'no_feedback');
      }
      /*
      if ($values['field_test'][0]['uri']) {
        $a = explode('node/', $values['field_test'][0]['uri']);
        $query->condition('ws.entity_id', $a[1]);
      }
      */
      if ($values['url']) {
        $query->condition('ws.uri', "%" . trim($values['url']) . "%", 'LIKE');
      }
    }
    $query->orderBy('ws.sid', 'DESC');
    $result = $query->execute()->fetchAll();
    $rows = [];
    foreach ($result as $row) {
      if ($entity = \Drupal\node\Entity\Node::load($row->entity_id)) {
        $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($entity->get('field_taxonomy')->target_id);
        foreach($parents as $parent) {
          $taxonomy = $parent->getName();
          $tid = $parent->id();
        }
        $query = \Drupal::database()->select('webform_submission_data', 'wsd');
        $query->fields('wsd', ['name', 'value']);
        $query->condition('wsd.sid', $row->sid);
        $result2 = $query->execute()->fetchAll();
        $issue = '';
        $iid = 0;
        $text = '';
        foreach ($result2 as $v) {
          if ($v->name == 'answer') {
            $text = $v->value;
          }
          if ($v->name == 'radios' && $v->value) {
            $issue = $options[$v->value];
            $iid = $v->value;
          }
        }
        $rows[$row->sid] = [
          'created' => format_date($row->created, 'short'),
          'uri' => new FormattableMarkup('<a href="' . $row->uri . '">' . $row->uri . '</a>', []),
          'type' => ($row->webform_id == 'yes_feedback' ? 'Yes' : 'No'),
          'issue' => $issue,
          'text' => $text,
          'taxonomy' => $taxonomy,
          'iid' => $iid,
          'tid' => $tid,
        ];
      }
      // Now remove if issues or text
      if ($values) {
        $issues = [];
        if ($values['issues']) {
          foreach($values['issues'] as $issue) {
            if ($issue) {
              $issues[] = $issue;
            }
          }
        }
        if ($issues) {
          foreach($rows as $key => $value) {
            if (!in_array($value['iid'], $issues)) {
              unset($rows[$key]);
            }
          }
        }
        if ($values['text']) {
          foreach($rows as $key => $value) {
            if (strpos($value['text'], $values['text']) === FALSE) {
              unset($rows[$key]);
            }
          }
        }
        if ($values['taxonomy']) {
          foreach($rows as $key => $value) {
            if ($value['tid'] !== $values['taxonomy']) {
              unset($rows[$key]);
            }
          }
        }
      }
    }
    foreach($rows as $key => $value) {
      unset($rows[$key]['iid']);
      unset($rows[$key]['tid']);
    }
    if ($values && @$values['op'] == 'Download CSV File') {
      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename=feedback.csv');
      $output = fopen('php://output', 'w');
      fputcsv($output, array($this->t('Date'), $this->t('URL'), $this->t('Yes/No'), $this->t('Feedback issue'), $this->t('Additional text'), $this->t('Topic')));
      foreach($rows as $row) {
        $a = explode('">', $row['uri']);
        $row['uri'] = str_replace('</a>', '' , $a[1]);
        fputcsv($output, $row);
      }
      fclose($output);
      exit(0);
    }else{
      $limit = 10;
      pager_default_initialize(count($rows), $limit);
      $page = pager_find_page();
      if (count($rows) > $limit) {
        $rows = array_slice($rows, $page * $limit, $limit);
      }
      $build['table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No feedback found'),
      ];
      $build['pager'] = array(
        '#type' => 'pager'
      );
      return drupal_render($build);
    }
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('feedback.settings')
    ->set('from_date', $form_state->getValue('from_date'))
    ->set('to_date', $form_state->getValue('to_date'))
    ->set('url', $form_state->getValue('url'))
    ->set('type', $form_state->getValue('type'))
    ->set('issues', $form_state->getValue('issues'))
    ->set('text', $form_state->getValue('text'))
    ->set('taxonomy', $form_state->getValue('taxonomy'))
    ->save();
    $form_state->setRebuild();
  }
  
  public function submitFormReset(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('feedback.settings')
    ->set('from_date', '')
    ->set('to_date', '')
    ->set('url', '')
    ->set('type', 3)
    ->set('issues', '')
    ->set('text', '')
    ->set('taxonomy', '')
    ->save();
    $form_state->setRebuild(FALSE);
  }
  
  public function submitDownload(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }
}