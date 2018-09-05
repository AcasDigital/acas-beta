<?php

namespace Drupal\general\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class DeployForm.
 *
 */
class DeployForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'deploy_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $header = array(
      'date' => t('Modified'),
      'title' => t('Title'),
      'type' => t('Type'),
      'author' => t('Author'),
    );
    $query = \Drupal::database()->select('node_field_data', 'nfd');
    $query->fields('nfd', ['nid', 'title', 'changed', 'uid']);
    $query->condition('status', 1);
    $query->orderBy('changed' , 'DESC'); 
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
    $results = $pager->execute()->fetchAll();
    $rows = [];
    foreach ($results as $result) {
      $account = \Drupal\user\Entity\User::load($result->uid);
      $node = \Drupal\node\Entity\Node::load($result->nid);
      $rows[$result->nid] = [
        'date' => format_date($result->changed),
        'title' => $result->title,
        'type' => $node->type->entity->label(),
        'author' => $account->getUsername(),
      ];
    }
    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $rows,
      '#empty' => t('No content found'),
    ];
    $form['pager'] = [
      '#type' => 'pager'
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Deploy to Production',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $json = [];
    $values = $form_state->getValues();
    $results = array_filter($values['table']);
    foreach ($results as $result) {
      $query = \Drupal::database()->select('node__field_summary', 's');
      $query->join('node_field_data', 'nfd', 'nfd.nid = s.entity_id');
      $query->fields('s', ['field_summary_value', 'field_summary_summary']);
      $query->fields('nfd', ['title']);
      $query->condition('entity_id', $result);
      if ($d = $query->execute()->fetchAll()) {
        $data = [
          'nid' => $result,
          'title' => $d[0]->title,
          'content' => $d[0]->field_summary_value,
          'summary' => $d[0]->field_summary_summary,
        ];
        $json[] = $data;
      }else {
        // Basic page
        $query = \Drupal::database()->select('node__body', 'b');
        $query->join('node_field_data', 'nfd', 'nfd.nid = b.entity_id');
        $query->fields('b', ['body_value', 'body_summary']);
        $query->fields('nfd', ['title']);
        $query->condition('entity_id', $result);
        if ($d = $query->execute()->fetchAll()) {
          $data = [
            'nid' => $result,
            'title' => $d[0]->title,
            'content' => $d[0]->body_value,
            'summary' => $d[0]->body_summary,
          ];
          $json[] = $data;
        }
      }
    }
    if ($json) {
      $POST_DATA = array(
        'data' => json_encode($json),
        'UUID' => \Drupal::config('system.site')->get('uuid'),
      );
      $config = $this->config('acas.settings');
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $config->get('prod') . '/deploy-update');
      curl_setopt($curl, CURLOPT_TIMEOUT, 30);
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $POST_DATA);
      $response = curl_exec($curl);
      curl_close ($curl);
      drupal_set_message('Finished deploying ' . count($json) . ' content items to Production');
    }
  }
}