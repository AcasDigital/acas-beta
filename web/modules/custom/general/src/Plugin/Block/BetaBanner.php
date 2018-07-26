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
    $output = '<span class="beta-icon">BETA</span> This is a new service - your <a href="/report-a-problem-with-the-beta-website">feedback</a> will help us to improve it.';
    $site = '';
    $a = explode('.', $_SERVER['HTTP_HOST']);
    if (strpos($a[0], 'dev') !== FALSE) {
      $site .= 'DEV ';
    }else if (strpos($a[0], 'uat') !== FALSE) {
      $site .= 'UAT ';
    }
    if ($site) {
      $site = '<strong>' . $site . '</strong>';
    }
    return ['#markup' => $site . $output];
  }
}
