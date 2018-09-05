<?php

namespace Drupal\general\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the Admin form.
 */
class SyncForm extends ConfigFormBase {
    
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sync_form';
  }
  
  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'acas.settings',
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!_is_site('uat')) {
      drupal_set_message("Sync to Production can only be run from the UAT site!", 'error');
      return array('#markup' => '<h3>Not allowed</h3>');
    }
    $config = $this->config('acas.settings');
    $form['invalidate'] = [
      '#type' => 'checkbox',
      '#title' => 'Invalidate CloudFront content',
      '#default_value' => TRUE,
      '#description' => 'If any CSS changes'
    ];
    $form['content_only'] = [
      '#type' => 'checkbox',
      '#title' => 'Content only',
      '#default_value' => TRUE,
      '#description' => 'Syncronise only content, no code'
    ];
    $form['#prefix'] = '<h2>Syncronise content to Production</h2>';
    $form['#action'] = '/admin/config/development/sync-prod';
    $form['#attached']['library'][] = 'general/sync_prod';
    $form['#attributes']['onsubmit'] = 'return syncProd()';
    $form['#suffix'] = '<div id="sync_progress" class="hidden">Sync to Production has started, this might take several minutes. Please wait...</div>';
    return parent::buildForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
}