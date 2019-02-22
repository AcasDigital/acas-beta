<?php
/**
 * @file
 * Contains \Drupal\general\Controller\GeneralController.
 */

namespace Drupal\general\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\votingapi\Entity\Vote;
use Drupal\votingapi\Entity\VoteType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Dompdf\Dompdf;
use Drupal\Component\Utility\Html;

class GeneralController extends ControllerBase {
  public function searchheader() {
    return array('#markup' => '');
  }
  public function health() {
    return array('#markup' => general_health());
  }
  public function feedback($entity_id, $value) {
    if ($value == 'Yes') {
      $vote_value = 1;
    }
    else {
      $vote_value = -1;
    }
    
    $entity = $this->entityTypeManager()
      ->getStorage('node')
      ->load($entity_id);
      
    $voteType = VoteType::load('vote');
    $this->entityTypeManager()
      ->getViewBuilder('node')
      ->resetCache([$entity]);
      
    $vote = Vote::create(['type' => 'vote']);
    $vote->setVotedEntityId($entity_id);
    $vote->setVotedEntityType('node');
    $vote->setValueType($voteType->getValueType());
    $vote->setValue($vote_value);
    $vote->save();

    $this->entityTypeManager()
      ->getViewBuilder('node')
      ->resetCache([$entity]);
      
    return new JsonResponse([
      'vote' => $vote_value,
      'message_type' => 'status',
      'operation' => 'voted',
      'message' => t('Your vote was added.'),
    ]);
  }
  
  public function feedback_results() {
    $connection = \Drupal::database();
    $query = $connection->query("SELECT DISTINCT entity_id FROM {votingapi_result} v WHERE v.entity_type = 'node' AND v.type = 'vote'");
    $result = $query->fetchAll();
    $return = [];
    foreach($result as $v) {
      $node = \Drupal\node\Entity\Node::load($v->entity_id);
      $query2 = $connection->query("SELECT * FROM {votingapi_result} v WHERE v.entity_id = " . $v->entity_id);
      $result2 = $query2->fetchAll();
      $vote = [
        'title' => $node->getTitle(),
        'url' => $node->toUrl()->toString(),
      ];
      foreach($result2 as $v2) {
        $vote['vote'][$v2->function] = $v2->value;
      }
      $return[] = $vote;
    }
    return new JsonResponse($return);
  }
  
  public function anything_wrong_results() {
    $return = [];
    $connection = \Drupal::database();
    $query = $connection->query("SELECT s.sid, s.created FROM {webform_submission} s WHERE s.webform_id = 'anything_wrong'");
    $result = $query->fetchAll();
    foreach($result as $sid) {
      $query2 = $connection->query("SELECT name, value FROM {webform_submission_data} WHERE sid = :sid", array('sid' => $sid->sid));
      $result2 = $query2->fetchAll();
      $data = [];
      $data['sid'] = $sid->sid;
      $data['date'] = date('Y-m-d', $sid->created);
      foreach($result2 as $s) {
        if ($s->name == 'email_optional_') {
          $s->name = 'email';
        }
        if ($s->name == 'how_should_we_improve_this_page_') {
          $s->name = 'message';
        }
        if ($s->name == 'name_optional_') {
          $s->name = 'name';
        }
        $data[$s->name] = $s->value;
      }
      $return[] = $data;
    }
    return new JsonResponse($return);
  }
  
  public function guide_print_download($entity_id) {
    return general_guide_page($entity_id);
  }
  
  public function guide_print($entity_id) {
    return general_guide_page($entity_id);
  }
  
  public function page_print($entity_id) {
    $node = \Drupal\node\Entity\Node::load($entity_id);
    $buid = [];
    $build[] = [
      '#type' => 'markup',
      '#markup' => '<div class="col-xs-8 col-sm-6"><section id="block-sitebranding" class="block block-system block-system-branding-block clearfix"><img src="/themes/custom/acas/toplogo.png" alt="Home"></section></div>
        <header id="block-acas-page-title" class="block block-core block-page-title-block clearfix col-xs-12 col-md-7"><h1 class="page-header"><span>' . $node->getTitle() . '</span></h1></header>'
    ];
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build[] = $view_builder->view($node, 'print_download');
    $build['#attached']['library'][] = 'general/guide_print_modify';
    return $build;
  }
  
  public function guide_download($entity_id) {
    $node = \Drupal\node\Entity\Node::load($entity_id);
    $build = general_guide_page($entity_id);
    $html = general_download_html_alter(drupal_render($build));
    $html = '<html><head><title>' . $node->getTitle() . '</title><style>' . general_download_css() . '</style></head><body>' . $html . '</body></html>';
    $dompdf = new Dompdf(array('enable_remote' => true));
    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream(trim(general_taxonomy_path($node->getTitle())) . '.pdf');
    return array(
      '#markup' => '',
    );
  }
  
  public function page_download($entity_id) {
    $node = \Drupal\node\Entity\Node::load($entity_id);
    $buid = [];
    $build[] = [
      '#type' => 'markup',
      '#markup' => '<div class="col-xs-8 col-sm-6"><section id="block-sitebranding" class="block block-system block-system-branding-block clearfix"><img src="https://' . $_SERVER['HTTP_HOST'] . '/themes/custom/acas/toplogo.png" alt="Home"></section></div>
        <header id="block-acas-page-title" class="block block-core block-page-title-block clearfix col-xs-12 col-md-7"><h1 class="page-header"><span>' . $node->getTitle() . '</span></h1></header>'
    ];
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build[] = $view_builder->view($node, 'print_download');
    $html = general_download_html_alter(drupal_render($build));
    $html = '<html><head><title>' . $node->getTitle() . '</title><style>' . general_download_css() . '</style></head><body>' . $html . '</body></html>';
    $dompdf = new Dompdf(array('enable_remote' => true));
    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream(trim(general_taxonomy_path($node->getTitle())) . '.pdf');
    return array(
      '#markup' => '',
    );
  }
  
  public function freeze() {
    return array('#markup' => '<p>Content adding/editing is frozen on this site.</p><p>You can still add/edit content on the UAT site</p>');
  }
  
  public function feedback_page($type, $nid) {
    return general_feedback_page($type, $nid);
  }
  
  public function feedback_title($type, $nid) {
    if ($type == 'no') {
      return 'Please tell us why the information did not help.';
    }
    else {
      return 'What were you looking for?';
    }
  }
  
  public function feedback_email($webform_id, $sid) {
    
  }
}