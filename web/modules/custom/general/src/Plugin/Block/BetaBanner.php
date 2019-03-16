<?php

namespace Drupal\general\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'BetaBanner' block.
 * @Block(
 *   id = "beta_banner",
 *   admin_label = @Translation("Beta banner block"),
 * )
 */
class BetaBanner extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = '<span class="beta-icon">' . t('BETA') . '</span> ' . t('This is a new service - your <a href="@url">feedback</a> will help us to improve it.', ['@url' => '/report-a-problem-with-the-beta-website']);
    return ['#markup' => $output];
  }
}
