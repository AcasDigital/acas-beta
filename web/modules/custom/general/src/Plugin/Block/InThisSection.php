<?php

namespace Drupal\general\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'InThisSection' block.
 * @Block(
 *   id = "in_this_section_block",
 *   admin_label = @Translation("In this section block"),
 * )
 */
class InThisSection extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (is_numeric($node)) {
      $node = \Drupal\node\Entity\Node::load($node);
    }
    $output = '';
    if ($node->hasField('field_taxonomy')) {
      $query = \Drupal::database()->select('taxonomy_index', 'ti');
      $query->join('taxonomy_term_field_data', 'fd', 'fd.tid = ti.tid');
      $query->join('node_field_data', 'nfd', 'nfd.nid = ti.nid');
      $query->leftJoin('node__field_weight', 'w' , 'w.entity_id = ti.nid');
      $query->fields('ti', array('nid'));
      $query->condition('ti.tid', $node->get('field_taxonomy')->target_id, '=');
      $query->condition('nfd.type', 'secondary_page', '=');
      $query->orderBy('w.field_weight_value', 'ASC');
      $result = $query->execute();
      if($nodeIds = $result->fetchCol()) {
        if ($node->getType() == 'details_page') {
          $output .= '<li class="active">' . $node->get('field_overview_title')->value . '</li>';
        }
        else {
          $query2 = \Drupal::database()->select('taxonomy_index', 'ti');
          $query2->join('taxonomy_term_field_data', 'fd', 'fd.tid = ti.tid');
          $query2->join('node_field_data', 'nfd', 'nfd.nid = ti.nid');
          $query2->fields('ti', array('nid'));
          $query2->condition('ti.tid', $node->get('field_taxonomy')->target_id, '=');
          $query2->condition('nfd.type', 'details_page', '=');
          $result2 = $query2->execute();
          if ($nid = $result2->fetchCol()) {
            if ($node2 = \Drupal\node\Entity\Node::load($nid[0])) {
              $output .= '<li><a href="' . $node2->toUrl()->toString() . '">' . $node2->get('field_overview_title')->value . '</a></li>';
            }
          }
        }
        $nodes = \Drupal\node\Entity\Node::loadMultiple($nodeIds);
        foreach($nodes as $n) {
          if ($node->id() == $n->id()) {
            $output .= '<li class="active">' . $n->getTitle() . '</li>';
          }
          else {
            $output .= '<li><a href="' . $n->toUrl()->toString() . '">' . $n->getTitle() . '</a></li>';
          }
        }
      }
    }
    if ($output) {
      $output = '<div class="title">' . t('Chapters') . '</div><ul>' . $output . '</ul>';
    }
    return ['#markup' => $output];
  }

}
