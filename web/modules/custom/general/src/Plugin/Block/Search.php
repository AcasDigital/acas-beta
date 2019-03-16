<?php

namespace Drupal\general\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Search' block.
 * @Block(
 *   id = "search_block",
 *   admin_label = @Translation("Search block"),
 * )
 */
class Search extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\general\Form\SearchForm');
    unset($form['form_build_id']);
    unset($form['form_id']);
    return ['#markup' => \Drupal::service('renderer')->render($form)];
  }
}