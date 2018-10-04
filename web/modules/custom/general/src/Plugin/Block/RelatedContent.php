<?php

namespace Drupal\general\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'RelatedContent' block.
 * @Block(
 *   id = "related_content_block",
 *   admin_label = @Translation("Related Content block"),
 * )
 */
class RelatedContent extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (is_numeric($node)) {
      $node = \Drupal\node\Entity\Node::load($node);
    }
    $url = '';
    $parent = NULL;
    if ($node->hasField('field_taxonomy')) {
      $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadAllParents($node->get('field_taxonomy')->target_id);
      $parents = array_reverse($parents, TRUE);
      array_pop($parents);
      foreach($parents as $parent) {
        $url .= '/' . general_taxonomy_path($parent->getName());
      }
      $parent = array_pop($parents);
    }
    $output = '';
    if ($node->get('field_social_share')->value) {
      $block_manager = \Drupal::service('plugin.manager.block');
      $config = [];
      $plugin_block = $block_manager->createInstance('social_sharing_block', $config);
      $render = $plugin_block->build();
      $render['text'] = [
        '#markup' => '<div class="text">Share this page</div>',
        '#weight' => -1,
      ];
      $output .= '<div id="social-share">' . drupal_render($render) . '</div>';
    }
    if ($node->get('field_show_related_content')->value) {
      if ($node->getType() == 'details_page' || $node->getType() == 'secondary_page') {
        $output .= '<nav class="nav-related" aria-labelledby="nav-related__title">
        <h3 id="nav-related__title">
          Related content
        </h3>
        <div class="nav-related__list" tabindex="-1">
            <ul>';
        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($parent->getVocabularyId(), $parent->id());
        $path = $url;
        foreach($terms as $term) {
          if (!$term->depth) {
            $t = \Drupal\taxonomy\Entity\Term::load($term->tid);
            if ($t->get('field_enabled')->value) {
              $url = '/' . general_taxonomy_path($term->name);
              if ($node->get('field_taxonomy')->target_id != $term->tid) {
                $output .= '<li><a href="' . $url . '">' . $term->name . '</a></li>';
              }else{
                //$output .= '<li class="active">' . $term->name . '</li>';
              }
            }
          }
        }
        if ($node->hasField('field_related_content')) {
          foreach($node->get('field_related_content') as $link) {
            $url = $link->getUrl()->toString();
            $title = $link->title;
            if (strpos($url, 'http') === 0) {
              if (!$title) {
                $html = file_get_contents($url);
                $a = explode('<h1>', $html);
                $b = explode('</h1>', $a[1]);
                $title = $b[0];
              }
              $output .= '<li class="extra"><a class="external-link" href="' . $url . '">' . $title . '</a></li>';
            }else{
              $params = $link->getUrl()->getRouteParameters();
              $entity_type = key($params);
              if ($entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($params[$entity_type])) {
                if (!$title) {
                  $title = $entity->getTitle();
                }
                $output .= '<li class="extra"><a href="' . $url . '">' . $title . '</a></li>';
              }
            }
          }
        }
        $output .= '</ul></div></nav>';
      }else if ($node->getType() == 'support_page') {
        if ($node->hasField('field_related_content')) {
          $links = '';
          foreach($node->get('field_related_content') as $link) {
            $url = $link->getUrl()->toString();
            $title = $link->title;
            if (strpos($url, 'http') === 0) {
              if (!$title) {
                $html = file_get_contents($url);
                $a = explode('<h1>', $html);
                $b = explode('</h1>', $a[1]);
                $title = $b[0];
              }
              $output .= '<li class="extra"><a class="external-link" href="' . $url . '">' . $title . '</a></li>';
            }else{
              $params = $link->getUrl()->getRouteParameters();
              $entity_type = key($params);
              if ($entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($params[$entity_type])) {
                if (!$title) {
                  $title = $entity->getTitle();
                }
                $links .= '<li class="extra"><a href="' . $url . '">' . $title . '</a></li>';
              }
            }
          }
          if ($links) {
            $output .= '<nav class="nav-related" aria-labelledby="nav-related__title">
              <h3 id="nav-related__title">
                Related content
              </h3>
              <ul id="section-nav-list" tabindex="-1">
                <li>
                  <ul>' . $links . '</ul>
                </li>
              </ul>
            </nav>';
          }
        }
      }
    }
    return ['#markup' => $output];
  }

}
