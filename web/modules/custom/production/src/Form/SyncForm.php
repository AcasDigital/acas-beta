<?php

namespace Drupal\production\Form;

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
      //drupal_set_message("Sync to Production can only be run from the UAT site!", 'error');
      //return array('#markup' => '<h3>Not allowed</h3>');
    }
    $config = $this->config('acas.settings');
    $form['cloudfront'] = array(
      '#type' => 'checkbox',
      '#default_value' => TRUE,
      '#title' => t('Clear CloudFront cache'),
      '#description' => t('When checked the CloudFront cache will be cleared for updated content (if sync type is for database). If un-checked eg. for testing, you will have to manually clear the cache'),
    );
    $form['sync_type'] = [
      '#type' => 'radios',
      '#title' => 'Sync type',
      '#options' => [1 => 'Database only', 2 => 'Database and code', 3 => 'Code only'],
      '#default_value' => 1,
    ];
    $form['#prefix'] = '<h2>Syncronise database to Production</h2>';
    $form['#action'] = '/admin/config/development/sync-prod';
    $form['#attached']['library'][] = 'production/sync_prod';
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