<?php

namespace Drupal\general\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Current website block' block.
 * @Block(
 *   id = "current_website",
 *   admin_label = @Translation("Current website block"),
 * )
 */
class CurrentWebsite extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = \Drupal::routeMatch()->getParameter('node');
    if ($entity->hasField('field_current_website')) {
      if ($url = $entity->field_current_website->uri) {
        $title = 'archived version of this advice';
        if ($entity->field_current_website->title) {
          $title = $entity->field_current_website->title;
        }
        return ['#markup' => '<div class="inset-text--beta"><p>This is our beta website. Pages are being tested and improved. You can view the <a href="' . $url . '">' . $title . '</a> on The National Archives website.</p></div>'];
      }
    }
    return ['#markup' => ''];
  }
}