<?php

namespace Drupal\production\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements the CloudFront invalidate Form.
 */
class CloudFrontForm extends ConfigFormBase {
    
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudfront_form';
  }
  
  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'acas.cloudfront'
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('acas.cloudfront');
    $form['all'] = array(
      '#type' => 'checkbox',
      '#default_value' => 1,
      '#title' => t('Clear the entire CloudFront cache'),
      '#description' => t('There is a limit of 1000 free individual URLs that can be cleared per month before there is a charge. If you want to clear just one URL, uncheck this checkbox.'),
    );
    $form['url'] = array(
      '#type' => 'textfield',
      '#title' => t('URL to clear'),
      '#description' => t('Enter the URL to clear including the forward slash eg. "/your-rights-during-redundancy/how-your-employer-must-consult-you"'),
      '#size' => 100,
      '#states' => [
        'visible' => [
          'input[name="all"]' => ['checked' => FALSE]
        ]
      ],
    );
    return parent::buildForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('all')) {
      $path = \Drupal::service('path.alias_manager')->getPathByAlias($form_state->getValue('url'));
      if(!preg_match('/node\/(\d+)/', $path, $matches)) {
        $form_state->setErrorByName('url', 'URL ' . $form_state->getValue('url') . ' not found!');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('all')) {
      $form_state->setRedirectUrl(url::fromUserInput('/admin/config/development/cloudfront-invalidate-do/0'));
    }else{
      $path = \Drupal::service('path.alias_manager')->getPathByAlias($form_state->getValue('url'));
      if (preg_match('/node\/(\d+)/', $path, $matches)) {
        $form_state->setRedirectUrl(url::fromUserInput('/admin/config/development/cloudfront-invalidate-do/' . $matches[1]));
      }
    }
  }
}