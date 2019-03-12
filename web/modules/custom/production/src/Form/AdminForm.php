<?php

namespace Drupal\production\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the Admin form.
 */
class AdminForm extends ConfigFormBase {
    
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_form';
  }
  
  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'acas.settings',
      'acas.cloudfront'
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('acas.settings');
    $cloudfront_config = $this->config('acas.cloudfront');
    $form['enable_feedback_email'] = array(
      '#type' => 'checkbox',
      '#default_value' => $config->get('enable_feedback_email'),
      '#title' => t('Enable feedback emails'),
    );
    $form['feedback'] = array(
      '#type' => 'fieldset',
      '#title' => t('Feedback email'),
      '#collapsible' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="enable_feedback_email"]' => array('checked' => TRUE),
        ],
      ],
    );
    $form['feedback']['site_email'] = array(
      '#type' => 'checkbox',
      '#default_value' => $config->get('site_email'),
      '#title' => t('Use the site email address'),
    );
    $form['feedback']['feedback_email'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('feedback_email') ?: '',
      '#title' => t('Feedback email address'),
      '#size' => 100,
      '#states' => [
        'visible' => [
          ':input[name="site_email"]' => array('checked' => FALSE),
        ],
      ],
    );
    $form['search_placeholder'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('search_placeholder') ?: 'Search beta website',
      '#title' => t('Search placeholder'),
      '#description' => t('Place holder for the Search form'),
      '#size' => 100,
    );
    $form['freeze'] = array(
      '#type' => 'checkbox',
      '#default_value' => $config->get('freeze') ?: 0,
      '#title' => t('Content freeze'),
      '#description' => t('When checked content can not be added/edited on this site'),
    );
    $form['sync'] = array(
      '#type' => 'fieldset',
      '#title' => t('Production content sync'),
      '#collapsible' => TRUE,
    );
    $form['sync']['prod'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('prod') ?: 'https://beta-acas.org.uk',
      '#title' => t('Production URL'),
    );
    $form['sync']['tables'] = array(
      '#type' => 'textarea',
      '#default_value' => $config->get('tables') ?: '',
      '#title' => t('Tables to exclude'),
      '#description' => t('Enter the tables to exclude, one per line'),
    );
    $form['sync']['config'] = array(
      '#type' => 'textarea',
      '#default_value' => $config->get('config') ?: '',
      '#title' => t('Configuration names to ignore'),
      '#description' => t('Enter the configuration names to ignore, one per line'),
    );
    $form['cloudfront'] = array(
      '#type' => 'fieldset',
      '#title' => t('CloudFront'),
      '#collapsible' => TRUE,
    );
    $form['cloudfront']['id'] = array(
      '#type' => 'textfield',
      '#default_value' => $cloudfront_config->get('id') ?: '',
      '#title' => t('Distribution ID'),
      '#required' => TRUE,
    );
    $form['cloudfront']['key'] = array(
      '#type' => 'textfield',
      '#default_value' => $cloudfront_config->get('key') ?: '',
      '#title' => t('AWS Key'),
      '#required' => TRUE,
    );
    $form['cloudfront']['secret'] = array(
      '#type' => 'textfield',
      '#default_value' => $cloudfront_config->get('secret') ?: '',
      '#title' => t('AWS Secret'),
      '#required' => TRUE,
    );
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
    \Drupal::configFactory()->getEditable('acas.settings')
    ->set('search_placeholder', $form_state->getValue('search_placeholder'))
    ->set('freeze', $form_state->getValue('freeze'))
    ->set('prod', $form_state->getValue('prod'))
    ->set('tables', $form_state->getValue('tables'))
    ->set('config', $form_state->getValue('config'))
    ->set('site_email', $form_state->getValue('site_email'))
    ->set('feedback_email', $form_state->getValue('feedback_email'))
    ->save();
    
    \Drupal::configFactory()->getEditable('acas.cloudfront')
    ->set('id', $form_state->getValue('id'))
    ->set('key', $form_state->getValue('key'))
    ->set('secret', $form_state->getValue('secret'))
    ->save();
    parent::submitForm($form, $form_state);
  }
}