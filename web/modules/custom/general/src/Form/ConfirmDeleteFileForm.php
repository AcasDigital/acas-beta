<?php

namespace Drupal\general\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form to confirm deletion of something by id.
 */
class ConfirmDeleteFileForm extends ConfirmFormBase {

  /**
   * ID of the item to delete.
   *
   * @var int
   * @var file
   */
  protected $fid;
  protected $file;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $fid = NULL) {
    $this->fid = $fid;
    $this->file = \Drupal\file\Entity\File::load($fid);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    file_delete($this->fid);
    $url_object = \Drupal::service('path.validator')->getUrlIfValid('/admin/content/files');
    $form_state->setRedirectUrl($url_object);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_delete_file_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $url_object = \Drupal::service('path.validator')->getUrlIfValid('/admin/content/files');
    return $url_object;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->file) {
      return t('Do you want to delete file %name?<br />', ['%name' => $this->file->getFilename()]);
    }
  }

}