<?php

namespace Drupal\general\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'HomePage' block.
 * @Block(
 *   id = "home_page_block",
 *   admin_label = @Translation("Home Page block"),
 * )
 */
class HomePage extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = '';
    $node = \Drupal::routeMatch()->getParameter('node');
    if (is_numeric($node)) {
      $node = \Drupal\node\Entity\Node::load($node);
    }
    if ($node->hasField('field_recent_content')) {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
      foreach($node->get('field_recent_content') as $link) {
        $params = \Drupal\Core\Url::fromUri("internal:" . $link->getUrl()->toString())->getRouteParameters();
        $entity_type = key($params);
        $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($params[$entity_type]);
        $view = $view_builder->view($entity, 'teaser');
        $output .= \Drupal::service('renderer')->render($view);
      }
    }
    return ['#markup' => $output];
  }
}
